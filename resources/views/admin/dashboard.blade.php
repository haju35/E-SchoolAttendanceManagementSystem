@extends('layouts.admin')

@section('page-title', 'Dashboard')
@section('title', 'Admin Dashboard')

@section('admin-content')
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Students</h5>
                        <h2 class="mb-0">{{ $stats['total_students'] }}</h2>
                    </div>
                    <i class="fas fa-user-graduate fa-3x opacity-50"></i>
                </div>
                <a href="{{ route('admin.students.index') }}" class="text-white text-decoration-none mt-2 d-block">
                    View Details <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Teachers</h5>
                        <h2 class="mb-0">{{ $stats['total_teachers'] }}</h2>
                    </div>
                    <i class="fas fa-chalkboard-user fa-3x opacity-50"></i>
                </div>
                <a href="{{ route('admin.teachers.index') }}" class="text-white text-decoration-none mt-2 d-block">
                    View Details <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Families</h5>
                        <h2 class="mb-0">{{ $stats['total_families'] }}</h2>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
                <a href="{{ route('admin.families.index') }}" class="text-white text-decoration-none mt-2 d-block">
                    View Details <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total Classes</h5>
                        <h2 class="mb-0">{{ $stats['total_classes'] }}</h2>
                    </div>
                    <i class="fas fa-building fa-3x opacity-50"></i>
                </div>
                <a href="{{ route('admin.classes.index') }}" class="text-white text-decoration-none mt-2 d-block">
                    View Details <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Recent Students</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Admission No</th>
                                <th>Class</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['recent_students'] as $student)
                            <tr>
                                <td>{{ $student->user->name }}</td>
                                <td>{{ $student->admission_number }}</td>
                                <td>{{ $student->currentClass->name ?? 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('admin.students.show', $student->id) }}" class="btn btn-sm btn-info">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Recent Teachers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Employee ID</th>
                                <th>Qualification</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['recent_teachers'] as $teacher)
                            <tr>
                                <td>{{ $teacher->user->name }}</td>
                                <td>{{ $teacher->employee_id }}</td>
                                <td>{{ $teacher->qualification }}</td>
                                <td>
                                    <a href="{{ route('admin.teachers.show', $teacher->id) }}" class="btn btn-sm btn-info">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection