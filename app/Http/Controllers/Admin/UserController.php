<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountStatusMail;
use App\Models\EmployeeDocument;
use App\Models\Role;
use App\Models\User;
use App\Services\SessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Lista de todos los usuarios con métricas.
     */
    public function index(SessionManager $sessions, Request $request): View
    {
        $query = User::withCount('loginLogs')->with('roles', 'employeeDocuments');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('payroll_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($position = $request->get('position')) {
            $query->where('position', $position);
        }

        $users = $query->orderByDesc('created_at')->get();

        $activeCounts = $sessions->activeCountsForAll();

        $positions = User::whereNotNull('position')
            ->where('position', '!=', '')
            ->distinct()
            ->pluck('position')
            ->sort()
            ->values();

        return view('admin.users.index', [
            'users'        => $users,
            'activeCounts' => $activeCounts,
            'positions'    => $positions,
            'roles'        => Role::orderBy('label')->get(),
            'filters'      => $request->only(['search', 'status', 'position']),
        ]);
    }

    /**
     * Completa/edita los datos de RH (puesto, teléfono, nómina, datos personales,
     * contactos de emergencia y roles) de un usuario que ya está registrado en
     * el sistema. No crea cuentas nuevas: solo puede seleccionarse entre los
     * usuarios existentes.
     */
    public function updateHrProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'payroll_number' => ['nullable', 'string', 'max:100'],
            'checador_id' => ['nullable', 'string', 'max:100'],
            'curp' => ['nullable', 'string', 'max:18'],
            'ine' => ['nullable', 'string', 'max:255'],
            'acta_nacimiento' => ['nullable', 'string', 'max:255'],
            'licencia' => ['nullable', 'string', 'max:255'],
            'domicilio' => ['nullable', 'string'],
            'fecha_ingreso' => ['nullable', 'date'],
            'vacaciones_disponibles' => ['nullable', 'integer', 'min:0'],
            'nombre_contacto_emergencia' => ['nullable', 'string', 'max:255'],
            'numero_contacto_emergencia' => ['nullable', 'string', 'max:50'],
            'domicilio_contacto_emergencia' => ['nullable', 'string'],
            'nombre_contacto_emergencia_secundario' => ['nullable', 'string', 'max:255'],
            'numero_contacto_emergencia_secundario' => ['nullable', 'string', 'max:50'],
            'domicilio_contacto_emergencia_secundario' => ['nullable', 'string'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'ine_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'acta_nacimiento_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'licencia_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'curp_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $user = User::findOrFail($data['user_id']);

        $user->forceFill([
            'position' => $data['position'] ?? null,
            'cargo' => $data['cargo'] ?? null,
            'phone' => $data['phone'] ?? null,
            'payroll_number' => $data['payroll_number'] ?? null,
            'checador_id' => $data['checador_id'] ?? null,
            'curp' => $data['curp'] ?? null,
            'ine' => $data['ine'] ?? null,
            'acta_nacimiento' => $data['acta_nacimiento'] ?? null,
            'licencia' => $data['licencia'] ?? null,
            'domicilio' => $data['domicilio'] ?? null,
            'fecha_ingreso' => $data['fecha_ingreso'] ?? null,
            'vacaciones_disponibles' => $data['vacaciones_disponibles'] ?? 0,
            'nombre_contacto_emergencia' => $data['nombre_contacto_emergencia'] ?? null,
            'numero_contacto_emergencia' => $data['numero_contacto_emergencia'] ?? null,
            'domicilio_contacto_emergencia' => $data['domicilio_contacto_emergencia'] ?? null,
            'nombre_contacto_emergencia_secundario' => $data['nombre_contacto_emergencia_secundario'] ?? null,
            'numero_contacto_emergencia_secundario' => $data['numero_contacto_emergencia_secundario'] ?? null,
            'domicilio_contacto_emergencia_secundario' => $data['domicilio_contacto_emergencia_secundario'] ?? null,
        ])->save();

        $user->roles()->sync($data['roles'] ?? []);

        foreach ([
            'curp_file' => 'CURP',
            'ine_file' => 'INE',
            'acta_nacimiento_file' => 'Acta de nacimiento',
            'licencia_file' => 'Licencia de manejo',
        ] as $field => $documentName) {
            if ($request->hasFile($field)) {
                $this->storeEmployeeDocument($user, $documentName, $request->file($field));
            }
        }

        return back()->with('status', "Datos de RH actualizados para {$user->name}.");
    }

    /**
     * Guarda o reemplaza el archivo de un documento del empleado (INE, CURP,
     * acta de nacimiento, licencia, etc.). Si ya existe un documento con el
     * mismo nombre para el usuario, se reemplaza el archivo anterior.
     */
    protected function storeEmployeeDocument(User $user, string $documentName, \Illuminate\Http\UploadedFile $file): void
    {
        $document = EmployeeDocument::where('user_id', $user->id)
            ->where('name', $documentName)
            ->first();

        if ($document && $document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $path = $file->store("employee_documents/{$user->id}", 'public');

        EmployeeDocument::updateOrCreate(
            ['user_id' => $user->id, 'name' => $documentName],
            [
                'file_path' => $path,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]
        );
    }

    /**
     * Detalle de un usuario con su historial de conexiones.
     */
    public function show(User $user, SessionManager $sessions): View
    {
        $documents = $user->employeeDocuments()->latest()->get();
        $shifts = $user->employeeShifts()->orderByDesc('shift_date')->limit(10)->get();
        $shiftDates = $user->employeeShifts()
            ->where('shift_date', '>=', now()->startOfMonth())
            ->where('shift_date', '<=', now()->endOfMonth())
            ->get();

        return view('admin.users.show', [
            'user'        => $user,
            'logs'        => $user->loginLogs()->limit(50)->get(),
            'activeCount' => $sessions->activeCountFor($user->id),
            'documents'   => $documents,
            'shifts'      => $shifts,
            'shiftDates'  => $shiftDates,
        ]);
    }

    /**
     * Da o quita el rol de administrador.
     */
    public function toggleAdmin(User $user, Request $request): RedirectResponse
    {
        // No permitir que un admin se quite el rol a sí mismo (evita quedarse sin admins por error).
        if ($user->id === $request->user()->id) {
            return back()->with('status', 'No puedes cambiar tu propio rol de administrador.');
        }

        $user->forceFill(['is_admin' => ! $user->is_admin])->save();

        return back()->with('status', 'Rol actualizado para '.$user->name.'.');
    }

    /**
     * Aprueba una cuenta pendiente para darle acceso.
     */
    public function approve(User $user): RedirectResponse
    {
        $user->forceFill([
            'status'        => User::STATUS_APPROVED,
            'banned_reason' => null,
            'approved_at'   => now(),
        ])->save();

        $this->notify($user, 'approved');

        return back()->with('status', "Aprobaste el acceso de {$user->name}. Se le notificó por correo.");
    }

    /**
     * Banea / desactiva una cuenta y cierra sus sesiones activas.
     */
    public function ban(User $user, Request $request): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('status', 'No puedes desactivar tu propia cuenta.');
        }

        $data = $request->validate([
            'banned_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user->forceFill([
            'status'        => User::STATUS_BANNED,
            'banned_reason' => $data['banned_reason'] ?? null,
        ])->save();

        // Expulsar de todos sus dispositivos.
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $this->notify($user, 'banned', $data['banned_reason'] ?? null);

        return back()->with('status', "Desactivaste la cuenta de {$user->name}. Se le notificó por correo.");
    }

    /**
     * Reactiva una cuenta baneada (le devuelve el acceso).
     */
    public function unban(User $user): RedirectResponse
    {
        $user->forceFill([
            'status'        => User::STATUS_APPROVED,
            'banned_reason' => null,
            'approved_at'   => $user->approved_at ?? now(),
        ])->save();

        $this->notify($user, 'reactivated');

        return back()->with('status', "Reactivaste la cuenta de {$user->name}. Se le notificó por correo.");
    }

    /**
     * Envía el correo de notificación de estado (sin romper si el SMTP falla).
     */
    protected function notify(User $user, string $type, ?string $reason = null): void
    {
        try {
            Mail::to($user->email)->send(new AccountStatusMail($type, $user->name, $reason));
        } catch (\Throwable $e) {
            Log::error("No se pudo enviar el correo de estado ({$type}) a {$user->email}: ".$e->getMessage());
        }
    }
}
