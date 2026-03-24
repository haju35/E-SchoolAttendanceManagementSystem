@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('admin-content')
<div class="row">
    <!-- Statistics Cards -->
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Students</h6>
                        <h2 class="mb-0">{{ $stats['total_students'] ?? 0 }}</h2>
                    </div>
                    <i class="fas fa-user-graduate fa-3x opacity-50"></i>
                </div>
                <small class="mt-2 d-block">+{{ $stats['new_students_month'] ?? 0 }} this month</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Teachers</h6>
                        <h2 class="mb-0">{{ $stats['total_teachers'] ?? 0 }}</h2>
                    </div>
                    <i class="fas fa-chalkboard-user fa-3x opacity-50"></i>
                </div>
                <small class="mt-2 d-block">{{ $stats['active_teachers'] ?? 0 }} active</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Today's Attendance</h6>
                        <h2 class="mb-0">{{ $stats['today_attendance'] ?? 0 }}%</h2>
                    </div>
                    <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                </div>
                <small class="mt-2 d-block">{{ $stats['present_today'] ?? 0 }} / {{ $stats['total_students'] ?? 0 }} present</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Classes</h6>
                        <h2 class="mb-0">{{ $stats['total_classes'] ?? 0 }}</h2>
                    </div>
                    <i class="fas fa-building fa-3x opacity-50"></i>
                </div>
                <small class="mt-2 d-block">{{ $stats['total_sections'] ?? 0 }} sections</small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Attendance Trend (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="attendanceChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Today's Attendance Summary</h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <div class="mb-3">
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar bg-success" style="width: {{ $stats['present_percentage'] ?? 0 }}%">
                                Present: {{ $stats['present_today'] ?? 0 }}
                            </div>
                            <div class="progress-bar bg-danger" style="width: {{ $stats['absent_percentage'] ?? 0 }}%">
                                Absent: {{ $stats['absent_today'] ?? 0 }}
                            </div>
                            <div class="progress-bar bg-warning" style="width: {{ $stats['late_percentage'] ?? 0 }}%">
                                Late: {{ $stats['late_today'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <h6>Present</h6>
                                <h4 class="text-success">{{ $stats['present_today'] ?? 0 }}</h4>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <h6>Absent</h6>
                                <h4 class="text-danger">{{ $stats['absent_today'] ?? 0 }}</h4>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-2">
                                <h6>Late</h6>
                                <h4 class="text-warning">{{ $stats['late_today'] ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities Row -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Recent Students</h5>
                <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-primary float-end">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Admission No</th><th>Name</th><th>Class</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach($stats['recent_students'] ?? [] as $student)
                            <tr>
                                <td>{{ $student->admission_number }}</td>
                                <td>{{ $student->user->name }}</td>
                                <td>{{ $student->currentClass->name ?? 'N/A' }}</td>
                                <td><span class="badge bg-{{ $student->status == 'active' ? 'success' : 'danger' }}">{{ $student->status }}</span></td>
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
                <h5>Frequently Absent Students</h5>
                <a href="{{ route('admin.reports.attendance') }}" class="btn btn-sm btn-warning float-end">View Report</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Student</th><th>Class</th><th>Absent Days</th><th>Attendance %</th></tr>
                        </thead>
                        <tbody>
                            @foreach($stats['frequent_absentees'] ?? [] as $student)
                            <tr>
                                <td>{{ $student->user->name }}</td>
                                <td>{{ $student->currentClass->name ?? 'N/A' }}</td>
                                <td class="text-danger">{{ $student->absent_days }}</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-danger" style="width: {{ 100 - $student->attendance_percentage }}%">
                                            {{ $student->attendance_percentage }}%
                                        </div>
                                    </div>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($stats['chart_labels'] ?? []) !!},
            datasets: [{
                label: 'Attendance %',
                data: {!! json_encode($stats['chart_data'] ?? []) !!},
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
</script>
@endpush
@endsection