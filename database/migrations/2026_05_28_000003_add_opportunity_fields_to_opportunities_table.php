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
        Schema::table('opportunities', function (Blueprint $table) {
            $table->decimal('estimated_value', 15, 2)->nullable()->after('stage');
            $table->string('status')->default('open')->after('estimated_value');
            $table->text('proposal_notes')->nullable()->after('status');
            $table->json('proposal_payload')->nullable()->after('proposal_notes');
            $table->json('ai_recommendations')->nullable()->after('proposal_payload');

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn([
                'estimated_value',
                'status',
                'proposal_notes',
                'proposal_payload',
                'ai_recommendations',
            ]);
        });
    }
};
