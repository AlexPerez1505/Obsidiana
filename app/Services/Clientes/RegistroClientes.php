<?php

namespace App\Services\Clientes;

use App\Models\Category;
use App\Models\ClienteSeguimiento;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de clientes, compartidas por la web y la API.
 *
 * Vive aquí y no en el controlador para que las dos entradas apliquen
 * exactamente las mismas reglas: la misma limpieza previa, el mismo
 * aviso de cliente repetido y los mismos valores por defecto. Si cada
 * una tuviera su copia, la primera corrección dejaría a la otra atrás.
 */
class RegistroClientes
{
    /**
     * Limpia la entrada antes de validar.
     *
     * RFC, categoría y congreso son opcionales: cuando llegan vacíos se
     * guardan como NULL para no chocar con el índice único del RFC ni con
     * las llaves foráneas.
     */
    public function normalizar(Request $request): void
    {
        $rfc = strtoupper(trim((string) $request->input('rfc')));

        $congresoId = $request->filled('congreso_id') ? $request->input('congreso_id') : null;

        $request->merge([
            'telefono' => trim((string) $request->input('telefono')),
            'rfc' => $rfc !== '' ? $rfc : null,
            'categoria_id' => $request->filled('categoria_id') ? $request->input('categoria_id') : null,
            'congreso_id' => $congresoId,
            // El congreso ya responde "cómo lo conocimos"; el texto libre
            // solo aplica cuando no se levantó en uno.
            'como_conocio' => $congresoId ? null : (trim((string) $request->input('como_conocio')) ?: null),
        ]);
    }

    /**
     * ¿El teléfono o el correo ya son de otro cliente? Regresa el aviso
     * listo para mostrar, o null si no hay choque.
     *
     * @return array{motivo: string, mensaje: string, cliente: Customer}|null
     */
    public function similar(?string $telefono, ?string $correo, ?int $ignorarId = null): ?array
    {
        $similar = Customer::buscarSimilar($telefono, $correo, $ignorarId);

        if (! $similar) {
            return null;
        }

        return [
            'motivo' => $similar['motivo'],
            'mensaje' => $similar['motivo'] === 'correo'
                ? 'Ya hay un cliente registrado con ese correo electrónico.'
                : 'Ya hay un cliente registrado con ese número de teléfono.',
            'cliente' => $similar['cliente'],
        ];
    }

    /** El campo del formulario donde se marca el choque. */
    public function campoDelChoque(array $aviso): string
    {
        return $aviso['motivo'] === 'correo' ? 'gmail' : 'telefono';
    }

    public function reglasAlta(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:20', Rule::unique('clientes', 'telefono')],
            'rfc' => ['nullable', 'string', 'max:13', Rule::unique('clientes', 'rfc')],
            'gmail' => ['nullable', 'email', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'comentarios' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'congreso_id' => ['nullable', 'exists:congresos_eventos,id'],
            'como_conocio' => ['nullable', 'string', 'max:255'],
            'recibe_promocion' => ['nullable', 'boolean'],
            'etapa' => ['nullable', Rule::in(array_keys(Customer::ETAPAS))],
            // Primer seguimiento, opcional: "cotizarle en un mes", etc.
            'seguimiento_tipo' => ['nullable', Rule::in(array_keys(ClienteSeguimiento::TIPOS))],
            'seguimiento_fecha' => ['nullable', 'date', 'after_or_equal:today', 'required_with:seguimiento_tipo'],
            'seguimiento_nota' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function mensajesAlta(): array
    {
        return [
            'telefono.unique' => 'Este teléfono ya está registrado en otro cliente.',
            'rfc.unique' => 'Este RFC ya está registrado en otro cliente.',
            'seguimiento_fecha.after_or_equal' => 'La fecha del seguimiento no puede ser anterior a hoy.',
            'seguimiento_fecha.required_with' => 'Indica la fecha del seguimiento.',
        ];
    }

    public function reglasEdicion(Customer $cliente): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:20', Rule::unique('clientes', 'telefono')->ignore($cliente->id)],
            'rfc' => ['nullable', 'string', 'max:13', Rule::unique('clientes', 'rfc')->ignore($cliente->id)],
            'gmail' => ['nullable', 'email', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'comentarios' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'congreso_id' => ['nullable', 'exists:congresos_eventos,id'],
            'como_conocio' => ['nullable', 'string', 'max:255'],
            'recibe_promocion' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
            'etapa' => ['nullable', Rule::in(array_keys(Customer::ETAPAS))],
        ];
    }

    public function mensajesEdicion(): array
    {
        return [
            'telefono.unique' => 'Este teléfono ya está registrado en otro cliente.',
            'rfc.unique' => 'Este RFC ya está registrado en otro cliente.',
        ];
    }

    /**
     * Da de alta al cliente (ya validado) a nombre de quien lo registra,
     * con su primer seguimiento si se pidió uno.
     */
    public function registrar(array $data, User $asesor): Customer
    {
        $data['recibe_promocion'] = $this->siONo($data['recibe_promocion'] ?? false);
        $data['activo'] = true;
        $data['asesor_id'] = $asesor->id;
        $data['etapa'] = $data['etapa'] ?? 'cliente';

        $seguimiento = filled($data['seguimiento_fecha'] ?? null) ? [
            'user_id' => $asesor->id,
            'tipo' => $data['seguimiento_tipo'] ?? 'llamada',
            'fecha' => $data['seguimiento_fecha'],
            'nota' => $data['seguimiento_nota'] ?? null,
            'created_by' => $asesor->id,
        ] : null;

        unset($data['seguimiento_tipo'], $data['seguimiento_fecha'], $data['seguimiento_nota']);

        $customer = Customer::create($data);

        if ($seguimiento) {
            $customer->seguimientos()->create($seguimiento);
        }

        return $customer;
    }

    /**
     * Guarda la edición (ya validada). Lo que no llega cae en el valor del
     * formulario: activo si no se dice lo contrario, sin promoción si no se
     * marcó, y la etapa que ya tenía.
     */
    public function actualizar(Customer $cliente, array $data): Customer
    {
        $data['recibe_promocion'] = $this->siONo($data['recibe_promocion'] ?? false);
        $data['activo'] = array_key_exists('activo', $data) ? $this->siONo($data['activo']) : true;
        $data['etapa'] = $data['etapa'] ?? $cliente->etapa;

        $cliente->update($data);

        return $cliente;
    }

    public function reglasCategoria(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', 'unique:categorias,nombre'],
        ];
    }

    public function registrarCategoria(array $data): Category
    {
        return Category::create($data);
    }

    /** Igual que $request->boolean(): "1", "true", "on" y "yes" cuentan como sí. */
    private function siONo(mixed $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }
}
