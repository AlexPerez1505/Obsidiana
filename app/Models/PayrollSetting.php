<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Ajustes sueltos de la nómina (clave → valor). */
class PayrollSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    public static function obtener(string $key, $default = null)
    {
        return static::find($key)?->value ?? $default;
    }

    public static function guardar(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }

    /** Días en que se divide el sueldo de la quincena para sacar "un día de sueldo". */
    public static function diasSueldo(): int
    {
        return max(1, (int) static::obtener('dias_sueldo', 15));
    }
}
