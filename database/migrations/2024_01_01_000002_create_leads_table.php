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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('organization')->nullable();
            $table->string('interest')->nullable(); // Course or service of interest
            $table->enum('source', ['Landing Page', 'Website', 'Referral', 'Social Media', 'Email Campaign', 'Other'])->default('Website');
            $table->enum('status', ['New', 'Contacted', 'Qualified', 'Converted', 'Lost'])->default('New');
            $table->text('notes')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->decimal('potential_value', 10, 2)->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable(); // User ID
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('source');
            $table->index('email');
            $table->index('assigned_to');
            
            $table->foreign('assigned_to')->references('usermanagementid')->on('usermanagement')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};