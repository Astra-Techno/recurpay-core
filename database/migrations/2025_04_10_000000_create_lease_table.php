<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('lease')) {
            Schema::create('lease', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id');
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['active', 'vacated', 'terminated'])->default('active');
                $table->timestamps();
            });
        }
    }

    public function down(): void {
        Schema::dropIfExists('lease');
    }
};
