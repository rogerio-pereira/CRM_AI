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
        Schema::table('clients', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['stage', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['stage', 'updated_at']);
        });
    }
};
