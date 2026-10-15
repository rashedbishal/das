<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('das:bootstrap-mother-admin')]
#[Description('Create the initial Mother Admin account for the DAS CMS')]
class BootstrapMotherAdmin extends Command
{
    public function handle(): int
    {
        $motherRole = Role::query()->where('slug', Role::MOTHER_ADMIN)->first();

        if (! $motherRole) {
            (new RolePermissionSeeder)->run();
            $motherRole = Role::query()->where('slug', Role::MOTHER_ADMIN)->firstOrFail();
        }

        if (User::query()->where('role_id', $motherRole->id)->exists()) {
            $this->components->error('A Mother Admin account already exists. This command refuses to create a second one.');

            return self::FAILURE;
        }

        $name = config('das.mother_admin.name') ?: $this->ask('Mother Admin name');
        $email = config('das.mother_admin.email') ?: $this->ask('Mother Admin email');
        $password = config('das.mother_admin.password') ?: $this->secret('Mother Admin password');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $admin = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'user_type' => 'admin',
            'role_id' => $motherRole->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->components->info("Mother Admin account created for {$admin->email}.");

        return self::SUCCESS;
    }
}
