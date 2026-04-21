<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('class_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('class_room_id')->constrained('class_rooms')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->string('academic_year');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Ensure a teacher can only be homeroom for one class per academic year
            $table->unique(['teacher_id', 'academic_year'], 'unique_teacher_homeroom_per_year');
            
            // Ensure a class only has one homeroom teacher per academic year
            $table->unique(['class_room_id', 'section_id', 'academic_year'], 'unique_class_homeroom_per_year');
            
            // Indexes for faster queries
            $table->index('teacher_id');
            $table->index('class_room_id');
            $table->index('section_id');
            $table->index('academic_year');
        });
    }

    public function down()
    {
        Schema::dropIfExists('class_teachers');
    }
};