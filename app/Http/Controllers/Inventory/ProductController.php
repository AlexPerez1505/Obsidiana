<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::latest()
            ->get()
            ->map(fn (Product $product): array => $product->toTableRow());

        return view('structure.gestion_Inventario.equipos.menu_productos', [
            'products' => $products,
        ]);
    }

    public function create(): View
    {
        return view('structure.gestion_Inventario.equipos.c_productos', [
            'selectOptions' => $this->productOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($this->validatedData($request));

        return redirect()
            ->route('inventory.productos.index')
            ->with('status', 'Producto registrado correctamente.');
    }

    public function show(Product $producto): View
    {
        return view('structure.gestion_Inventario.equipos.detalle_producto', [
            'product' => $producto->toFormData(),
        ]);
    }

    public function edit(Product $producto): View
    {
        return view('structure.gestion_Inventario.equipos.c_productos', [
            'mode' => 'edit',
            'product' => $producto->toFormData(),
            'selectOptions' => $this->productOptions(),
        ]);
    }

    public function update(Request $request, Product $producto): RedirectResponse
    {
        $producto->update($this->validatedData($request, $producto));

        return redirect()
            ->route('inventory.productos.index')
            ->with('status', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $producto): RedirectResponse
    {
        $producto->delete();

        return redirect()
            ->route('inventory.productos.index')
            ->with('status', 'Producto eliminado correctamente.');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        if ($request->filled('serial_number')) {
            $request->merge([
                'serial_number' => strtoupper(trim((string) $request->input('serial_number'))),
            ]);
        }

        $serialNumberRule = Rule::unique('products', 'serial_number');

        if ($product) {
            $serialNumberRule->ignore($product->id);
        }

        $data = $request->validate([
            'serial_number' => ['nullable', 'string', 'max:100', $serialNumberRule],
            'name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'subtype' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:30'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'stock_current' => ['nullable', 'integer', 'min:0'],
            'warehouse' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'technical_category' => ['nullable', 'string', 'max:100'],
            'specifications' => ['nullable', 'string'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'supplier_code' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([
                Product::STATUS_ACTIVE,
                Product::STATUS_MAINTENANCE,
                Product::STATUS_INACTIVE,
            ])],
            'thumb' => ['nullable', 'string', 'max:40'],
            'product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $data['code'] = $product?->code ?? Product::nextCode();
        $data['serial_number'] = ($data['serial_number'] ?? null) ?: ($product?->serial_number ?? $data['code']);
        $data['category'] = $this->resolveOption($data['category'] ?? null, ProductOption::TYPE_CATEGORY);
        $data['subtype'] = $this->resolveOption($data['subtype'] ?? null, ProductOption::TYPE_SUBTYPE);
        $data['brand'] = $this->resolveOption($data['brand'] ?? null, ProductOption::TYPE_BRAND);
        $data['model'] = $this->resolveOption($data['model'] ?? null, ProductOption::TYPE_MODEL);
        $data['unit'] = $this->resolveOption($data['unit'] ?? null, ProductOption::TYPE_UNIT) ?: ($product?->unit ?? 'Pza');
        $this->resolveOptionRelation(
            ProductOption::TYPE_CATEGORY,
            $data['category'] ?? null,
            ProductOption::TYPE_SUBTYPE,
            $data['subtype'] ?? null
        );
        $data['name'] = trim((string) ($data['name'] ?? '')) ?: $this->generatedProductName($data);
        $data['price'] = (float) ($data['price'] ?? 0);
        $data['status'] = $data['status'] ?? $product?->status ?? Product::STATUS_ACTIVE;
        $data['thumb'] = $data['thumb'] ?? $product?->thumb ?? 'scope';
        $data['stock_current'] = array_key_exists('stock_current', $data)
            ? (int) $data['stock_current']
            : (int) ($product?->stock_current ?? 0);

        if ($request->hasFile('product_image')) {
            $data['image_path'] = $this->storeProductImage($request->file('product_image'), $product);
        }

        unset($data['product_image']);

        return $data;
    }

    private function storeProductImage(UploadedFile $file, ?Product $product = null): string
    {
        $directory = public_path('uploads/products');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = $file->getClientOriginalExtension() ?: $file->extension();
        $filename = (string) Str::uuid() . '.' . strtolower($extension);

        $file->move($directory, $filename);

        if ($product?->image_path) {
            File::delete(public_path($product->image_path));
        }

        return 'uploads/products/' . $filename;
    }

    private function productOptions(): array
    {
        return [
            'categories' => $this->optionsFor(ProductOption::TYPE_CATEGORY, 'category'),
            'subtypes' => $this->optionsFor(ProductOption::TYPE_SUBTYPE, 'subtype'),
            'subtypesByCategory' => $this->relatedOptions(
                ProductOption::TYPE_CATEGORY,
                ProductOption::TYPE_SUBTYPE
            ),
            'brands' => $this->optionsFor(ProductOption::TYPE_BRAND, 'brand'),
            'models' => $this->optionsFor(ProductOption::TYPE_MODEL, 'model'),
        ];
    }

    private function optionsFor(string $type, string $productColumn): array
    {
        return ProductOption::where('type', $type)
            ->pluck('value')
            ->merge(
                Product::query()
                    ->whereNotNull($productColumn)
                    ->where($productColumn, '<>', '')
                    ->distinct()
                    ->pluck($productColumn)
            )
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function resolveOption(?string $selected, string $type): ?string
    {
        $value = trim((string) $selected);

        if ($value === '') {
            return null;
        }

        ProductOption::firstOrCreate([
            'type' => $type,
            'value' => $value,
        ]);

        return $value;
    }

    private function relatedOptions(string $parentType, string $childType): array
    {
        return DB::table('product_option_relations')
            ->where('parent_type', $parentType)
            ->where('child_type', $childType)
            ->orderBy('parent_value')
            ->orderBy('child_value')
            ->get(['parent_value', 'child_value'])
            ->groupBy('parent_value')
            ->map(fn ($rows): array => $rows
                ->pluck('child_value')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all())
            ->all();
    }

    private function resolveOptionRelation(
        string $parentType,
        ?string $parentValue,
        string $childType,
        ?string $childValue
    ): void {
        $parent = trim((string) $parentValue);
        $child = trim((string) $childValue);

        if ($parent === '' || $child === '') {
            return;
        }

        DB::table('product_option_relations')->insertOrIgnore([
            'parent_type' => $parentType,
            'parent_value' => $parent,
            'child_type' => $childType,
            'child_value' => $child,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function generatedProductName(array $data): string
    {
        $parts = array_filter([
            $data['brand'] ?? null,
            $data['model'] ?? null,
            $data['subtype'] ?? null,
            $data['category'] ?? null,
        ]);

        return $parts ? implode(' ', $parts) : 'Producto ' . $data['code'];
    }
}
