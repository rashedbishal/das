<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The activity log moved from the app's own admin_activity_logs table to
 * spatie/laravel-activitylog's activity_log. This keeps the IP address and browser
 * columns the old table had, copies the existing entries across, and drops the old
 * table.
 */
return new class extends Migration
{
    /** Old action prefix => activity log name (category). */
    private const LOG_NAMES = [
        'product' => 'catalogue',
        'admin' => 'users',
        'role' => 'users',
    ];

    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('batch_uuid');
            $table->string('user_agent', 500)->nullable()->after('ip_address');
            $table->index('created_at');
        });

        if (! Schema::hasTable('admin_activity_logs')) {
            return;
        }

        $causerType = (new User)->getMorphClass();

        DB::table('admin_activity_logs')->orderBy('id')->chunk(500, function ($rows) use ($causerType) {
            DB::table('activity_log')->insert($rows->map(function (object $row) use ($causerType) {
                [$prefix, $event] = explode('.', (string) $row->action, 2) + [1 => (string) $row->action];

                return [
                    'log_name' => self::LOG_NAMES[$prefix] ?? 'catalogue',
                    'description' => $row->description ?? $row->action,
                    'subject_type' => $row->subject_type,
                    'subject_id' => $row->subject_id,
                    'event' => $event,
                    'causer_type' => $row->user_id !== null ? $causerType : null,
                    'causer_id' => $row->user_id,
                    'properties' => $row->properties,
                    'ip_address' => $row->ip_address,
                    'user_agent' => $row->user_agent !== null ? mb_substr((string) $row->user_agent, 0, 500) : null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->created_at,
                ];
            })->all());
        });

        Schema::drop('admin_activity_logs');
    }

    public function down(): void
    {
        Schema::create('admin_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropColumn(['ip_address', 'user_agent']);
        });
    }
};
