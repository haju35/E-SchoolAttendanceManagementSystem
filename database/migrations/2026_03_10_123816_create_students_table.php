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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');;
            $table->foreignId('family_id')->nullable()->constrained('families')->onDelete('set null');;
            $table->string('admission_number', 50)->unique();
            $table->string('roll_number', 20)->nullable();
            $table->date('date_of_birth');
            $table->string('gender', 10);
            $table->foreignId('current_class_id')->nullable()->constrained('class_rooms');
            $table->foreignId('current_section_id')->nullable()->constrained('sections');
            $table->date('admission_date');
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
