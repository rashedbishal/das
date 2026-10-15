<?php

namespace App\Services;

use App\Models\Tag;
use App\Support\SlugGenerator;
use Illuminate\Validation\ValidationException;

class TagService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Tag
    {
        $data['slug'] = SlugGenerator::unique('tags', $data['name']);

        return Tag::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Tag $tag, array $data): Tag
    {
        $data['slug'] = isset($data['name']) ? SlugGenerator::unique('tags', $data['name'], $tag->id) : $tag->slug;

        $tag->update($data);

        return $tag;
    }

    public function delete(Tag $tag): void
    {
        if ($tag->products()->exists()) {
            throw ValidationException::withMessages(['tag' => 'This tag is still assigned to products and cannot be deleted.']);
        }

        $tag->delete();
    }
}
