<?php

namespace App\Models;

use Database\Factories\HomepageSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $image
 * @property string|null $link_url
 * @property array<string, mixed>|null $content
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'title', 'subtitle', 'image', 'link_url', 'content', 'sort_order', 'is_active', 'starts_at', 'ends_at'])]
class HomepageSection extends Model
{
    /** @use HasFactory<HomepageSectionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<HomepageSection>  $query
     * @return Builder<HomepageSection>
     */
    public function scopeVisible(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
