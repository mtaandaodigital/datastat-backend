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
        Schema::create('sent_email_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('registrant_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->text('extra_note')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_html')->nullable();
            $table->timestamps();

            // Optional foreign keys - keep them nullable to avoid issues on legacy DBs
            // Note: Not enforcing foreign key constraints to avoid migration fragility on existing DBs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_email_logs');
    }
};
