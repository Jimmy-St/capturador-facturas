<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_type',
        'folio',
        'rut',
        'supplier',
        'document_date',
        'reception_date',
        'amount',
        'fidelity',
        'tokens_cost',
        'is_reviewed',
        'image_path',
        'raw_response_json',
        'user_id',
    ];

    protected $casts = [
        'document_date' => 'date',
        'reception_date' => 'datetime',
        'amount' => 'decimal:2',
        'is_reviewed' => 'boolean',
        'tokens_cost' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}