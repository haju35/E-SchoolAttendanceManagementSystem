<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFamilyRequest;
use App\Http\Requests\UpdateFamilyRequest;
use App\Models\User;
use App\Models\Family;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\FamilyCredentialsMail;

class FamilyController extends Controller
{
    public function index(Request $request)
    {
        $families = Family::with(['user', 'students' => function($q) {
                $q->with('user', 'currentClass', 'currentSection');
            }])
            ->when($request->search, function($query, $search) {
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            })
            ->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $families
        ]);
    }

    public function store(StoreFamilyRequest $request)
    {
        DB::beginTransaction();
        
        try {
            $password = Str::random(10);

            // Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'is_active' => true,
                'password' => Hash::make($password),
                'role' => 'family'
            ]);

            $user->assignRole('family');
            
            // Create Family
            $family = Family::create([
                'user_id' => $user->id,
                'occupation' => $request->occupation,
                'emergency_contact' => $request->emergency_contact
            ]);
            
            // Link students if provided
            if ($request->has('student_ids')) {
                Student::whereIn('id', $request->student_ids)
                    ->update(['family_id' => $family->id]);
            }

            // Send email
            $emailSent = false;

            try {
                Mail::to($user->email)->send(
                    new FamilyCredentialsMail($user, $password)
                );

                $emailSent = true;
            } catch (\Exception $e) {
                \Log::warning('Family email failed: ' . $e->getMessage());
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Family created successfully',
                'credentials' => [
                    'email' => $user->email,
                    'password' => $password
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create family',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $family = Family::with(['user', 'students' => function($q) {
                $q->with('user', 'currentClass', 'currentSection');
            }])->find($id);
        
        if (!$family) {
            return response()->json([
                'success' => false,
                'message' => 'Family not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $family
        ]);
    }

    public function update(UpdateFamilyRequest $request, $id)
    {
        $family = Family::find($id);
        
        if (!$family) {
            return response()->json([
                'success' => false,
                'message' => 'Family not found'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            // Update User
            $userData = [];
            if ($request->has('name')) $userData['name'] = $request->name;
            if ($request->has('email')) $userData['email'] = $request->email;
            if ($request->has('phone')) $userData['phone'] = $request->phone;
            if ($request->has('address')) $userData['address'] = $request->address;
            
            if (!empty($userData)) {
                $family->user->update($userData);
            }
            
            // Update Family
            $familyData = $request->only(['occupation', 'emergency_contact']);
            if (!empty($familyData)) {
                $family->update($familyData);
            }
            
            // Update student links
            if ($request->has('student_ids')) {
                // Remove existing links
                Student::where('family_id', $family->id)
                    ->update(['family_id' => null]);
                
                // Add new links
                Student::whereIn('id', $request->student_ids)
                    ->update(['family_id' => $family->id]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Family updated successfully',
                'data' => $family->fresh(['user', 'students'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update family',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $family = Family::with('user')->find($id);
        
        if (!$family) {
            return response()->json([
                'success' => false,
                'message' => 'Family not found'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            // Unlink students
            Student::where('family_id', $family->id)
                ->update(['family_id' => null]);
            
            // Delete family (user will be cascade deleted)
            if($family->user){
                $family->user->delete();
            }
            $family->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Family deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete family',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}