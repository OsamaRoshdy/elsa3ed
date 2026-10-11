<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A label such as "Original" or "Korea aftermarket". A product can have several tags.
 */
#[Fillable(['name', 'color'])]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    /**
     * Badge colours (key => Tailwind classes). Written out in full so Tailwind picks them up.
     */
    public const COLORS = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
    ];

    public const DEFAULT_COLOR = 'slate';

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function colorClasses(): string
    {
        return self::COLORS[$this->color] ?? self::COLORS[self::DEFAULT_COLOR];
    }
}
