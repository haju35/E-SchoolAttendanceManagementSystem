@extends('layouts.admin')

@section('title', 'Sections')
@section('page-title', 'Sections')

@section('page-actions')
<a href="#" class="btn btn-primary btn-sm disabled" aria-disabled="true">
    <i class="fas fa-plus"></i> Add Section
</a>
@endsection

@section('admin-content')
<div class="card">
    <div class="card-body">
        <p class="mb-0 text-muted">
            Sections view is ready. Add section list and create/edit controls when routes are finalized.
        </p>
    </div>
</div>
@endsection
