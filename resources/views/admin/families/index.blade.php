@extends('layouts.admin')

@section('page-title', 'Manage Families')
@section('title', 'Families Management')

@section('page-actions')
<a href="{{ route('admin.families.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> Add New Family
</a>
@endsection

@section('admin-content')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="families-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Occupation</th>
                        <th>Children Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($families as $family)
                    <tr>
                        <td>{{ $family->id }}</td>
                        <td>{{ $family->user->name }}</td>
                        <td>{{ $family->user->email }}</td>
                        <td>{{ $family->user->phone }}</td>
                        <td>{{ $family->occupation }}</td>
                        <td>{{ $family->students->count() }}</td>
                        <td>
                            <a href="{{ route('admin.families.show', $family->id) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.families.edit', $family->id) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-danger delete-family" data-id="{{ $family->id }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-center">
            {{ $families->links() }}
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
        $('#families-table').DataTable({
            responsive: true,
            pageLength: 25
        });
        
        $('.delete-family').click(function() {
            var id = $(this).data('id');
            if(confirm('Are you sure you want to delete this family?')) {
                var form = $('#delete-form');
                form.attr('action', '/admin/families/' + id);
                form.submit();
            }
        });
    });
</script>
@endpush