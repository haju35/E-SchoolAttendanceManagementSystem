@extends('layouts.student')

@section('page-title', 'My Attendance')
@section('title', 'Attendance Records')

@section('student-content')
<div class="card">
    <div class="card-header">
        <h5>Attendance Summary</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="alert alert-success text-center">
                    <h4>{{ $summary['present'] }}</h4>
                    <p>Present Days</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-danger text-center">
                    <h4>{{ $summary['absent'] }}</h4>
                    <p>Absent Days</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-warning text-center">
                    <h4>{{ $summary['late'] }}</h4>
                    <p>Late Days</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert alert-info text-center">
                    <h4>{{ $summary['total'] }}</h4>
                    <p>Total Days</p>
                </div>
            </div>
        </div>
        
        <div class="progress mb-4" style="height: 30px;">
            <div class="progress-bar bg-success" style="width: {{ $summary['percentage'] }}%">
                {{ $summary['percentage'] }}% Present
            </div>
            <div class="progress-bar bg-danger" style="width: {{ $summary['absent_percentage'] }}%">
                {{ $summary['absent_percentage'] }}% Absent
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5>Attendance Details</h5>
        <form method="GET" class="float-end">
            <div class="row">
                <div class="col-md-5">
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date', date('Y-m-01')) }}">
                </div>
                <div class="col-md-5">
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date', date('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="attendance-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendances as $attendance)
                    <tr>
                        <td>{{ date('d-m-Y', strtotime($attendance->date)) }}</td>
                        <td>{{ $attendance->subject->name ?? 'N/A' }}</td>
                        <td>{{ $attendance->teacher->user->name ?? 'N/A' }}</td>
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
        
        {{ $attendances->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#attendance-table').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']]
        });
    });
</script>
@endpush