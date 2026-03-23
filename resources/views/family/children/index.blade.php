@extends('layouts.family')

@section('page-title', 'My Children')
@section('title', 'Children')

@section('family-content')
<div class="row">
    @foreach($children as $child)
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">{{ $child->user->name }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Admission No:</strong> {{ $child->admission_number }}</p>
                        <p><strong>Roll Number:</strong> {{ $child->roll_number }}</p>
                        <p><strong>Class:</strong> {{ $child->currentClass->name ?? 'N/A' }}</p>
                        <p><strong>Section:</strong> {{ $child->currentSection->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Date of Birth:</strong> {{ date('d-m-Y', strtotime($child->date_of_birth)) }}</p>
                        <p><strong>Gender:</strong> {{ ucfirst($child->gender) }}</p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-{{ $child->status == 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($child->status) }}
                            </span>
                        </p>
                        <p><strong>Attendance:</strong> 
                            <span class="badge bg-info">{{ $child->attendance_percentage }}%</span>
                        </p>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('family.children.show', $child->id) }}" class="btn btn-info">
                        <i class="fas fa-eye"></i> View Profile
                    </a>
                    <a href="{{ route('family.children.attendance', $child->id) }}" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> View Attendance
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection