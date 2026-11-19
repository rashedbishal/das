<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the Mother Admin account, or replaces the existing one's name, email
 * and password. The existing row is overwritten in place (rather than deleted)
 * so records it created or updated keep pointing at the Mother Admin.
 */
#[Signature('das:reset-mother-admin {--force : Replace an existing Mother Admin without asking for confirmation}')]
#[Description('Create the Mother Admin account, replacing the existing one if there is one')]
class ResetMotherAdmin extends Command
{
    public function handle(): int
    {
        $motherRole = Role::query()->where('slug', Role::MOTHER_ADMIN)->first();

        if (! $motherRole) {
            (new RolePermissionSeeder)->run();
            $motherRole = Role::query()->where('slug', Role::MOTHER_ADMIN)->firstOrFail();
        }

        $existing = User::query()->where('role_id', $motherRole->id)->first();

        if ($existing) {
            $this->components->warn("A Mother Admin account already exists ({$existing->email}). It will be replaced.");

            if (! $this->option('force') && ! $this->confirm('Replace the existing Mother Admin account?')) {
                $this->components->info('Nothing changed.');

                return self::FAILURE;
            }
        }

        $name = config('das.mother_admin.name') ?: $this->ask('Mother Admin name');
        $email = config('das.mother_admin.email') ?: $this->ask('Mother Admin email');
        $password = config('das.mother_admin.password') ?: $this->secret('Mother Admin password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($existing?->id)],
                'password' => ['required', 'string', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $admin = DB::transaction(function () use ($existing, $motherRole, $name, $email, $password): User {
            $admin = $existing ?? new User;

            $admin->fill([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'user_type' => 'admin',
                'role_id' => $motherRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            if ($existing) {
                $admin->forceFill([
                    'phone' => null,
                    'google_id' => null,
                ]);
                $admin->setRememberToken(Str::random(60));
            }

            $admin->save();

            if ($existing) {
                DB::table(config('session.table', 'sessions'))->where('user_id', $admin->id)->delete();
            }

            return $admin;
        });

        $this->components->info($existing
            ? "Mother Admin account replaced; it now signs in as {$admin->email}."
            : "Mother Admin account created for {$admin->email}.");

        return self::SUCCESS;
    }
}
