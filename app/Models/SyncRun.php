<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['status', 'triggered_by', 'products_count', 'clients_count', 'invoices_count', 'payments_count', 'error', 'started_at', 'finished_at'])]
class SyncRun extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public static function lastSuccessful(): ?self
    {
        return static::where('status', self::STATUS_SUCCESS)->latest('finished_at')->first();
    }
}
