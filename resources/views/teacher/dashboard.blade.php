@extends('layouts.teacher')

@section('page-title', 'Teacher Dashboard')
@section('title', 'Teacher Dashboard')

@section('teacher-content')
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">My Classes</h5>
                        <h2 class="mb-0">{{ $data['my_classes']->count() }}</h2>
                    </div>
                    <i class="fas fa-chalkboard fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Students</h5>
                        <h2 class="mb-0">{{ $data['total_students'] }}</h2>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Today's Attendance</h5>
                        <h2 class="mb-0">{{ $data['today_attendance'] }}</h2>
                    </div>
                    <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Subjects</h5>
                        <h2 class="mb-0">{{ $data['subjects_count'] }}</h2>
                    </div>
                    <i class="fas fa-book fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>My Classes</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    @foreach($data['my_classes'] as $class)
                        <a href="{{ route('teacher.classes.students', $class->id) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-building"></i> {{ $class->name }}
                                    <small class="text-muted">({{ $class->sections->count() }} sections)</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="{{ route('teacher.attendance.index') }}" class="btn btn-primary w-100">
                            <i class="fas fa-calendar-alt"></i><br>
                            Mark Attendance
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="{{ route('teacher.reports.attendance') }}" class="btn btn-info w-100">
                            <i class="fas fa-chart-line"></i><br>
                            View Reports
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="{{ route('teacher.classes.index') }}" class="btn btn-success w-100">
                            <i class="fas fa-chalkboard"></i><br>
                            My Classes
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="{{ route('teacher.profile.show') }}" class="btn btn-warning w-100">
                            <i class="fas fa-user"></i><br>
                            My Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection