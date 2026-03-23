@extends('layouts.admin')

@section('page-title', 'Manage Terms')
@section('title', 'Terms Management')

@section('page-actions')
<a href="{{ route('admin.terms.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> Add New Term
</a>
@endsection

@section('admin-content')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="terms-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Academic Year</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($terms as $term)
                    <tr>
                        <td>{{ $term->id }}</td>
                        <td>{{ $term->name }}</td>
                        <td>{{ $term->academicYear->name }}</td>
                        <td>{{ $term->start_date->format('d-m-Y') }}</td>
                        <td>{{ $term->end_date->format('d-m-Y') }}</td>
                        <td>
                            @if($term->is_current)
                                <span class="badge bg-success">Current</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.terms.show', $term->id) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.terms.edit', $term->id) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-danger delete-term" data-id="{{ $term->id }}">
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
        $('#terms-table').DataTable({
            responsive: true,
            order: [[0, 'desc']]
        });
        
        $('.delete-term').click(function() {
            var id = $(this).data('id');
            if(confirm('Are you sure you want to delete this term?')) {
                var form = $('#delete-form');
                form.attr('action', '/admin/terms/' + id);
                form.submit();
            }
        });
    });
</script>
@endpush