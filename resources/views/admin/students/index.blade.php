@extends('layouts.admin')

@section('page-title', 'Manage Students')
@section('title', 'Students Management')

@section('page-actions')
<div>
    <a href="{{ route('admin.students.import') }}" class="btn btn-info">
        <i class="fas fa-upload"></i> Bulk Import
    </a>
    <a href="{{ route('admin.students.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Student
    </a>
</div>
@endsection

@section('admin-content')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="students-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Admission No</th>
                        <th>Roll No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                    <tr>
                        <td>{{ $student->id }}</td>
                        <td>{{ $student->admission_number }}</td>
                        <td>{{ $student->roll_number }}</td>
                        <td>{{ $student->user->name }}</td>
                        <td>{{ $student->user->email }}</td>
                        <td>{{ $student->currentClass->name ?? 'N/A' }}</td>
                        <td>{{ $student->currentSection->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $student->status == 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($student->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.students.show', $student->id) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.students.edit', $student->id) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-danger delete-student" data-id="{{ $student->id }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-center">
            {{ $students->links() }}
        </div>
    </div>
</div>

<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#students-table').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']]
        });
        
        $('.delete-student').click(function() {
            var id = $(this).data('id');
            if(confirm('Are you sure you want to delete this student?')) {
                var form = $('#delete-form');
                form.attr('action', '/admin/students/' + id);
                form.submit();
            }
        });
    });
</script>
@endpush