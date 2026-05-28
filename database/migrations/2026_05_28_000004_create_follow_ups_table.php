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
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('due_at');
            $table->string('priority')->default('medium');
            $table->text('notes')->nullable();
            $table->string('reminder_status')->default('pending');
            $table->dateTime('snoozed_until')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index('client_id');
            $table->index('opportunity_id');
            $table->index('due_at');
            $table->index('reminder_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
