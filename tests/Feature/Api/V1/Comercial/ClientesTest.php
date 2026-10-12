<?php

namespace Tests\Feature\Api\V1\Comercial;

use App\Models\Category;
use App\Models\Congress;
use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\TokenApi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function cliente(User $asesor, array $atributos = []): Customer
    {
        return Customer::create($atributos + [
            'nombre' => 'Laura',
            'apellido' => 'Méndez',
            'telefono' => (string) random_int(5500000000, 5599999999),
            'direccion' => 'Av. Reforma 10',
            'etapa' => 'cliente',
            'activo' => true,
            'recibe_promocion' => false,
            'asesor_id' => $asesor->id,
        ]);
    }

    private function datosAlta(array $cambios = []): array
    {
        return $cambios + [
            'nombre' => 'Jorge',
            'apellido' => 'Ramírez',
            'telefono' => '722 123 4567',
            'rfc' => 'raj800101abc',
            'gmail' => 'jorge@example.com',
            'direccion' => 'Calle Hidalgo 5, Toluca',
        ];
    }

    /* ===================== Listado ===================== */

    public function test_sin_ver_todos_solo_lista_los_clientes_propios(): void
    {
        $yo = $this->usuarioApi(['clientes.ver']);
        $otro = $this->usuarioApi();
        $mio = $this->cliente($yo, ['nombre' => 'Mío']);
        $this->cliente($otro, ['nombre' => 'Ajeno']);

        $this->conToken($yo)->getJson('/api/v1/clientes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mio->id)
            ->assertJsonPath('data.0.asesor.id', $yo->id)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_con_ver_todos_lista_todos_y_filtra(): void
    {
        $yo = $this->usuarioApi(['clientes.ver', 'clientes.ver_todos']);
        $otro = $this->usuarioApi();
        $this->cliente($yo, ['nombre' => 'Mío']);
        $prospecto = $this->cliente($otro, ['nombre' => 'Rosa', 'apellido' => 'Lima', 'etapa' => 'prospecto']);

        $peticion = $this->conToken($yo);

        $peticion->getJson('/api/v1/clientes')->assertOk()->assertJsonCount(2, 'data');

        $peticion->getJson('/api/v1/clientes?etapa=prospecto')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $prospecto->id);

        // Nombre completo, como lo teclea alguien en el buscador.
        $peticion->getJson('/api/v1/clientes?buscar=Rosa%20Lima')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.etapa', 'prospecto');

        $peticion->getJson('/api/v1/clientes?asesor_id='.$otro->id)
            ->assertOk()->assertJsonCount(1, 'data');

        $peticion->getJson('/api/v1/clientes?desde=2020-13-01')->assertStatus(422);
    }

    public function test_sin_permiso_de_ver_clientes_da_403(): void
    {
        $user = $this->usuarioApi(['cotizaciones.ver']);

        $this->conToken($user)->getJson('/api/v1/clientes')->assertForbidden();
    }

    public function test_la_llave_de_integracion_recorta_el_alcance(): void
    {
        $dueno = $this->usuarioApi(['clientes.ver', 'clientes.ver_todos', 'clientes.crear']);
        $otro = $this->usuarioApi();
        $this->cliente($dueno);
        $this->cliente($otro);

        // La llave solo trae clientes.ver: sin ver_todos, solo ve lo del dueño.
        $llave = $this->conToken($dueno, ['clientes.ver'], TokenApi::TIPO_INTEGRACION);

        $llave->getJson('/api/v1/clientes')->assertOk()->assertJsonCount(1, 'data');
        $llave->postJson('/api/v1/clientes', $this->datosAlta())->assertForbidden();

        $this->assertSame(2, Customer::count());
    }

    /* ===================== Detalle ===================== */

    public function test_detalle_trae_seguimientos_y_solo_las_cotizaciones_visibles(): void
    {
        $yo = $this->usuarioApi(['clientes.ver', 'clientes.ver_todos', 'cotizaciones.ver']);
        $otro = $this->usuarioApi();
        $cliente = $this->cliente($otro);
        $cliente->seguimientos()->create(['user_id' => $otro->id, 'tipo' => 'llamada', 'fecha' => now()->addDay()->toDateString()]);

        $mia = Cotizacion::create(['folio' => 'COT-2026-0001', 'customer_id' => $cliente->id, 'seller_id' => $yo->id, 'modalidad' => 'contado']);
        Cotizacion::create(['folio' => 'COT-2026-0002', 'customer_id' => $cliente->id, 'seller_id' => $otro->id, 'modalidad' => 'contado']);

        $this->conToken($yo)->getJson('/api/v1/clientes/'.$cliente->id)
            ->assertOk()
            ->assertJsonPath('data.id', $cliente->id)
            ->assertJsonCount(1, 'data.seguimientos')
            ->assertJsonPath('data.proximo_seguimiento.tipo', 'llamada')
            ->assertJsonPath('data.proximo_seguimiento.estado', 'pendiente')
            ->assertJsonCount(1, 'data.cotizaciones')
            ->assertJsonPath('data.cotizaciones.0.id', $mia->id);

        // Sin cotizaciones.ver, la ficha no las enseña.
        $sinCot = $this->usuarioApi(['clientes.ver', 'clientes.ver_todos']);
        $this->conToken($sinCot)->getJson('/api/v1/clientes/'.$cliente->id)
            ->assertOk()
            ->assertJsonMissingPath('data.cotizaciones');
    }

    public function test_el_cliente_de_otro_asesor_da_403_y_el_inexistente_404(): void
    {
        $yo = $this->usuarioApi(['clientes.ver', 'clientes.editar']);
        $ajeno = $this->cliente($this->usuarioApi());

        $peticion = $this->conToken($yo);

        $peticion->getJson('/api/v1/clientes/'.$ajeno->id)
            ->assertForbidden()->assertJsonPath('message', 'Este cliente lo registró otro asesor.');
        $peticion->putJson('/api/v1/clientes/'.$ajeno->id, $this->datosAlta())->assertForbidden();
        $peticion->getJson('/api/v1/clientes/999999')->assertNotFound();
    }

    /* ===================== Alta ===================== */

    public function test_registrar_cliente_con_primer_seguimiento(): void
    {
        $yo = $this->usuarioApi(['clientes.crear', 'clientes.ver']);
        $categoria = Category::create(['nombre' => 'Hospital']);

        $res = $this->conToken($yo)->postJson('/api/v1/clientes', $this->datosAlta([
            'categoria_id' => $categoria->id,
            'como_conocio' => 'Facebook',
            'recibe_promocion' => true,
            'etapa' => 'prospecto',
            'seguimiento_tipo' => 'cotizacion',
            'seguimiento_fecha' => now()->addMonth()->toDateString(),
            'seguimiento_nota' => 'Le interesa una torre',
        ]));

        $res->assertCreated()
            ->assertJsonPath('message', 'Cliente guardado correctamente.')
            ->assertJsonPath('data.asesor_id', $yo->id)
            ->assertJsonPath('data.rfc', 'RAJ800101ABC')
            ->assertJsonPath('data.etapa', 'prospecto')
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.recibe_promocion', true)
            ->assertJsonPath('data.categoria.nombre', 'Hospital')
            ->assertJsonPath('data.seguimientos.0.tipo', 'cotizacion')
            ->assertJsonPath('data.seguimientos.0.user_id', $yo->id);

        $this->assertDatabaseHas('clientes', ['id' => $res->json('data.id'), 'como_conocio' => 'Facebook', 'asesor_id' => $yo->id]);
        $this->assertDatabaseHas('cliente_seguimientos', ['customer_id' => $res->json('data.id'), 'nota' => 'Le interesa una torre', 'created_by' => $yo->id]);
    }

    public function test_alta_con_congreso_ignora_como_conocio(): void
    {
        $yo = $this->usuarioApi(['clientes.crear']);
        $congreso = Congress::create([
            'nombre' => 'Expo Médica',
            'categoria_id' => Category::create(['nombre' => 'Expos'])->id,
            'fecha_inicio' => now(),
            'fecha_finalizacion' => now()->addDays(2),
        ]);

        $res = $this->conToken($yo)->postJson('/api/v1/clientes', $this->datosAlta([
            'congreso_id' => $congreso->id,
            'como_conocio' => 'No debe guardarse',
        ]))->assertCreated();

        $this->assertNull(Customer::find($res->json('data.id'))->como_conocio);
        $res->assertJsonPath('data.congreso.id', $congreso->id);
    }

    public function test_alta_invalida_da_422_en_espanol(): void
    {
        $yo = $this->usuarioApi(['clientes.crear']);

        $this->conToken($yo)->postJson('/api/v1/clientes', [
            'apellido' => 'Sin nombre',
            'seguimiento_tipo' => 'llamada',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'telefono', 'direccion', 'seguimiento_fecha'])
            ->assertJsonPath('errors.seguimiento_fecha.0', 'Indica la fecha del seguimiento.');
    }

    public function test_alta_con_telefono_repetido_en_otro_formato_da_422(): void
    {
        $yo = $this->usuarioApi(['clientes.crear', 'clientes.ver']);
        $this->cliente($this->usuarioApi(), ['telefono' => '7221234567']);

        $this->conToken($yo)->postJson('/api/v1/clientes', $this->datosAlta(['telefono' => '+52 (722) 123-4567']))
            ->assertStatus(422)
            ->assertJsonPath('errors.telefono.0', 'Ya hay un cliente registrado con ese número de teléfono.');

        $this->assertSame(1, Customer::count());
    }

    public function test_sin_permiso_de_crear_da_403(): void
    {
        $yo = $this->usuarioApi(['clientes.ver']);

        $this->conToken($yo)->postJson('/api/v1/clientes', $this->datosAlta())->assertForbidden();
        $this->assertSame(0, Customer::count());
    }

    /* ===================== Posible duplicado ===================== */

    public function test_similar_avisa_aunque_el_cliente_sea_de_otro_asesor(): void
    {
        $yo = $this->usuarioApi(['clientes.ver']);
        $ajeno = $this->cliente($this->usuarioApi(), ['gmail' => 'Dra.Ana@Example.com']);

        $peticion = $this->conToken($yo);

        $peticion->getJson('/api/v1/clientes/similar?gmail=dra.ana@example.com')
            ->assertOk()
            ->assertJsonPath('data.motivo', 'correo')
            ->assertJsonPath('data.cliente.id', $ajeno->id)
            // No la puede abrir: no se le da la liga.
            ->assertJsonPath('data.cliente.url', null)
            ->assertJsonPath('data.cliente.alta', $ajeno->created_at->toDateString());

        $peticion->getJson('/api/v1/clientes/similar?gmail=dra.ana@example.com&ignorar='.$ajeno->id)
            ->assertOk()->assertJsonPath('data', null);
    }

    /* ===================== Edición ===================== */

    public function test_editar_conserva_las_casillas_que_no_se_mandan(): void
    {
        $yo = $this->usuarioApi(['clientes.editar']);
        $cliente = $this->cliente($yo, ['recibe_promocion' => true, 'activo' => true, 'etapa' => 'prospecto']);

        $this->conToken($yo)->putJson('/api/v1/clientes/'.$cliente->id, [
            'nombre' => 'Laura',
            'apellido' => 'Méndez Ruiz',
            'telefono' => $cliente->telefono,
            'direccion' => 'Nueva dirección 20',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Cliente actualizado correctamente.')
            ->assertJsonPath('data.apellido', 'Méndez Ruiz')
            ->assertJsonPath('data.recibe_promocion', true)
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.etapa', 'prospecto');

        $this->conToken($yo)->putJson('/api/v1/clientes/'.$cliente->id, [
            'nombre' => 'Laura',
            'apellido' => 'Méndez Ruiz',
            'telefono' => $cliente->telefono,
            'direccion' => 'Nueva dirección 20',
            'activo' => false,
        ])->assertOk()->assertJsonPath('data.activo', false);
    }

    public function test_editar_con_correo_de_otro_cliente_da_422(): void
    {
        $yo = $this->usuarioApi(['clientes.editar']);
        $this->cliente($yo, ['gmail' => 'ocupado@example.com']);
        $cliente = $this->cliente($yo);

        $this->conToken($yo)->putJson('/api/v1/clientes/'.$cliente->id, [
            'nombre' => 'Laura',
            'apellido' => 'Méndez',
            'telefono' => $cliente->telefono,
            'gmail' => 'OCUPADO@example.com',
            'direccion' => 'Av. Reforma 10',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['gmail']);
    }

    public function test_convertir_prospecto_en_cliente(): void
    {
        $yo = $this->usuarioApi(['clientes.editar']);
        $prospecto = $this->cliente($yo, ['etapa' => 'prospecto']);

        $this->conToken($yo)->postJson('/api/v1/clientes/'.$prospecto->id.'/convertir')
            ->assertOk()
            ->assertJsonPath('data.etapa', 'cliente')
            ->assertJsonPath('message', 'Laura Méndez ahora es cliente.');

        $this->assertSame('cliente', $prospecto->fresh()->etapa);

        $soloVer = $this->usuarioApi(['clientes.ver']);
        $this->conToken($soloVer)->postJson('/api/v1/clientes/'.$prospecto->id.'/convertir')->assertForbidden();
    }

    /* ===================== Catálogos y categorías ===================== */

    public function test_catalogos_solo_dan_usuarios_a_quien_ve_todos(): void
    {
        Category::create(['nombre' => 'Clínica']);
        $yo = $this->usuarioApi(['clientes.ver']);
        $jefe = $this->usuarioApi(['clientes.ver', 'clientes.ver_todos']);

        $this->conToken($yo)->getJson('/api/v1/clientes/catalogos')
            ->assertOk()
            ->assertJsonPath('data.categorias.0.nombre', 'Clínica')
            ->assertJsonPath('data.etapas.1.valor', 'prospecto')
            ->assertJsonPath('data.usuarios', []);

        $this->conToken($jefe)->getJson('/api/v1/clientes/catalogos')
            ->assertOk()
            ->assertJsonCount(2, 'data.usuarios');
    }

    public function test_crear_categoria_de_cliente(): void
    {
        $yo = $this->usuarioApi(['clientes.crear']);

        $this->conToken($yo)->postJson('/api/v1/clientes/categorias', ['nombre' => 'Consultorio'])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Consultorio');

        $this->conToken($yo)->postJson('/api/v1/clientes/categorias', ['nombre' => 'Consultorio'])
            ->assertStatus(422)->assertJsonValidationErrors(['nombre']);

        $this->conToken($this->usuarioApi(['clientes.ver']))
            ->postJson('/api/v1/clientes/categorias', ['nombre' => 'Otra'])->assertForbidden();
    }
}
