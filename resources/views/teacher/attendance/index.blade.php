@extends('layouts.teacher')

@section('page-title', 'Mark Attendance')
@section('title', 'Attendance Management')

@section('teacher-content')
<div class="card">
    <div class="card-header">
        <h5>Select Class and Date</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('teacher.attendance.index') }}" class="row">
            <div class="col-md-4">
                <label for="class_id">Class</label>
                <select name="class_id" id="class_id" class="form-control" required>
                    <option value="">Select Class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="section_id">Section</label>
                <select name="section_id" id="section_id" class="form-control" required>
                    <option value="">Select Section</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date">Date</label>
                <input type="date" name="date" id="date" class="form-control" value="{{ request('date', date('Y-m-d')) }}" required>
            </div>
            <div class="col-md-1">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary form-control">Load</button>
            </div>
        </form>
    </div>
</div>

@if(isset($students) && count($students) > 0)
<div class="card mt-4">
    <div class="card-header">
        <h5>Mark Attendance for {{ date('d-m-Y', strtotime($date)) }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('teacher.attendance.store') }}" method="POST">
            @csrf
            <input type="hidden" name="class_id" value="{{ request('class_id') }}">
            <input type="hidden" name="section_id" value="{{ request('section_id') }}">
            <input type="hidden" name="date" value="{{ $date }}">
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                         <tr>
                            <th>Roll No</th>
                            <th>Student Name</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Remarks</th>
                         </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                        <tr>
                            <td>{{ $student->roll_number }}</td>
                            <td>{{ $student->user->name }}</td>
                            <td>
                                <select name="attendance[{{ $student->id }}][subject_id]" class="form-control" required>
                                    <option value="">Select Subject</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="attendance[{{ $student->id }}][status]" class="form-control" required>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="late">Late</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="attendance[{{ $student->id }}][reason]" class="form-control" placeholder="Reason (if absent/late)">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <button type="submit" class="btn btn-primary mt-3">Save Attendance</button>
        </form>
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
                    $('#section_id').append('<option value="">Select Section</option>');
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