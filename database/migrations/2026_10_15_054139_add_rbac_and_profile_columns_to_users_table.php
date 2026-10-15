<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->unique()->after('email');
            $table->enum('user_type', ['admin', 'customer'])->default('customer')->after('phone');
            $table->foreignId('role_id')->nullable()->after('user_type')->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('role_id');
            $table->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();

            $table->index('user_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('role_id');
            $table->dropIndex(['user_type']);
            $table->dropColumn(['phone', 'user_type', 'is_active']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
