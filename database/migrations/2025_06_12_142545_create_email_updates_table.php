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
        Schema::create('email_updates', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('message');
            $table->text('static_content')->nullable();
            $table->integer('recipients_count')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->enum('status', ['draft', 'sending', 'completed', 'failed'])->default('draft');
            $table->foreignId('sent_by')->constrained('admin_users');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_updates');
    }
};
