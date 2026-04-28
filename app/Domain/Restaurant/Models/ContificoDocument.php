<?php

namespace App\Domain\Restaurant\Models;

use Illuminate\Database\Eloquent\Model;

class ContificoDocument extends Model
{
    protected $fillable = [
        'external_id',
        'tipo_registro',
        'tipo_documento',
        'documento',
        'estado',
        'anulado',
        'fecha_emision',
        'fecha_vencimiento',
        'total',
        'saldo',
        'servicio',
        'vendedor_id',
        'vendedor_nombre',
        'persona_nombre',
        'detalles',
        'cobros',
        'raw',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'anulado' => 'boolean',
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'total' => 'decimal:2',
            'saldo' => 'decimal:2',
            'servicio' => 'decimal:2',
            'detalles' => 'array',
            'cobros' => 'array',
            'raw' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
