<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->timestamp('followup_1_sent_at')->nullable()->after('loss_reason');
            $table->timestamp('followup_2_sent_at')->nullable()->after('followup_1_sent_at');
            $table->timestamp('followup_3_sent_at')->nullable()->after('followup_2_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn(['followup_1_sent_at', 'followup_2_sent_at', 'followup_3_sent_at']);
        });
    }
};
