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
            $table->string('custom_domain_dns_status')->nullable()->after('custom_domain_connected_at');
            $table->string('custom_domain_dns_target')->nullable()->after('custom_domain_dns_status');
            $table->timestamp('custom_domain_dns_checked_at')->nullable()->after('custom_domain_dns_target');
            $table->string('custom_domain_dns_message')->nullable()->after('custom_domain_dns_checked_at');
            $table->string('custom_domain_ssl_status')->nullable()->after('custom_domain_dns_message');
            $table->string('custom_domain_ssl_issuer')->nullable()->after('custom_domain_ssl_status');
            $table->timestamp('custom_domain_ssl_expires_at')->nullable()->after('custom_domain_ssl_issuer');
            $table->timestamp('custom_domain_ssl_checked_at')->nullable()->after('custom_domain_ssl_expires_at');
            $table->string('custom_domain_ssl_message')->nullable()->after('custom_domain_ssl_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn([
                'custom_domain_dns_status',
                'custom_domain_dns_target',
                'custom_domain_dns_checked_at',
                'custom_domain_dns_message',
                'custom_domain_ssl_status',
                'custom_domain_ssl_issuer',
                'custom_domain_ssl_expires_at',
                'custom_domain_ssl_checked_at',
                'custom_domain_ssl_message',
            ]);
        });
    }
};
