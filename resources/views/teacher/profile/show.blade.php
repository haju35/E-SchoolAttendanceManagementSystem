@extends('layouts.teacher')

@section('page-title', 'My Profile')
@section('title', 'Profile')

@section('teacher-content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                @if($teacher->user->profile_photo)
                    <img src="{{ asset('storage/'.$teacher->user->profile_photo) }}" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                @else
                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                         style="width: 150px; height: 150px;">
                        <i class="fas fa-user fa-5x text-white"></i>
                    </div>
                @endif
                <h3 class="mt-3">{{ $teacher->user->name }}</h3>
                <p class="text-muted">{{ ucfirst($teacher->user->role) }}</p>
                <p><strong>Employee ID:</strong> {{ $teacher->employee_id }}</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Personal Information</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="200">Full Name:</th>
                        <td>{{ $teacher->user->name }}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>{{ $teacher->user->email }}</td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td>{{ $teacher->user->phone ?? 'Not provided' }}</td>
                    </tr>
                    <tr>
                        <th>Address:</th>
                        <td>{{ $teacher->user->address ?? 'Not provided' }}</td>
                    </tr>
                    <tr>
                        <th>Qualification:</th>
                        <td>{{ $teacher->qualification }}</td>
                    </tr>
                    <tr>
                        <th>Joining Date:</th>
                        <td>{{ date('d-m-Y', strtotime($teacher->joining_date)) }}</td>
                    </tr>
                    <tr>
                        <th>Member Since:</th>
                        <td>{{ $teacher->user->created_at->format('d-m-Y') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Assigned Subjects</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($teacher->assignments as $assignment)
                        <div class="col-md-4 mb-2">
                            <div class="alert alert-info">
                                <strong>{{ $assignment->subject->name }}</strong><br>
                                <small>{{ $assignment->class->name }} - Section {{ $assignment->section->name }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('teacher.profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $teacher->user->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ $teacher->user->phone }}">
                    </div>
                    <div class="mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3">{{ $teacher->user->address }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection