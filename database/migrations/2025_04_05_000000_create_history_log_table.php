<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('history_log', function (Blueprint $table) {
            $table->id(); // id INT AUTO_INCREMENT PRIMARY KEY
            $table->integer('table_id');
            $table->integer('record_id');
            $table->string('changes', 1000);
            $table->dateTime('changed')->nullable();
            $table->integer('changed_by');
            $table->integer('changes_id')->default(0);
            $table->integer('class_id')->default(0);
            $table->boolean('is_first')->default(0);

            // Indexes
            $table->index(['table_id', 'record_id']);
            $table->index(['record_id', 'table_id']);
            $table->index('class_id');
            $table->index(['changed_by', 'changed']);
            $table->index('changed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_log');
    }
};
