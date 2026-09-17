<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Services\EquipmentReportController;
use App\Http\Controllers\Services\QrController;
use App\Http\Controllers\Services\ServiceController;
use App\Models\EquipmentType;
use App\Models\Service;
use App\Models\GarantiaDocumento;
use App\Models\Venta;

Route::middleware(['auth', 'verified', 'approved'])->group(function () {
    Route::get('/gestion-servicios/historial-servicios', function () {
        $services = Service::with(['customer', 'serviceEquipment'])->latest()->get();
        return view('structure.gestion_servicios.historial_servicios.menu_historial_servicios', compact('services'));
    })->name('gestion.servicios.historial');
    Route::get('/gestion-servicios/historial-servicios/aprobaciones', function () {
        $services = Service::where('status', 'registrado')
            ->with(['customer', 'currentStep'])
            ->latest()
            ->get();

        return view('structure.gestion_servicios.historial_servicios.aprobaciones.index', compact('services'));
    })->name('gestion.servicios.historial.aprobaciones.index');
    Route::get('/gestion-servicios/historial-servicios/aprobaciones/{service}', function (Service $service) {
        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'spareParts', 'serviceTrackings.serviceStep', 'currentStep']);

        return view('structure.gestion_servicios.historial_servicios.aprobaciones.show', compact('service'));
    })->name('gestion.servicios.historial.aprobaciones.show');

    Route::post('/gestion-servicios/historial-servicios/aprobaciones/{service}/cotizacion', [ServiceController::class, 'updateCotizacionPrecios'])
        ->name('gestion.servicios.historial.aprobaciones.cotizacion');

    Route::get('/gestion-servicios/historial-servicios/aprobaciones/cliente/{service}', [ServiceController::class, 'customerShow'])
        ->name('gestion.servicios.historial.aprobaciones.cliente')
        ->middleware('signed');

    Route::post('/gestion-servicios/historial-servicios/aprobaciones/cliente/{service}/decision', [ServiceController::class, 'customerDecide'])
        ->name('gestion.servicios.historial.aprobaciones.cliente.decision')
        ->middleware('signed');

    Route::get('/gestion-servicios/historial-servicios/externo', function () {
        $services = Service::with(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician', 'currentStep'])
            ->where('service_type', 'externo')
            ->whereIn('status', ['aprobado', 'en_progreso', 'completado', 'entregado'])
            ->latest()
            ->get();

        return view('structure.gestion_servicios.historial_servicios.Mantenimiento_Externo.Mantenimiento', compact('services'));
    })->name('gestion.servicios.externo');

    Route::get('/gestion-servicios/historial-servicios/{service}', [ServiceController::class, 'show'])
        ->name('gestion.servicios.historial.show');
    Route::post('/gestion-servicios/historial-servicios/{service}/aprobar', [ServiceController::class, 'approve'])
        ->name('gestion.servicios.historial.approve');
    Route::post('/gestion-servicios/historial-servicios/{service}/denegar', [ServiceController::class, 'deny'])
        ->name('gestion.servicios.historial.deny');
    Route::post('/gestion-servicios/historial-servicios/{service}/renovar-qr', [QrController::class, 'renew'])
        ->name('qr.renew');

    Route::get('/gestion-servicios/garantia', function () {
        $documentos = GarantiaDocumento::latest()->get();
        return view('structure.gestion_servicios.garantia.index', compact('documentos'));
    })->name('gestion.servicios.garantia.index');

    Route::get('/gestion-servicios/garantia/agregar-carta', function () {
        $equipmentTypes = EquipmentType::orderBy('name')->get();
        return view('structure.gestion_servicios.garantia.create', compact('equipmentTypes'));
    })->name('gestion.servicios.garantia.agregar_carta');

    Route::post('/gestion-servicios/garantia/agregar-carta', function (Request $request) {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo_equipo' => 'required|string|max:255',
            'archivos' => 'nullable|array|max:20',
            'archivos.*' => 'file|max:20480',
        ], [
            'nombre.required' => 'El nombre de la carta es obligatorio.',
            'tipo_equipo.required' => 'El tipo de equipo es obligatorio.',
            'archivos.*.file' => 'Cada archivo debe ser un archivo válido.',
            'archivos.*.max' => 'Cada archivo no debe superar los 20 MB.',
        ]);

        $paths = [];
        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $archivo) {
                $paths[] = $archivo->store('garantias', 'public');
            }
        }

        GarantiaDocumento::create([
            'folio' => GarantiaDocumento::siguienteFolio(),
            'nombre' => $data['nombre'],
            'tipo_equipo' => $data['tipo_equipo'],
            'archivos' => $paths,
        ]);

        return redirect()->route('gestion.servicios.garantia.index')->with('success', 'Carta agregada correctamente.');
    })->name('gestion.servicios.garantia.guardar_carta');



    Route::get('/gestion-servicios/mantenimiento/{service}/reporte/raw', function (Service $service) {
        $service->load(['customer', 'serviceEquipment', 'internalTechnician', 'externalTechnician']);

        $equipo = $service->serviceEquipment;
        $tecnico = $service->service_type === 'interno'
            ? $service->internalTechnician?->name
            : $service->externalTechnician?->name;

        $datos = [
            'cliente' => trim(($service->customer?->nombre ?? '').' '.($service->customer?->apellido ?? '')),
            'folio' => $service->service_number ?? '',
            'equipo' => $equipo?->type_text ?? '',
            'marca' => $equipo?->brand_text ?: 'Fujinon',
            'modelo' => $equipo?->model_text ?? '',
            'ns' => $equipo?->serial_number ?? '',
            'diagnostico' => $equipo?->description ?? '',
            'fecha' => $service->created_at?->format('d/m/Y') ?? '',
            'tecnico' => $tecnico ?? '',
        ];

        $html = file_get_contents(
            resource_path('views/structure/gestion_servicios/Registro/Fujinon.blade.php')
        );

        $script = '<style>'
            .'html,body,*{scrollbar-width:none!important;}'
            .'::-webkit-scrollbar{width:0!important;height:0!important;display:none!important;}'
            .'#btnTema{display:none!important;}'
            .'body{background:#070c17!important;background-image:none!important;}'
            .'body.light{background:#f4f6fa!important;background-image:none!important;color:#1f2733!important;}'
            .'body.light input,body.light select,body.light textarea{color:#1f2733!important;background:#fff!important;border-color:#d8dee7!important;}'
            .'body.light input::placeholder,body.light textarea::placeholder{color:#8a97a8!important;}'
            .'body.light .datos{border-color:#d8dee7!important;}'
            .'body.light .campo{border-color:#eef1f5!important;}'
            .'body.light .pill input:checked+span{background:#2f4a8c!important;color:#fff!important;border-color:#2f4a8c!important;}'
            .'body.light .pill input+span{background:#fff!important;}'
            .'body.light .evid-add,body.light .evid-item{background:#f4f7fb!important;color:#5b6675!important;border-color:#d8dee7!important;}'
            .'body.light .rail{background:transparent!important;}'
            .'body.light .rail-head,body.light .rail-pie{background:#fff!important;color:#1f2733!important;border-color:#d8dee7!important;}'
            .'body.light .rail-item{background:#fff!important;border-color:#d8dee7!important;}'
            .'body.light .rail-item-tit,body.light .diag-name{color:#1f2733!important;}'
            .'body.light .rail-zoom{background:#2f4a8c!important;color:#fff!important;border-color:#2f4a8c!important;}'
            .'body.light .seccion{border-color:#d8dee7!important;}'
            .'body.light .resumen{background:#fff!important;border-color:#d8dee7!important;color:#1f2733!important;}'
            .'body.light .fab{background:#2f4a8c!important;color:#fff!important;}'
            .'body.light .autosave{color:#5b6675!important;}'
            .'body.light .tabla-mec th,body.light .tabla-mec td{border-color:#d8dee7!important;}'
            .'body.light .perfil-lbl{color:#5b6675!important;}'
            .'body.light .et-firma{color:#5b6675!important;}'
            .'body.light .firma-select,body.light .firma-date{color:#1f2733!important;background:#fff!important;border-color:#d8dee7!important;}'
            .'body.light .modal{border-color:#d8dee7!important;}'
            .'body.light .modal-close{background:#eef2f7!important;color:#1f2733!important;}'
            .'body.light .cap-toggle{color:#2f4a8c!important;border-color:#cdd6e2!important;}'
            .'@media print{'
            .'@page{size:letter portrait;margin:3mm;}'
            .'.hoja{zoom:.55;}'
            .'.encabezado{padding:8px 14px!important;gap:12px!important;}'
            .'.encabezado .logo{height:38px!important;}'
            .'.titulo-doc .t1{font-size:15px!important;}'
            .'.titulo-doc .t2{font-size:9px!important;}'
            .'.cuerpo{padding:0!important;}'
            .'.barra,.barra-sec{padding:4px 8px!important;font-size:11px!important;margin:0!important;}'
            .'.datos{margin-bottom:8px!important;}'
            .'.campo{min-height:20px!important;}'
            .'.campo .et{flex:0 0 88px!important;font-size:8px!important;padding:0 6px!important;}'
            .'.campo input[type=text]{font-size:9px!important;padding:0 6px!important;}'
            .'.seg{padding:0 6px!important;gap:4px!important;}'
            .'.pill span{padding:1px 8px!important;font-size:9px!important;}'
            .'.perfil-bar{padding:4px 8px!important;font-size:10px!important;margin-bottom:6px!important;}'
            .'.perfil-hint{display:none!important;}'
            .'table{font-size:9px!important;}'
            .'table th,table td{padding:2px 4px!important;}'
            .'.est span{width:16px!important;height:16px!important;}'
            .'td.obs input,td.mec-cell input{font-size:9px!important;padding:1px 4px!important;}'
            .'.evid-add,.quitar{display:none!important;}'
            .'.evid-grid img{max-height:40px!important;}'
            .'.firma{padding:6px 10px!important;gap:8px!important;}'
            .'.firma .f{padding:6px 8px!important;}'
            .'.seccion,.datos,.firma{page-break-inside:avoid;}'
            .'}'
            .'</style>'
            .'<script>(function(){'
            .'var d='.json_encode($datos, JSON_UNESCAPED_UNICODE).';'
            .'for(var k in d){'
            .'var el=document.querySelector("[data-key=\""+k+"\"]");'
            .'if(!el)continue;'
            .'if(el.tagName==="SELECT"){'
            .'if(d[k]&&![].some.call(el.options,function(o){return o.value===d[k];})){'
            .'el.appendChild(new Option(d[k],d[k],true,true));'
            .'}else{el.value=d[k];}'
            .'}else{el.value=d[k];}'
            .'el.dispatchEvent(new Event("input",{bubbles:true}));'
            .'el.dispatchEvent(new Event("change",{bubbles:true}));'
            .'}})();</script>';

        $saved = $service->reporte_data ? json_decode($service->reporte_data, true) : null;
        $saveUrl = route('gestion.servicios.mantenimiento.reporte.guardar', $service);
        $token = csrf_token();

        $script .= '<script>(function(){'
            .'var SAVED='.json_encode($saved, JSON_UNESCAPED_UNICODE).';'
            .'var URL='.json_encode($saveUrl).';'
            .'var TOKEN='.json_encode($token).';'
            .'if(SAVED){try{if(typeof aplicarSnapshot==="function")aplicarSnapshot(SAVED);}catch(e){}}'
            .'var bar=document.querySelector(".toolbar");'
            .'if(bar){'
            .'var btn=document.createElement("button");'
            .'btn.type="button";btn.className="ico";btn.id="btnGuardarReporte";'
            .'btn.title="Guardar reporte";btn.setAttribute("aria-label","Guardar reporte");'
            .'btn.innerHTML=\'<svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>\';'
            .'var prog=bar.querySelector(".prog");'
            .'if(prog&&prog.nextSibling){bar.insertBefore(btn,prog.nextSibling);}else{bar.appendChild(btn);}'
            .'btn.addEventListener("click",function(){'
            .'btn.disabled=true;'
            .'var payload=null;'
            .'try{payload=(typeof snapshot==="function")?snapshot():null;}catch(e){}'
            .'fetch(URL,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":TOKEN,"Accept":"application/json"},body:JSON.stringify({reporte:payload})})'
            .'.then(function(r){if(!r.ok)throw new Error("HTTP "+r.status);return r.json();})'
            .'.then(function(){'
            .'var s=document.getElementById("autosave");'
            .'if(s){s.textContent="Reporte guardado \u2713";s.classList.add("show");setTimeout(function(){s.classList.remove("show");},2000);}'
            .'else{alert("Reporte guardado.");}'
            .'})'
            .'.catch(function(e){alert("No se pudo guardar el reporte: "+e.message);})'
            .'.finally(function(){btn.disabled=false;});'
            .'});'
            .'}})();</script>';

        $script .= '<script>(function(){'
            .'if(new URLSearchParams(location.search).has("descargar")){'
            .'window.addEventListener("load",function(){setTimeout(function(){window.print();},600);});'
            .'}'
            .'})();</script>';

        $pos = strripos($html, '</body>');
        $html = $pos === false
            ? $html.$script
            : substr($html, 0, $pos).$script.substr($html, $pos);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    })->name('gestion.servicios.mantenimiento.reporte.raw');

    Route::post('/gestion-servicios/mantenimiento/{service}/reporte', function (Request $request, Service $service) {
        $data = $request->input('reporte');

        $service->forceFill([
            'reporte_data' => is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
        ])->save();

        return response()->json(['ok' => true]);
    })->name('gestion.servicios.mantenimiento.reporte.guardar');
});

Route::get('/qr/{token}', [QrController::class, 'show'])
    ->name('qr.show');
Route::post('/qr/{token}', [QrController::class, 'update'])
    ->name('qr.update');
Route::get('/qr/{token}/imprimir', [QrController::class, 'print'])
    ->name('qr.print');
Route::get('/qr-completado', function () {
    return view('structure.gestion_servicios.historial_servicios.qr.completed');
})->name('qr.completed');

Route::get('/reporte-equipo', [EquipmentReportController::class, 'create'])
    ->name('reporte.equipo.create');
Route::post('/reporte-equipo', [EquipmentReportController::class, 'store'])
    ->name('reporte.equipo.store');
Route::post('/api/qr/generar', [EquipmentReportController::class, 'generateQr'])
    ->name('qr.generar');
