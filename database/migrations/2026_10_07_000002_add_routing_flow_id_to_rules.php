<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rule', function (Blueprint $table) {
            $table->unsignedBigInteger('routing_flow_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('rule', function (Blueprint $table) {
            $table->dropIndex(['routing_flow_id']);
            $table->dropColumn('routing_flow_id');
        });
    }
};
