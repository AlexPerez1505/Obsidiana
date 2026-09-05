<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\EquipmentReport;
use App\Support\CodigoQr;
use Illuminate\Http\Request;

class EquipmentReportController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_email' => 'nullable|string|max:255',
            'equipment_type' => 'nullable|string|max:255',
            'equipment_subtype' => 'nullable|string|max:255',
            'equipment_brand' => 'nullable|string|max:255',
            'equipment_model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'observations' => 'nullable|string',
            'technician_name' => 'nullable|string|max:255',
        ]);

        return view('structure.gestion_servicios.historial_servicios.reportes.create', compact('data'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_email' => 'nullable|string|max:255',
            'equipment_type' => 'nullable|string|max:255',
            'equipment_subtype' => 'nullable|string|max:255',
            'equipment_brand' => 'nullable|string|max:255',
            'equipment_model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'observations' => 'nullable|string',
            'technician_name' => 'nullable|string|max:255',
            'report' => 'required|string|max:5000',
        ]);

        EquipmentReport::create($validated);

        return redirect()->route('reporte.equipo.create', $request->only([
            'customer_name', 'customer_phone', 'customer_email',
            'equipment_type', 'equipment_subtype', 'equipment_brand', 'equipment_model',
            'serial_number', 'description', 'observations', 'technician_name',
        ]))->with('status', 'Reporte enviado. Gracias.');
    }

    public function generateQr(Request $request)
    {
        $data = $request->validate([
            'url' => 'required|string|max:3000',
        ]);

        $svg = CodigoQr::svg($data['url'], 220);

        return response($svg)->header('Content-Type', 'image/svg+xml');
    }
}
