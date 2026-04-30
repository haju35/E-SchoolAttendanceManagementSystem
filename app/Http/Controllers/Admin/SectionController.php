<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SectionController extends Controller
{
    // ✅ Get all sections
    public function index(Request $request)
    {
        $sections = Section::with('classRoom')
            ->when($request->class_room_id, function($query, $classId) {
                $query->where('class_room_id', $classId);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }

    // ✅ Create SINGLE section
    public function store(Request $request)
    {
        $request->validate([
            'class_room_id' => 'required|exists:class_rooms,id',
            'name' => 'required|string|max:50',
            'capacity' => 'nullable|integer|min:1'
        ]);

        // Prevent duplicate section name in same class
        $exists = Section::where('class_room_id', $request->class_room_id)
            ->where(DB::raw('LOWER(name)'), strtolower($request->name))
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Section already exists in this class'
            ], 409);
        }

        $section = Section::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully',
            'data' => $section->load('classRoom')
        ], 201);
    }

    // ✅ Create MULTIPLE sections
    public function bulkStore(Request $request)
    {
        $request->validate([
            '*.class_room_id' => 'required|exists:class_rooms,id',
            '*.name' => 'required|string|max:50',
            '*.capacity' => 'nullable|integer|min:1'
        ]);

        $createdSections = [];
        $duplicates = [];

        foreach ($request->all() as $index => $data) {
            // Check for existing section in database
            $exists = Section::where('class_room_id', $data['class_room_id'])
                ->where(DB::raw('LOWER(name)'), strtolower($data['name']))
                ->exists();
            
            // Also check for duplicates within the same batch
            $batchDuplicate = false;
            for ($i = 0; $i < $index; $i++) {
                if (strtolower($request->all()[$i]['name']) === strtolower($data['name'])) {
                    $batchDuplicate = true;
                    break;
                }
            }

            if (!$exists && !$batchDuplicate) {
                $createdSections[] = Section::create($data);
            } else {
                $duplicates[] = $data['name'];
            }
        }

        $message = '';
        if (count($createdSections) > 0) {
            $message = count($createdSections) . ' section(s) created successfully';
        }
        if (count($duplicates) > 0) {
            $message .= (count($createdSections) > 0 ? '. ' : '') . 'Duplicate sections skipped: ' . implode(', ', $duplicates);
        }

        if (count($createdSections) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No new sections created. Duplicates found: ' . implode(', ', $duplicates)
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $createdSections
        ], 201);
    }

    // Show single section
    public function show($id)
    {
        $section = Section::with(['classRoom'])->find($id);
        
        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        // Get student count using the correct column name
        $studentCount = Student::where('current_section_id', $id)->count();
        $section->student_count = $studentCount;

        return response()->json([
            'success' => true,
            'data' => $section
        ]);
    }

    // Update section
    public function update(Request $request, $id)
    {
        $section = Section::find($id);
        
        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        $request->validate([
            'class_room_id' => 'sometimes|exists:class_rooms,id',
            'name' => 'sometimes|string|max:50',
            'capacity' => 'nullable|integer|min:1'
        ]);

        // Prevent duplicate on update
        if ($request->has('name')) {
            $classId = $request->class_room_id ?? $section->class_room_id;
            $exists = Section::where('class_room_id', $classId)
                ->where(DB::raw('LOWER(name)'), strtolower($request->name))
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section name already exists in this class'
                ], 409);
            }
        }

        $section->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully',
            'data' => $section->load('classRoom')
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $section = Section::find($id);

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found'
            ], 404);
        }

        // Check for attendance records
        $attendanceCount = DB::table('class_attendances')
            ->where('section_id', $id)
            ->count();
        
        // Check if there are students in this section
        $studentCount = Student::where('current_section_id', $id)->count();
        
        if ($studentCount > 0) {
            if (!$request->has('new_section_id')) {
                // Get available sections in the same class for the response
                $availableSections = Section::where('class_room_id', $section->class_room_id)
                    ->where('id', '!=', $id)
                    ->select('id', 'name', 'capacity')
                    ->get();
                
                return response()->json([
                    'success' => false,
                    'requires_transfer' => true,
                    'message' => "This section has {$studentCount} student(s). Please select another section to transfer them to.",
                    'student_count' => $studentCount,
                    'available_sections' => $availableSections
                ], 422);
            }
            
            $request->validate([
                'new_section_id' => 'required|exists:sections,id|different:' . $id
            ]);

            // Check if target section is in the same class
            $newSection = Section::find($request->new_section_id);
            if ($newSection->class_room_id != $section->class_room_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot move students to a section in a different class. Please select a section from the same class.'
                ], 400);
            }

            // Check if target section has capacity
            if ($newSection->capacity) {
                $targetStudentCount = Student::where('current_section_id', $newSection->id)->count();
                if ($targetStudentCount + $studentCount > $newSection->capacity) {
                    return response()->json([
                        'success' => false,
                        'message' => "Target section '{$newSection->name}' does not have enough capacity. Capacity: {$newSection->capacity}, Current students: {$targetStudentCount}, Need to move: {$studentCount}"
                    ], 400);
                }
            }
            
            // Move students and update attendance records using transaction
            DB::beginTransaction();
            try {
                // Update students to new section
                $updated = Student::where('current_section_id', $id)->update([
                    'current_section_id' => $request->new_section_id
                ]);
                
                // Update attendance records to new section
                DB::table('class_attendances')
                    ->where('section_id', $id)
                    ->update(['section_id' => $request->new_section_id]);
                
                DB::commit();
                
                Log::info("Successfully moved {$updated} students and {$attendanceCount} attendance records from section {$id} to section {$request->new_section_id}");
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to move data: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to move students and attendance records: ' . $e->getMessage()
                ], 500);
            }
        } else {
            // No students, but might have attendance records
            if ($attendanceCount > 0) {
                DB::beginTransaction();
                try {
                    // Either delete or update attendance records
                    // Option 1: Delete attendance records (if they can be regenerated)
                    DB::table('class_attendances')->where('section_id', $id)->delete();
                    
                    // OR Option 2: Update to null or another section
                    // DB::table('class_attendances')->where('section_id', $id)->update(['section_id' => null]);
                    
                    DB::commit();
                    
                    Log::info("Deleted {$attendanceCount} attendance records for section {$id}");
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Failed to handle attendance records: ' . $e->getMessage());
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to delete attendance records: ' . $e->getMessage()
                    ], 500);
                }
            }
        }

        // Delete the section
        $section->delete();

        $message = $studentCount > 0 
            ? "Section deleted successfully. {$studentCount} student(s) and {$attendanceCount} attendance record(s) were moved." 
            : ($attendanceCount > 0 
                ? "Section deleted successfully. {$attendanceCount} attendance record(s) were removed." 
                : 'Section deleted successfully');

        return response()->json([
            'success' => true,
            'message' => $message,
            'moved_students' => $studentCount,
            'moved_attendance' => $attendanceCount
        ]);
    }

    // Get sections by class
    public function getByClass($classId)
    {
        $sections = Section::where('class_room_id', $classId)
            ->orderBy('name')
            ->get();

        // Add student count for each section
        foreach ($sections as $section) {
            $section->student_count = Student::where('current_section_id', $section->id)->count();
        }

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    }
}