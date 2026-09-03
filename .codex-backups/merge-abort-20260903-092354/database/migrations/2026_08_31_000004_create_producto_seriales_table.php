<?php

use App\Models\Producto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada unidad física de un producto pasa a tener su propia fila con su
     * propio número de serie, en vez de vivir concatenada en un solo texto.
     * Esto permite saber exactamente qué serial salió en cada venta/factura.
     */
    public function up(): void
    {
        Schema::create('producto_seriales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('no_serie')->nullable();
            $table->boolean('vendido')->default(false);
            $table->timestamp('vendido_en')->nullable();
            $table->foreignId('venta_item_id')->nullable()->constrained('venta_items')->nullOnDelete();
            $table->timestamps();
        });

        $this->migrarDatosExistentes();
    }

    /**
     * Convierte el texto "SN001, SN002" de cada producto en filas
     * individuales, y completa con filas sin serial hasta llegar al stock
     * ya registrado (unidades que nunca tuvieron un serial capturado).
     */
    private function migrarDatosExistentes(): void
    {
        Producto::query()->select(['id', 'no_serie', 'stock'])->chunkById(100, function ($productos) {
            foreach ($productos as $producto) {
                $series = collect(explode(',', (string) $producto->no_serie))
                    ->map(fn ($s) => trim($s))
                    ->filter()
                    ->unique()
                    ->values();

                $filas = $series->map(fn ($serie) => [
                    'producto_id' => $producto->id,
                    'no_serie' => $serie,
                    'vendido' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                $faltantes = max(0, (int) $producto->stock - $series->count());

                for ($i = 0; $i < $faltantes; $i++) {
                    $filas[] = [
                        'producto_id' => $producto->id,
                        'no_serie' => null,
                        'vendido' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (! empty($filas)) {
                    \Illuminate\Support\Facades\DB::table('producto_seriales')->insert($filas);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_seriales');
    }
};
