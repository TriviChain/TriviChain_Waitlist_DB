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
        Schema::table('waitlists', function (Blueprint $table) {
            $table->boolean('welcome_email_sent')->default(false);
            $table->timestamp('welcome_email_sent_at')->nullable();
            $table->integer('updates_received')->default(0);
            $table->timestamp('last_update_received_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waitlists', function (Blueprint $table) {
            $table->dropColumn([
                'welcome_email_sent',
                'welcome_email_sent_at',
                'updates_received',
                'last_update_received_at'
            ]);
        });
    }
};
