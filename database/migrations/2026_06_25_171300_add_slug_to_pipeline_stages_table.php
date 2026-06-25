<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->string('slug', 50)->nullable()->after('name');
        });

        DB::table('pipeline_stages')->get()->each(function ($stage) {
            DB::table('pipeline_stages')
                ->where('id', $stage->id)
                ->update(['slug' => str()->slug($stage->name, '_')]);
        });

        DB::statement('ALTER TABLE pipeline_stages ALTER COLUMN slug SET NOT NULL');
        DB::statement('ALTER TABLE pipeline_stages ADD CONSTRAINT pipeline_stages_slug_unique UNIQUE (slug)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
