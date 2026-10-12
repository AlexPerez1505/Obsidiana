<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRunLine extends Model
{
    protected $fillable = ['payroll_run_id', 'user_id', 'user_name', 'employee_number', 'sueldo', 'bonos', 'descuentos', 'neto', 'snapshot'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'sueldo' => 'float', 'bonos' => 'float', 'descuentos' => 'float', 'neto' => 'float'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }
}
