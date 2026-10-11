<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An order request from a client. The portal never writes to Daftra:
 * the admin creates the invoice manually in Daftra, then links the synced invoice here.
 *
 * Flow: draft (client's cart) → submitted → processing → completed (invoice linked) | cancelled
 */
#[Fillable(['client_id', 'user_id', 'status', 'notes', 'admin_notes', 'invoice_id', 'estimated_total', 'submitted_at', 'completed_at'])]
class Order extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_total' => 'decimal:2',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function number(): string
    {
        return 'ORD-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeCancelledByClient(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function recalculateTotal(): void
    {
        $this->forceFill([
            'estimated_total' => $this->items()->selectRaw('COALESCE(SUM(price * quantity), 0) as total')->value('total'),
        ])->save();
    }
}
