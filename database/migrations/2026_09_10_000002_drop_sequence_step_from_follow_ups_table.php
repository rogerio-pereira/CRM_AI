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
        if (! Schema::hasColumn('follow_ups', 'sequence_step')) {
            return;
        }

        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropColumn('sequence_step');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('follow_ups', 'sequence_step')) {
            return;
        }

        Schema::table('follow_ups', function (Blueprint $table) {
            $table->unsignedTinyInteger('sequence_step')
                ->nullable()
                ->after('opportunity_id');
        });
    }
};
