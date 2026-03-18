<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\Family;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Check if user is active (using is_active field)
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account is not active. Please contact administrator.'
                ]);
            }

            $request->session()->regenerate();

            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'));
            } elseif ($user->isTeacher()) {
                return redirect()->intended(route('teacher.dashboard'));
            } elseif ($user->isStudent()) {
                return redirect()->intended(route('student.dashboard'));
            } elseif ($user->isFamily()) {
                return redirect()->intended(route('family.dashboard'));
            }
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.'
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function apiLogin(LoginRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials)) {
                /** @var \App\Models\User $user */
                $user = Auth::user();

                // Check if user is active
                if (!$user->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Account is not active. Please contact administrator.'
                    ], 403);
                }

                // Revoke old tokens
                $user->tokens()->delete();

                // Create new token
                $tokenResult = $user->createToken('auth_token');
                $token = $tokenResult->token;
                $token->expires_at = Carbon::now()->addDays(7);
                $token->save();

                // Load appropriate relationship based on role
                if ($user->isStudent()) {
                    $user->load('student.class', 'student.section');
                } elseif ($user->isTeacher()) {
                    $user->load('teacher');
                } elseif ($user->isFamily()) {
                    $user->load('family.students');
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role,
                            'phone' => $user->phone,
                            'address' => $user->address,
                            'profile_photo' => $user->profile_photo,
                            'is_active' => $user->is_active,
                            // Include role-specific data
                            'profile' => $user->isStudent() ? $user->student : ($user->isTeacher() ? $user->teacher : ($user->isFamily() ? $user->family : null))
                        ],
                        'access_token' => $tokenResult->accessToken,
                        'token_type' => 'Bearer',
                        'expires_at' => $token->expires_at->toDateTimeString()
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        } catch (\Exception $e) {
            Log::error('API Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ], 500);
        }
    }

    public function apiUser(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Load appropriate relationships based on role
            if ($user->isStudent()) {
                $user->load('student.class', 'student.section');
            } elseif ($user->isTeacher()) {
                $user->load('teacher');
            } elseif ($user->isFamily()) {
                $user->load('family.students');
            }

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'address' => $user->address,
                'profile_photo' => $user->profile_photo,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ];

            // Add role-specific data
            if ($user->isStudent() && $user->student) {
                $userData['student'] = $user->student;
                $userData['class'] = $user->student->class ?? null;
                $userData['section'] = $user->student->section ?? null;
            } elseif ($user->isTeacher() && $user->teacher) {
                $userData['teacher'] = $user->teacher;
            } elseif ($user->isFamily() && $user->family) {
                $userData['family'] = $user->family;
                $userData['children'] = $user->family->students ?? [];
            }

            return response()->json([
                'success' => true,
                'data' => $userData
            ]);
        } catch (\Exception $e) {
            Log::error('Get user error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user information'
            ], 500);
        }
    }

    public function apiRefresh(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $request->user()->token()->revoke();

            $tokenResult = $user->createToken('auth_token');
            $token = $tokenResult->token;
            $token->expires_at = Carbon::now()->addDays(7);
            $token->save();

            return response()->json([
                'success' => true,
                'access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->expires_at->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Token refresh error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed'
            ], 500);
        }
    }

    public function apiLogout(Request $request)
    {
        try {
            $request->user()->token()->revoke();
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    public function apiDashboard(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->isAdmin()) {
            return $this->apiAdminDashboard();
        } elseif ($user->isTeacher()) {
            return $this->apiTeacherDashboard($user);
        } elseif ($user->isStudent()) {
            return $this->apiStudentDashboard($user);
        } elseif ($user->isFamily()) {
            return $this->apiFamilyDashboard($user);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role'
            ], 400);
        }
    }

    public function apiAdminDashboard()
    {
        $stats = [
            'total_students' => Student::count(),
            'total_teachers' => Teacher::count(),
            'total_families' => Family::count(),
            'total_classes' => ClassRoom::count(),
            'today_attendance' => Attendance::whereDate('created_at', today())->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function apiTeacherDashboard(User $user)
    {
        $teacher = $user->teacher;

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher profile not found'
            ], 404);
        }

        // Get classes taught by this teacher
        $classes = $teacher->classRoom ?? collect();
        $classIds = $classes->pluck('id')->toArray();

        $data = [
            'teacher' => $teacher,
            'my_classes' => $classes,
            'total_students' => !empty($classIds) ? Student::whereIn('class_id', $classIds)->count() : 0,
            'today_attendance' => Attendance::where('teacher_id', $teacher->id)
                ->whereDate('created_at', today())->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function apiStudentDashboard(User $user)
    {
        $student = $user->student;

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student profile not found'
            ], 404);
        }

        // Load relationships
        $student->load('class', 'section');

        $totalDays = Attendance::where('student_id', $student->id)->count();
        $presentDays = Attendance::where('student_id', $student->id)
            ->where('status', 'present')->count();
        $absentDays = Attendance::where('student_id', $student->id)
            ->where('status', 'absent')->count();
        $lateDays = Attendance::where('student_id', $student->id)
            ->where('status', 'late')->count();

        $attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;

        $data = [
            'student' => $student,
            'class' => $student->class ?? null,
            'section' => $student->section ?? null,
            'attendance_summary' => [
                'total_days' => $totalDays,
                'present' => $presentDays,
                'absent' => $absentDays,
                'late' => $lateDays,
                'percentage' => $attendancePercentage
            ],
            'recent_attendance' => Attendance::where('student_id', $student->id)
                ->with(['class', 'subject'])
                ->latest()
                ->take(10)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function apiFamilyDashboard(User $user)
    {
        $family = $user->family;

        if (!$family) {
            return response()->json([
                'success' => false,
                'message' => 'Family profile not found'
            ], 404);
        }

        // Get children with their class and section
        $children = $family->students()->with(['class', 'section'])->get();

        // Get attendance summary for each child
        foreach ($children as $child) {
            $totalDays = Attendance::where('student_id', $child->id)->count();
            $presentDays = Attendance::where('student_id', $child->id)
                ->where('status', 'present')->count();
            $child->attendance_percentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;

            // Get today's attendance status
            $todayAttendance = Attendance::where('student_id', $child->id)
                ->whereDate('attendance_date', today())
                ->first();
            $child->today_status = $todayAttendance->status ?? 'Not marked';
        }

        $data = [
            'family' => $family,
            'children' => $children,
            'notifications' => $user->receivedNotifications()->latest()->take(5)->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
