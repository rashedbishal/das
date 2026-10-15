<?php

namespace App\Models;

use Database\Factories\ProductMediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $product_id
 * @property string $type
 * @property string $source
 * @property string|null $disk
 * @property string|null $path
 * @property string|null $url
 * @property bool $is_main
 * @property string|null $alt_text
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['product_id', 'type', 'source', 'disk', 'path', 'url', 'is_main', 'alt_text', 'sort_order'])]
class ProductMedia extends Model
{
    /** @use HasFactory<ProductMediaFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function resolvedUrl(): ?string
    {
        if ($this->source === 'url') {
            return $this->url;
        }

        if ($this->disk && $this->path) {
            return Storage::disk($this->disk)->url($this->path);
        }

        return null;
    }
}
