<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Published products are public (guests and search engines see the full page;
     * prices are hidden from guests by the resources). Drafts and archived products
     * are visible to admins only, as a preview.
     */
    public function view(?User $user, Product $product): bool
    {
        return $product->status === 'published' || ($user?->isAdmin() ?? false);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('products.view');
    }

    public function manage(User $user, Product $product): bool
    {
        return $user->hasPermission('products.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermission('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermission('products.delete');
    }

    public function publish(User $user, Product $product): bool
    {
        return $user->hasPermission('products.publish');
    }
}
