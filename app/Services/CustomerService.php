<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Customer account administration.
 */
class CustomerService
{
    /**
     * Permanently delete a customer account. Their login sessions and any
     * pending password-reset token go with it, so they are signed out at once;
     * a pending e-mail verification code is removed by its foreign key. Products
     * and stock records only point at admins, and the activity log keeps its
     * history (the deletion itself is logged by the model).
     */
    public function delete(User $customer): void
    {
        if (! $customer->isCustomer()) {
            throw new InvalidArgumentException('Only customer accounts can be deleted here.');
        }

        DB::transaction(function () use ($customer): void {
            DB::table('sessions')->where('user_id', $customer->id)->delete();

            if ($customer->email !== null) {
                DB::table('password_reset_tokens')->where('email', $customer->email)->delete();
            }

            $customer->delete();
        });
    }
}
