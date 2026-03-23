@extends('layouts.admin')

@section('page-title', 'Manage Teachers')
@section('title', 'Teachers Management')

@section('page-actions')
<a href="{{ route('admin.teachers.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> Add New Teacher
</a>
@endsection

@section('admin-content')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="teachers-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Qualification</th>
                        <th>Joining Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teachers as $teacher)
                    <tr>
                        <td>{{ $teacher->id }}</td>
                        <td>{{ $teacher->employee_id }}</td>
                        <td>{{ $teacher->user->name }}</td>
                        <td>{{ $teacher->user->email }}</td>
                        <td>{{ $teacher->qualification }}</td>
                        <td>{{ date('d-m-Y', strtotime($teacher->joining_date)) }}</td>
                        <td>
                            <a href="{{ route('admin.teachers.show', $teacher->id) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.teachers.edit', $teacher->id) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-danger delete-teacher" data-id="{{ $teacher->id }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-center">
            {{ $teachers->links() }}
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
        $('#teachers-table').DataTable({
            responsive: true,
            pageLength: 25
        });
        
        $('.delete-teacher').click(function() {
            var id = $(this).data('id');
            if(confirm('Are you sure you want to delete this teacher?')) {
                var form = $('#delete-form');
                form.attr('action', '/admin/teachers/' + id);
                form.submit();
            }
        });
    });
</script>
@endpush