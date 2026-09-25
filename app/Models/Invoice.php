<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'payment_status',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('payment_date', 'asc')->orderBy('id', 'asc');
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function remainingAmount(): float
    {
        return max(0.0, (float) $this->amount - $this->totalPaid());
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'pagado';
    }

    public function isDebt(): bool
    {
        return $this->payment_status === 'adeudado';
    }
}
