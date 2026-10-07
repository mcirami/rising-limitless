<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offer_routing_flows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 150);
            // Nullable for unassigned drafts; uniqueness prevents competing flows.
            $table->unsignedInteger('entry_offer_id')->nullable()->unique();
            $table->unsignedInteger('fallback_offer_id');
            $table->json('steps');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_routing_flows');
    }
};
