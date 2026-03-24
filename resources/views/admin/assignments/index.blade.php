@extends('layouts.admin')

@section('title', 'Teacher Assignments')
@section('page-title', 'Teacher Assignments')

@section('page-actions')
<a href="#" class="btn btn-primary btn-sm disabled" aria-disabled="true">
    <i class="fas fa-plus"></i> New Assignment
</a>
@endsection

@section('admin-content')
<div class="card">
    <div class="card-body">
        <p class="mb-0 text-muted">
            Teacher assignments view is ready. Add assignment matrix/list after connecting backend data.
        </p>
    </div>
</div>
@endsection
