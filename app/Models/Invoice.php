<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['daftra_id', 'client_id', 'invoice_number', 'total', 'paid', 'remaining', 'status', 'currency', 'invoice_date', 'due_date', 'pdf_url', 'last_synced_at'])]
#[Hidden(['pdf_url'])]
class Invoice extends Model
{
    public const STATUS_PAID = 'paid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_OVERPAID = 'overpaid';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
            'remaining' => 'decimal:2',
            'invoice_date' => 'date',
            'due_date' => 'date',
            'last_synced_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public static function statusFromAmounts(float $total, float $paid, float $remaining): string
    {
        if ($remaining < 0 || ($total > 0 && $paid > $total)) {
            return self::STATUS_OVERPAID;
        }

        if ($remaining <= 0) {
            return self::STATUS_PAID;
        }

        return $paid > 0 ? self::STATUS_PARTIAL : self::STATUS_UNPAID;
    }
}
