<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('avatar_url');
            $table->string('custom_domain')->nullable()->unique()->after('is_active');
            $table->timestamp('custom_domain_connected_at')->nullable()->after('custom_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropUnique(['custom_domain']);
            $table->dropColumn([
                'avatar_path',
                'custom_domain',
                'custom_domain_connected_at',
            ]);
        });
    }
};
