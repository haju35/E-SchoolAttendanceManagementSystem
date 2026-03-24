@extends('layouts.admin')

@section('title', 'Classes')
@section('page-title', 'Classes')

@section('page-actions')
<a href="#" class="btn btn-primary btn-sm disabled" aria-disabled="true">
    <i class="fas fa-plus"></i> Add Class
</a>
@endsection

@section('admin-content')
<div class="card">
    <div class="card-body">
        <p class="mb-0 text-muted">
            Classes view is ready. Connect this page to your class management routes and data table.
        </p>
    </div>
</div>
@endsection
