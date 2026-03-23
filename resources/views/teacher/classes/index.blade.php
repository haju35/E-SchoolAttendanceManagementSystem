@extends('layouts.teacher')

@section('page-title', 'My Classes')
@section('title', 'Classes')

@section('teacher-content')
<div class="row">
    @foreach($classes as $class)
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">{{ $class->name }}</h5>
            </div>
            <div class="card-body">
                <p><strong>Sections:</strong></p>
                <div class="list-group">
                    @foreach($class->sections as $section)
                        <a href="{{ route('teacher.classes.students', ['id' => $class->id, 'section_id' => $section->id]) }}" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-layer-group"></i> Section {{ $section->name }}
                                    <small class="text-muted">({{ $section->students->count() }} students)</small>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection