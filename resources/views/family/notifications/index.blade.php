@extends('layouts.family')

@section('page-title', 'Notifications')
@section('title', 'Notifications')

@section('family-content')
<div class="card">
    <div class="card-header">
        <h5>All Notifications</h5>
    </div>
    <div class="card-body">
        @forelse($notifications as $notification)
        <div class="alert alert-{{ $notification->read_at ? 'secondary' : 'primary' }} mb-3">
            <div class="d-flex justify-content-between">
                <h6>{{ $notification->title }}</h6>
                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
            </div>
            <p>{{ $notification->message }}</p>
            @if(!$notification->read_at)
                <a href="{{ route('family.notifications.read', $notification->id) }}" class="btn btn-sm btn-primary">
                    Mark as Read
                </a>
            @endif
        </div>
        @empty
        <p class="text-center">No notifications available.</p>
        @endforelse
        
        {{ $notifications->links() }}
    </div>
</div>
@endsection