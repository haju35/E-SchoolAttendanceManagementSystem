<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->boolean('is_class_teacher')->default(false);
            $table->foreignId('assigned_class_id')->nullable()->constrained('class_rooms');
            $table->foreignId('assigned_section_id')->nullable()->constrained('sections');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropForeign(['assigned_class_id']);
            $table->dropForeign(['assigned_section_id']);
            $table->dropColumn(['is_class_teacher', 'assigned_class_id', 'assigned_section_id']);
        });
    }
};