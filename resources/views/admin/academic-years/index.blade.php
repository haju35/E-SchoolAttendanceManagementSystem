@extends('layouts.admin')

@section('title', 'Academic Years')
@section('page-title', 'Academic Years')

@section('page-actions')
<a href="#" class="btn btn-primary btn-sm disabled" aria-disabled="true">
    <i class="fas fa-plus"></i> Add Academic Year
</a>
@endsection

@section('admin-content')
<div class="card">
    <div class="card-body">
        <p class="mb-0 text-muted">
            Academic years view is ready. Wire this to your academic year CRUD routes and form modals.
        </p>
    </div>
</div>
@endsection
