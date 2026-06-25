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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('company', 255)->nullable()->after('name');
            $table->string('city', 100)->nullable()->after('company');
            $table->char('state', 2)->nullable()->after('city');
            $table->string('segment', 50)->nullable()->after('state');
            $table->string('source', 50)->nullable()->after('segment');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['company', 'city', 'state', 'segment', 'source']);
        });
    }
};
