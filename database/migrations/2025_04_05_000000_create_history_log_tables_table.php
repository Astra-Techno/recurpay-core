<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('history_log_tables', function (Blueprint $table) {
            $table->id(); // id INT AUTO_INCREMENT PRIMARY KEY
            $table->string('name', 250)->unique();
            $table->boolean('enabled')->default(1);
            $table->boolean('new_table')->default(0);
            $table->string('primary_key', 30)->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_log_tables');
    }
};