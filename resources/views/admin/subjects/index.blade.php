@extends('layouts.admin')

@section('page-title', 'Manage Subjects')
@section('title', 'Subjects Management')

@section('page-actions')
<a href="{{ route('admin.subjects.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> Add New Subject
</a>
@endsection

@section('admin-content')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="subjects-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Assigned Teachers</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjects as $subject)
                     <tr>
                        <td>{{ $subject->id }}</td>
                        <td>{{ $subject->code }}</td>
                        <td>{{ $subject->name }}</td>
                        <td>{{ $subject->teacherAssignments->count() }}</td>
                        <td>
                            <a href="{{ route('admin.subjects.show', $subject->id) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.subjects.edit', $subject->id) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-danger delete-subject" data-id="{{ $subject->id }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                     </tr>
                    @endforeach
                </tbody>
             </table>
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
        $('#subjects-table').DataTable({
            responsive: true,
            pageLength: 25
        });
        
        $('.delete-subject').click(function() {
            var id = $(this).data('id');
            if(confirm('Are you sure you want to delete this subject?')) {
                var form = $('#delete-form');
                form.attr('action', '/admin/subjects/' + id);
                form.submit();
            }
        });
    });
</script>
@endpush