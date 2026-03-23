@extends('layouts.student')

@section('page-title', 'Student Dashboard')
@section('title', 'Dashboard')

@section('student-content')
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Attendance Percentage</h5>
                        <h2 class="mb-0">{{ $data['attendance_summary']['percentage'] }}%</h2>
                    </div>
                    <i class="fas fa-chart-line fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Present Days</h5>
                        <h2 class="mb-0">{{ $data['attendance_summary']['present'] }}</h2>
                    </div>
                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Absent Days</h5>
                        <h2 class="mb-0">{{ $data['attendance_summary']['absent'] }}</h2>
                    </div>
                    <i class="fas fa-times-circle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Student Information</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="150">Name:</th>
                        <td>{{ $data['student']->user->name }}</td>
                    </tr>
                    <tr>
                        <th>Admission No:</th>
                        <td>{{ $data['student']->admission_number }}</td>
                    </tr>
                    <tr>
                        <th>Roll Number:</th>
                        <td>{{ $data['student']->roll_number }}</td>
                    </tr>
                    <tr>
                        <th>Class:</th>
                        <td>{{ $data['class']->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Section:</th>
                        <td>{{ $data['section']->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Date of Birth:</th>
                        <td>{{ date('d-m-Y', strtotime($data['student']->date_of_birth)) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Recent Attendance</h5>
                <a href="{{ route('student.attendance.index') }}" class="btn btn-sm btn-primary float-end">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                             <tr>
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Status</th>
                             </tr>
                        </thead>
                        <tbody>
                            @foreach($data['recent_attendance'] as $attendance)
                            <tr>
                                <td>{{ date('d-m-Y', strtotime($attendance->date)) }}</td>
                                <td>{{ $attendance->subject->name ?? 'N/A' }}</td>
                                <td>
                                    @if($attendance->status == 'present')
                                        <span class="badge bg-success">Present</span>
                                    @elseif($attendance->status == 'absent')
                                        <span class="badge bg-danger">Absent</span>
                                    @else
                                        <span class="badge bg-warning">Late</span>
                                    @endif
                                
    
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