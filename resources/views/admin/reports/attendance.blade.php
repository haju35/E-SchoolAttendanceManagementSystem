@extends('layouts.admin')

@section('title', 'Attendance Reports')
@section('page-title', 'Attendance Reports & Analytics')

@section('admin-content')
<div class="card">
    <div class="card-header">
        <h5>Report Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.attendance') }}" class="row">
            <div class="col-md-3">
                <label>Report Type</label>
                <select name="type" class="form-control" required>
                    <option value="daily" {{ request('type') == 'daily' ? 'selected' : '' }}>Daily Report</option>
                    <option value="weekly" {{ request('type') == 'weekly' ? 'selected' : '' }}>Weekly Report</option>
                    <option value="monthly" {{ request('type') == 'monthly' ? 'selected' : '' }}>Monthly Report</option>
                    <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>Class</label>
                <select name="class_id" class="form-control">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>Section</label>
                <select name="section_id" class="form-control" id="sectionSelect">
                    <option value="">All Sections</option>
                </select>
            </div>
            <div class="col-md-2" id="dateRange" style="display: none;">
                <label>From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date', date('Y-m-01')) }}">
            </div>
            <div class="col-md-2" id="dateRange2" style="display: none;">
                <label>To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date', date('Y-m-d')) }}">
            </div>
            <div class="col-md-1">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary form-control">Generate</button>
            </div>
        </form>
    </div>
</div>

@if(isset($report))
<div class="card mt-4">
    <div class="card-header">
        <h5>{{ $report['title'] }}</h5>
        <div class="float-end">
            <a href="{{ route('admin.reports.attendance.export', request()->all()) }}" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <a href="{{ route('admin.reports.attendance.pdf', request()->all()) }}" class="btn btn-sm btn-danger">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <button onclick="window.print()" class="btn btn-sm btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="alert alert-info text-center">
                    <h4>{{ $report['total_students'] }}</h4>
                    <p>Total Students</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-success text-center">
                    <h4>{{ $report['present'] }}</h4>
                    <p>Present</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-danger text-center">
                    <h4>{{ $report['absent'] }}</h4>
                    <p>Absent</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-warning text-center">
                    <h4>{{ $report['attendance_percentage'] }}%</h4>
                    <p>Attendance Rate</p>
                </div>
            </div>
        </div>
        
        <!-- Chart -->
        <div class="row mb-4">
            <div class="col-md-12">
                <canvas id="attendanceChart" height="300"></canvas>
            </div>
        </div>
        
        <!-- Detailed Table -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Total Days</th>
                        <th>Attendance %</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['students'] as $student)
                    <tr>
                        <td>{{ $student['name'] }}</td>
                        <td>{{ $student['class'] }}</td>
                        <td>{{ $student['section'] }}</td>
                        <td class="text-success">{{ $student['present'] }}</td>
                        <td class="text-danger">{{ $student['absent'] }}</td>
                        <td class="text-warning">{{ $student['late'] }}</td>
                        <td>{{ $student['total'] }}</td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-{{ $student['percentage'] >= 75 ? 'success' : ($student['percentage'] >= 50 ? 'warning' : 'danger') }}" 
                                     style="width: {{ $student['percentage'] }}%">
                                    {{ $student['percentage'] }}%
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($student['percentage'] >= 75)
                                <span class="badge bg-success">Good</span>
                            @elseif($student['percentage'] >= 50)
                                <span class="badge bg-warning">Needs Improvement</span>
                            @else
                                <span class="badge bg-danger">Poor</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $('#classFilter').change(function() {
        var classId = $(this).val();
        if(classId) {
            $.ajax({
                url: '/admin/sections/by-class/' + classId,
                success: function(data) {
                    $('#sectionSelect').empty().append('<option value="">All Sections</option>');
                    $.each(data, function(key, section) {
                        $('#sectionSelect').append('<option value="'+ section.id +'">'+ section.name +'</option>');
                    });
                }
            });
        }
    });
    
    $('select[name="type"]').change(function() {
        if($(this).val() == 'custom') {
            $('#dateRange, #dateRange2').show();
        } else {
            $('#dateRange, #dateRange2').hide();
        }
    }).trigger('change');
    
    @if(isset($report['chart_data']))
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($report['chart_data']['labels']) !!},
            datasets: [{
                label: 'Attendance Percentage',
                data: {!! json_encode($report['chart_data']['data']) !!},
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
    @endif
</script>
@endpush
@endsection