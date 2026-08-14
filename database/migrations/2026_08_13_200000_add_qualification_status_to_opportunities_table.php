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
            $table->text('qualification_notes')
                ->nullable()
                ->after('ai_recommendations');
            $table->string('qualification_status')
                ->default('pending')
                ->after('qualification_notes');
            $table->text('qualification_last_error')
                ->nullable()
                ->after('qualification_status');
            $table->timestamp('qualified_at')
                ->nullable()
                ->after('qualification_last_error');
            $table->json('ai_insights')
                ->nullable()
                ->after('qualified_at');

            $table->index('qualification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex(['qualification_status']);
            $table->dropColumn([
                'qualification_notes',
                'qualification_status',
                'qualification_last_error',
                'qualified_at',
                'ai_insights',
            ]);
        });
    }
};
