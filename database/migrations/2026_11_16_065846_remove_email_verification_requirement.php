<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Drop the unused "require email verification" auth setting and mark any
     * customers it left unverified as verified, since no verification email
     * was ever sent and they would otherwise be stuck on the verify-email page.
     */
    public function up(): void
    {
        DB::table('settings')
            ->where('group', 'auth')
            ->where('key', 'require_email_verification')
            ->delete();

        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        //
    }
};
