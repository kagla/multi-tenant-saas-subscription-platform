<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->index('plan');
            $table->index('is_active');
            $table->index('custom_domain');
        });

        Schema::table('usage_records', function (Blueprint $table) {
            $table->index(['tenant_id', 'metric', 'recorded_at']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index('stripe_status');
            $table->index('stripe_id');
        });

        Schema::table('invitations', function (Blueprint $table) {
            $table->index(['tenant_id', 'email']);
            $table->index('expires_at');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['plan']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['custom_domain']);
        });

        Schema::table('usage_records', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'metric', 'recorded_at']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['stripe_status']);
            $table->dropIndex(['stripe_id']);
        });

        Schema::table('invitations', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'email']);
            $table->dropIndex(['expires_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['action']);
            $table->dropIndex(['created_at']);
        });
    }
};
