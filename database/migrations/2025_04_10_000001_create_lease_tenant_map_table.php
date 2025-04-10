<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('lease_tenant_map')) {
            Schema::create('lease_tenant_map', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('lease_id');
                $table->unsignedBigInteger('user_id');
                $table->boolean('status')->default(1);
                $table->timestamps();

                $table->foreign('lease_id')->references('id')->on('lease')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void {
        Schema::dropIfExists('lease_tenant_map');
    }
};
