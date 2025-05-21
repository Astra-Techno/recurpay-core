<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('scheduled_emails', function (Blueprint $table) {
            $table->id();
            $table->string('to_email');
            $table->foreignId('template_id')->constrained('email_templates');
            $table->json('payload');
            $table->dateTime('send_at');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('scheduled_emails');
    }
};
