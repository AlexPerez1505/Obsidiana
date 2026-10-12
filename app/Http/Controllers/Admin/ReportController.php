<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Administracion\ReportesDePersonal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla de Reportes de personal. La consulta, los resúmenes y el alta
 * viven en ReportesDePersonal, que comparte con la API.
 */
class ReportController extends Controller
{
    public function __construct(private ReportesDePersonal $reportes)
    {
    }

    public function index(Request $request): View
    {
        $filters = [
            'start_date' => $request->input('start_date', '2026-08-01'),
            'end_date' => $request->input('end_date', '2026-08-31'),
            'area' => $request->input('area', 'all'),
            'person' => $request->input('person', ''),
        ];

        // Las fechas solo filtran si llegaron; las de arriba son las que
        // se muestran en el formulario.
        $reports = $this->reportes->consulta([
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'area' => $filters['area'],
            'person' => $filters['person'],
        ])->get();

        $employees = $this->reportes->resumenPorColaborador($reports);

        return view('admin.reportes.index', [
            'employees' => $employees,
            'metrics' => $this->reportes->indicadores($reports, $employees),
            'records' => $this->reportes->renglones($reports),
            'pendingModules' => $this->reportes->pendientesPorModulo($reports),
            'areas' => $this->reportes->areas(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate(ReportesDePersonal::reglasAlta());

        $this->reportes->registrar($data, $request->user());

        return redirect()
            ->route('admin.reports.index')
            ->with('status', 'Reporte generado correctamente.');
    }
}
