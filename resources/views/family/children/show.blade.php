@extends('layouts.family')

@section('page-title', 'Child Details - ' . $child->user->name)
@section('title', 'Child Profile')

@section('page-actions')
<a href="{{ route('family.children.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Back to Children
</a>
@endsection

@section('family-content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                @if($child->user->profile_photo)
                    <img src="{{ asset('storage/'.$child->user->profile_photo) }}" class="rounded-circle img-fluid" style="width: 150px;">
                @else
                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                         style="width: 150px; height: 150px;">
                        <i class="fas fa-user-graduate fa-5x text-white"></i>
                    </div>
                @endif
                <h3 class="mt-3">{{ $child->user->name }}</h3>
                <p class="text-muted">Student</p>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Attendance Summary</h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <h2>{{ $summary['attendance_percentage'] }}%</h2>
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar bg-success" style="width: {{ $summary['attendance_percentage'] }}%"></div>
                    </div>
                    <p><strong>Present:</strong> {{ $summary['present'] }} days</p>
                    <p><strong>Absent:</strong> {{ $summary['absent'] }} days</p>
                    <p><strong>Late:</strong> {{ $summary['late'] }} days</p>
                    <p><strong>Total:</strong> {{ $summary['total_days'] }} days</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Personal Information</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="200">Full Name:</th>
                        <td>{{ $child->user->name }}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>{{ $child->user->email }}</td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td>{{ $child->user->phone ?? 'Not provided' }}</td>
                    </tr>
                    <tr>
                        <th>Address:</th>
                        <td>{{ $child->user->address ?? 'Not provided' }}</td>
                    </tr>
                    <tr>
                        <th>Admission Number:</th>
                        <td>{{ $child->admission_number }}</td>
                    </tr>
                    <tr>
                        <th>Roll Number:</th>
                        <td>{{ $child->roll_number }}</td>
                    </tr>
                    <tr>
                        <th>Class:</th>
                        <td>{{ $child->currentClass->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Section:</th>
                        <td>{{ $child->currentSection->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Date of Birth:</th>
                        <td>{{ date('d-m-Y', strtotime($child->date_of_birth)) }}</td>
                    </tr>
                    <tr>
                        <th>Gender:</th>
                        <td>{{ ucfirst($child->gender) }}</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <span class="badge bg-{{ $child->status == 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($child->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Recent Attendance</h5>
                <a href="{{ route('family.children.attendance', $child->id) }}" class="btn btn-sm btn-primary float-end">
                    View All
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent_attendance as $attendance)
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
                                </td>
                                <td>{{ $attendance->reason }}</td>
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