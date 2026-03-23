@extends('layouts.teacher')

@section('page-title', 'Attendance Reports')
@section('title', 'Attendance Reports')

@section('teacher-content')
<div class="card">
    <div class="card-header">
        <h5>Filter Reports</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('teacher.reports.attendance') }}" class="row">
            <div class="col-md-3">
                <label>Class</label>
                <select name="class_id" class="form-control">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Section</label>
                <select name="section_id" class="form-control" id="section_id">
                    <option value="">All Sections</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>From Date</label>
                <input type="date" name="from_date" class="form-control" value="{{ request('from_date', date('Y-m-01')) }}">
            </div>
            <div class="col-md-2">
                <label>To Date</label>
                <input type="date" name="to_date" class="form-control" value="{{ request('to_date', date('Y-m-d')) }}">
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary form-control">Generate</button>
            </div>
        </form>
    </div>
</div>

@if(isset($attendances) && count($attendances) > 0)
<div class="card mt-4">
    <div class="card-header">
        <h5>Attendance Report</h5>
        <div class="float-end">
            <a href="{{ route('teacher.reports.attendance', array_merge(request()->all(), ['export' => 'pdf'])) }}" 
               class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
            <a href="{{ route('teacher.reports.attendance', array_merge(request()->all(), ['export' => 'excel'])) }}" 
               class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendances as $attendance)
                    <tr>
                        <td>{{ date('d-m-Y', strtotime($attendance->date)) }}</td>
                        <td>{{ $attendance->student->user->name }}</td>
                        <td>{{ $attendance->class->name ?? 'N/A' }}</td>
                        <td>{{ $attendance->section->name ?? 'N/A' }}</td>
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
        
        <div class="mt-3">
            <strong>Summary:</strong>
            <div class="row mt-2">
                <div class="col-md-3">
                    <div class="alert alert-success">
                        Present: {{ $attendances->where('status', 'present')->count() }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-danger">
                        Absent: {{ $attendances->where('status', 'absent')->count() }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-warning">
                        Late: {{ $attendances->where('status', 'late')->count() }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-info">
                        Total: {{ $attendances->count() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    $('#class_id').change(function() {
        var classId = $(this).val();
        if(classId) {
            $.ajax({
                url: '/admin/sections/by-class/' + classId,
                type: 'GET',
                success: function(data) {
                    $('#section_id').empty();
                    $('#section_id').append('<option value="">All Sections</option>');
                    $.each(data, function(key, value) {
                        $('#section_id').append('<option value="'+ value.id +'">'+ value.name +'</option>');
                    });
                }
            });
        }
    });
    
    @if(request('class_id'))
        $('#class_id').trigger('change');
        setTimeout(function() {
            $('#section_id').val('{{ request('section_id') }}');
        }, 500);
    @endif
</script>
@endpush