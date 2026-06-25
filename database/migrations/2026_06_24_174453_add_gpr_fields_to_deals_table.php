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
        Schema::table('deals', function (Blueprint $table) {
            $table->string('service_type', 50)->nullable()->after('value');
            $table->decimal('area_m2', 10, 2)->nullable()->after('service_type');
            $table->date('scheduled_date')->nullable()->after('area_m2');
            $table->text('description')->nullable()->after('scheduled_date');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn(['service_type', 'area_m2', 'scheduled_date', 'description']);
        });
    }
};
