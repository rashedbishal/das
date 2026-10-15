<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Full product detail is never available to guests — only to any
     * authenticated user (customer or admin) once the product is published,
     * or to admins regardless of status.
     */
    public function view(?User $user, Product $product): bool
    {
        if ($user === null) {
            return false;
        }

        return $product->status === 'published' || $user->isAdmin();
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
