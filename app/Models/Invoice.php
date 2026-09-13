<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'folio',
        'rut_emisor',
        'rut_receptor',
        'invoice_date',
        'reception_date',
        'amount',
        'status',
        'image_path',
        'aws_response_json',
        'user_id',
    ];

    /**
     * Relación: Una factura fue registrada por un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}