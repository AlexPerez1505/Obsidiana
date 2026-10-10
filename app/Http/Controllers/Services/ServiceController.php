<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Equipo;
use App\Models\EquipmentType;
use App\Models\ExternalTechnician;
use App\Models\Service;
use App\Models\ServiceInvitation;
use App\Models\User;
use App\Services\Servicios\OrdenesDeServicio;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(private OrdenesDeServicio $ordenes)
    {
    }

    public function createFromInvitation(ServiceInvitation $invitation)
    {
        if (!$invitation->isValid()) {
            abort(403, 'La invitación no es válida o ya fue usada.');
        }

        $customers = Customer::with('seller')->latest()->get();
        $equipmentTypes = EquipmentType::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $externalTechnicians = ExternalTechnician::where('is_active', true)->orderBy('name')->get();
        $internalTechnicians = User::where('status', User::STATUS_APPROVED)->orderBy('name')->get();

        $equipos = Equipo::all();

        return view('structure.gestion_servicios.historial_servicios.registro_servicio.c_registro_serv', compact('customers', 'equipmentTypes', 'brands', 'equipos', 'externalTechnicians', 'internalTechnicians', 'invitation'));
    }

    public function publicStore(Request $request, ServiceInvitation $invitation)
    {
        if (!$invitation->isValid()) {
            return back()->with('error', 'La invitación no es válida o ya fue usada.');
        }

        $service = $this->ordenes->registrar($request, $invitation->invited_by);

        $invitation->update([
            'status' => 'used',
            'used_at' => now(),
        ]);

        return redirect()->route('gestion.servicios.historial')
            ->with('success', "Servicio {$service->service_number} creado.");
    }

    public function store(Request $request)
    {
        $service = $this->ordenes->registrar($request, auth()->id());

        return redirect()->route('gestion.servicios.historial')
            ->with('success', "Servicio {$service->service_number} creado.");
    }

    public function show(Service $service)
    {
        return redirect()->route('gestion.servicios.historial');
    }

    public function approve(Service $service)
    {
        if (! $this->ordenes->aprobar($service)) {
            return back()->with('error', 'La orden no puede aprobarse en su estado actual.');
        }

        return redirect()->route('gestion.servicios.historial')
            ->with('success', "Orden {$service->service_number} aprobada.");
    }

    public function deny(Service $service)
    {
        if (! $this->ordenes->denegar($service)) {
            return back()->with('error', 'La orden no puede denegarse en su estado actual.');
        }

        return back()->with('success', "Orden {$service->service_number} denegada.");
    }

    public function invite()
    {
        $invitation = $this->ordenes->invitar(auth()->id());

        return response()->json([
            'link' => route('public.nueva_orden', $invitation),
            'token' => $invitation->token,
            'expires_at' => $invitation->expires_at,
        ]);
    }
}
