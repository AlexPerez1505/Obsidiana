<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Producto;
use App\Models\Subtype;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Common\EccLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EquipoController extends Controller
{
    public function index(Request $request): View
    {
        $query = Producto::query()->with('registradoPor')->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('tipo_equipo', 'like', "%{$search}%")
                    ->orWhere('marca', 'like', "%{$search}%")
                    ->orWhere('modelo', 'like', "%{$search}%")
                    ->orWhere('no_serie', 'like', "%{$search}%");
            });
        }

        if ($estado = $request->get('estado')) {
            $query->where('estado', $estado);
        }

        $equipos = $query->paginate(10)->withQueryString();

        return view('structure.gestion_Inventario.equipos.menu_equipos', [
            'equipos' => $equipos,
            'estados' => $this->estados(),
            'filters' => $request->only('search', 'estado'),
        ]);
    }

    public function create(): View
    {
        return view('structure.gestion_Inventario.equipos.c_equipos', [
            'tipos' => EquipmentType::with('subtypes')->orderBy('name')->get(),
            'marcas' => Brand::with('equipmentModels')->orderBy('name')->get(),
        ]);
    }

    public function nextBaseSerial(Request $request): JsonResponse
    {
        $data = $request->only(['tipo_equipo', 'subtipo', 'marca', 'modelo']);
        $excludeId = $request->get('exclude_id') ? (int) $request->get('exclude_id') : null;

        return response()->json([
            'no_serie_base' => $this->generateBaseSerial($data, $excludeId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }

        $data['user_id'] = Auth::id();
        $data['precio'] = $data['precio'] ?? 0;
        $data['stock'] = $data['stock'] ?? 0;
        $data['no_serie_base'] = $this->generateBaseSerial($data);

        if ($request->filled('firma')) {
            $data['firma_path'] = $this->storeFirma($request->input('firma'));
        }

        Producto::create($data);

        return redirect()->route('inventory.equipos.index')->with('status', 'Equipo registrado correctamente.');
    }

    public function show(Producto $equipo): View
    {
        return view('structure.gestion_Inventario.equipos.detalle_equipo', [
            'equipo' => $equipo,
        ]);
    }

    public function edit(Producto $equipo): View
    {
        return view('structure.gestion_Inventario.equipos.c_equipos', [
            'mode' => 'edit',
            'equipo' => $equipo,
            'tipos' => EquipmentType::with('subtypes')->orderBy('name')->get(),
            'marcas' => Brand::with('equipmentModels')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Producto $equipo): RedirectResponse
    {
        $data = $this->validated($request, $equipo);

        if ($request->hasFile('imagen')) {
            if ($equipo->imagen_path) {
                Storage::disk('public')->delete($equipo->imagen_path);
            }
            $data['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }

        $data['precio'] = $data['precio'] ?? $equipo->precio;
        $data['stock'] = $data['stock'] ?? $equipo->stock;

        if ($this->shouldRegenerateBaseSerial($data, $equipo)) {
            $data['no_serie_base'] = $this->generateBaseSerial($data, $equipo->id);
        }

        if ($request->filled('firma')) {
            if ($equipo->firma_path) {
                Storage::disk('public')->delete($equipo->firma_path);
            }
            $data['firma_path'] = $this->storeFirma($request->input('firma'));
        }

        $equipo->update($data);

        return redirect()->route('inventory.equipos.index')->with('status', 'Equipo actualizado correctamente.');
    }

    public function destroy(Producto $equipo): RedirectResponse
    {
        if ($equipo->imagen_path) {
            Storage::disk('public')->delete($equipo->imagen_path);
        }

        $equipo->delete();

        return redirect()->route('inventory.equipos.index')->with('status', 'Equipo eliminado correctamente.');
    }

    private function validated(Request $request, ?Producto $equipo = null): array
    {
        return $request->validate([
            'tipo_equipo' => ['required', 'string', 'max:255'],
            'subtipo' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'no_serie' => ['required', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:255'],
            'fecha_adquisicion' => ['nullable', 'date'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'imagen' => ['nullable', 'image', 'max:5120'],
            'precio' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'firma' => $equipo ? ['nullable', 'string'] : ['required', 'string'],
        ]);
    }

    private function estados(): array
    {
        return Producto::select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->orderBy('estado')
            ->pluck('estado')
            ->all();
    }

    private function shouldRegenerateBaseSerial(array $data, Producto $equipo): bool
    {
        if (empty($equipo->no_serie_base)) {
            return true;
        }

        $keys = ['tipo_equipo', 'subtipo', 'marca', 'modelo'];
        foreach ($keys as $key) {
            if (($data[$key] ?? null) !== $equipo->{$key}) {
                return true;
            }
        }

        return false;
    }

    private function generateBaseSerial(array $data, ?int $excludeId = null): string
    {
        $prefix = implode('-', [
            $this->abbreviate($data['tipo_equipo'] ?? ''),
            $this->abbreviate($data['subtipo'] ?? ''),
            $this->abbreviate($data['marca'] ?? ''),
            $this->abbreviate($data['modelo'] ?? ''),
        ]);

        $query = Producto::where('tipo_equipo', $data['tipo_equipo'] ?? '')
            ->where('subtipo', $data['subtipo'] ?? null)
            ->where('marca', $data['marca'] ?? null)
            ->where('modelo', $data['modelo'] ?? null);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $count = $query->count();

        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function abbreviate(string $text, int $length = 2): string
    {
        $text = Str::upper(Str::slug($text, ' '));
        $words = array_values(array_filter(explode(' ', $text)));

        if (count($words) >= 2) {
            $initials = implode('', array_map(fn ($word) => $word[0] ?? '', $words));

            return Str::substr($initials, 0, $length);
        }

        return Str::substr(Str::slug($text, ''), 0, $length);
    }

    private function storeFirma(string $base64): string
    {
        $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $image = base64_decode($base64);

        $name = 'firmas/' . Auth::id() . '-' . time() . '-' . Str::random(8) . '.png';
        Storage::disk('public')->makeDirectory('firmas');
        Storage::disk('public')->put($name, $image);

        return $name;
    }

    public function qrImage(Producto $equipo): Response
    {
        $url = route('inventory.equipos.public', $equipo);

        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64'    => false,
            'scale'           => 8,
            'eccLevel'        => EccLevel::M,
        ]);

        $png = (new QRCode($options))->render($url);

        return response($png, 200)->header('Content-Type', 'image/png');
    }

    public function publicShow(Producto $equipo): View
    {
        return view('structure.gestion_Inventario.equipos.public_show', [
            'equipo' => $equipo,
        ]);
    }
}
