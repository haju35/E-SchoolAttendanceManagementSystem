@extends('layouts.family')

@section('page-title', 'Family Dashboard')
@section('title', 'Dashboard')

@section('family-content')
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">My Children</h5>
            </div>
            <div class="card-body">
                @foreach($children as $child)
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5>{{ $child->user->name }}</h5>
                                <p class="mb-1">
                                    <strong>Class:</strong> {{ $child->currentClass->name ?? 'N/A' }} - 
                                    Section {{ $child->currentSection->name ?? 'N/A' }}
                                </p>
                                <p class="mb-1">
                                    <strong>Roll No:</strong> {{ $child->roll_number }}
                                </p>
                                <p class="mb-1">
                                    <strong>Attendance:</strong> 
                                    <span class="badge bg-success">{{ $child->attendance_percentage }}%</span>
                                </p>
                                <p class="mb-0">
                                    <strong>Today's Status:</strong>
                                    @if($child->today_status == 'present')
                                        <span class="badge bg-success">Present</span>
                                    @elseif($child->today_status == 'absent')
                                        <span class="badge bg-danger">Absent</span>
                                    @elseif($child->today_status == 'late')
                                        <span class="badge bg-warning">Late</span>
                                    @else
                                        <span class="badge bg-secondary">Not Marked</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('family.children.show', $child->id) }}" class="btn btn-sm btn-info">
                                    View Details
                                </a>
                                <a href="{{ route('family.children.attendance', $child->id) }}" class="btn btn-sm btn-primary mt-2">
                                    View Attendance
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Recent Notifications</h5>
            </div>
            <div class="card-body">
                @forelse($notifications as $notification)
                <div class="alert alert-{{ $notification->read_at ? 'secondary' : 'primary' }}">
                    <h6>{{ $notification->title }}</h6>
                    <p class="mb-1">{{ $notification->message }}</p>
                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                    @if(!$notification->read_at)
                        <a href="{{ route('family.notifications.read', $notification->id) }}" class="float-end btn btn-sm btn-link">
                            Mark as Read
                        </a>
                    @endif
                </div>
                @empty
                <p class="text-center">No notifications yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection