@extends('layouts.teacher')

@section('page-title', 'Students - ' . $class->name . ' - Section ' . $section->name)
@section('title', 'Class Students')

@section('page-actions')
<a href="{{ route('teacher.attendance.index', ['class_id' => $class->id, 'section_id' => $section->id]) }}" 
   class="btn btn-primary">
    <i class="fas fa-calendar-check"></i> Mark Attendance
</a>
<a href="{{ route('teacher.classes.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Back
</a>
@endsection

@section('teacher-content')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="students-table">
                <thead>
                     <tr>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th>Admission No</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Attendance %</th>
                        <th>Actions</th>
                     </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                    <tr>
                        <td>{{ $student->roll_number }}</td>
                        <td>{{ $student->user->name }}</td>
                        <td>{{ $student->admission_number }}</td>
                        <td>{{ $student->user->email }}</td>
                        <td>{{ $student->user->phone }}</td>
                        <td>
                            @php
                                $total = $student->attendances->count();
                                $present = $student->attendances->where('status', 'present')->count();
                                $percentage = $total > 0 ? round(($present/$total)*100, 2) : 0;
                            @endphp
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: {{ $percentage }}%">
                                    {{ $percentage }}%
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('teacher.students.show', $student->id) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('teacher.attendance.index', ['student_id' => $student->id]) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-calendar-alt"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#students-table').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'asc']]
        });
    });
</script>
@endpush