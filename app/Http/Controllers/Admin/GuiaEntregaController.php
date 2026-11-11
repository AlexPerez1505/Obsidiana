<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuiaEntrega;
use App\Models\ProductoSerial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GuiaEntregaController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $guias = GuiaEntrega::query()
            ->with(['creador', 'seriales.producto'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('numero_rastreo', 'like', $like)
                        ->orWhere('contenido', 'like', $like)
                        ->orWhere('destinatario', 'like', $like)
                        ->orWhereHas('seriales', fn ($s) => $s->where('no_serie', 'like', $like)->orWhere('codigo', 'like', $like));
                });
            })
            ->latest('fecha_entrega')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.guia_entregas.index', compact('guias', 'search'));
    }

    public function create(): View
    {
        return view('admin.guia_entregas.create');
    }

    public function searchSeriales(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q'));
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $like = '%'.$term.'%';
        $seriales = ProductoSerial::query()
            ->with('producto:id,tipo_equipo,subtipo,marca,modelo')
            ->where(function ($query) use ($like) {
                $query->where('no_serie', 'like', $like)
                    ->orWhere('codigo', 'like', $like)
                    ->orWhereHas('producto', function ($product) use ($like) {
                        $product->where('tipo_equipo', 'like', $like)
                            ->orWhere('subtipo', 'like', $like)
                            ->orWhere('marca', 'like', $like)
                            ->orWhere('modelo', 'like', $like);
                    });
            })
            ->whereNotNull('no_serie')
            ->where('no_serie', '!=', '')
            ->orderBy('no_serie')
            ->limit(15)
            ->get()
            ->map(fn (ProductoSerial $serial) => [
                'id' => $serial->id,
                'serie' => $serial->no_serie,
                'codigo' => $serial->codigo,
                'equipo' => trim(implode(' ', array_filter([
                    $serial->producto?->tipo_equipo,
                    $serial->producto?->subtipo,
                    $serial->producto?->marca,
                    $serial->producto?->modelo,
                ]))),
            ]);

        return response()->json($seriales);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'numero_rastreo' => ['required', 'string', 'max:120', 'unique:guia_entregas,numero_rastreo'],
            'peso_total' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'fecha_entrega' => ['required', 'date'],
            'contenido' => ['nullable', 'string', 'max:5000'],
            'destinatario' => ['nullable', 'string', 'max:255'],
            'entregado_por' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'serial_ids' => ['nullable', 'array'],
            'serial_ids.*' => ['integer', 'distinct', 'exists:producto_seriales,id'],
            'foto' => ['nullable', 'image', 'max:10240'],
            'firma' => ['nullable', 'string', 'max:5000000'],
        ]);

        $guia = DB::transaction(function () use ($request, $data) {
            $data['created_by'] = $request->user()->id;
            $data['estado'] = 'entregado';
            $data['foto_path'] = $request->file('foto')?->store('guias/entregas', 'public');
            $data['firma_path'] = $this->guardarFirma($data['firma'] ?? null);
            unset($data['serial_ids'], $data['foto'], $data['firma']);

            $guia = GuiaEntrega::create($data);
            $guia->seriales()->sync($request->input('serial_ids', []));

            return $guia;
        });

        return redirect()->route('admin.guia_entregas.show', $guia)->with('status', 'Entrega de guía registrada correctamente.');
    }

    public function show(GuiaEntrega $guiaEntrega): View
    {
        $guiaEntrega->load(['creador', 'seriales.producto']);

        return view('admin.guia_entregas.show', ['guia' => $guiaEntrega]);
    }

    private function guardarFirma(?string $dataUrl): ?string
    {
        if (! $dataUrl || ! preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $dataUrl, $match)) {
            return null;
        }

        $extension = strtolower($match[1]) === 'jpeg' ? 'jpg' : strtolower($match[1]);
        $path = 'guias/firmas/'.str()->uuid().'.'.$extension;
        Storage::disk('public')->put($path, base64_decode($match[2], true));

        return $path;
    }
}
