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
        if (Schema::hasTable('producto_seriales')) {
            return;
        }

        Schema::create('producto_seriales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            // codigo, condicion y estado las agregaba 2026_08_29_000009, que
            // corre antes que esta tabla exista: en una base nueva nacen
            // ya incluidas aquí.
            $table->string('codigo', 30)->nullable()->unique();
            $table->string('no_serie')->nullable();
            $table->enum('condicion', ['nuevo', 'usado'])->default('nuevo');
            $table->string('estado', 20)->default('disponible');
            $table->boolean('vendido')->default(false);
            $table->timestamp('vendido_en')->nullable();
            $table->foreignId('venta_item_id')->nullable()->constrained('venta_items')->nullOnDelete();
            $table->timestamps();
            $table->index('estado');
        });

        $this->migrarDatosExistentes();

        // pieza_procesos ahora se crea en 2026_08_31_000011 con su FK ya
        // declarada, después de que esta tabla existe. No hace falta
        // agregarla condicionalmente aquí.

        // Mismo criterio que 2026_08_29_000009 para las piezas migradas.
        \Illuminate\Support\Facades\DB::table('producto_seriales')->whereNull('codigo')->orderBy('id')->each(function ($fila) {
            \Illuminate\Support\Facades\DB::table('producto_seriales')->where('id', $fila->id)->update([
                'codigo' => 'MB-'.str_pad((string) $fila->id, 6, '0', STR_PAD_LEFT),
                'estado' => $fila->vendido ? 'vendido' : 'disponible',
            ]);
        });
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
