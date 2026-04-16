<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('class_room_id')->constrained('class_rooms');
            $table->foreignId('section_id')->constrained('sections');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late']);
            $table->foreignId('marked_by')->constrained('teachers');
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            // Prevent duplicate attendance for same student on same day
            $table->unique(['student_id', 'date'], 'unique_class_attendance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_attendances');
    }
};