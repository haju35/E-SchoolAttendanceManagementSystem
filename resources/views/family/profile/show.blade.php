@extends('layouts.family')

@section('page-title', 'My Profile')
@section('title', 'Profile')

@section('family-content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                @if($family->user->profile_photo)
                    <img src="{{ asset('storage/'.$family->user->profile_photo) }}" class="rounded-circle img-fluid" style="width: 150px;">
                @else
                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                         style="width: 150px; height: 150px;">
                        <i class="fas fa-users fa-5x text-white"></i>
                    </div>
                @endif
                <h3 class="mt-3">{{ $family->user->name }}</h3>
                <p class="text-muted">Parent/Guardian</p>
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
                        <td>{{ $family->user->name }}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>{{ $family->user->email }}</td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td>{{ $family->user->phone ?? 'Not provided' }}</td>
                    </tr>
                    <tr>
                        <th>Address:</th>
                        <td>{{ $family->user->address ?? 'Not provided' }}</td>
                    </tr>
                    <tr>
                        <th>Occupation:</th>
                        <td>{{ $family->occupation ?? 'Not provided' }}</td>
                    </tr>
                    <tr>
                        <th>Emergency Contact:</th>
                        <td>{{ $family->emergency_contact ?? 'Not provided' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>My Children</h5>
            </div>
            <div class="card-body">
                @foreach($family->students as $child)
                <div class="alert alert-info">
                    <strong>{{ $child->user->name }}</strong><br>
                    Class: {{ $child->currentClass->name ?? 'N/A' }} - Section {{ $child->currentSection->name ?? 'N/A' }}<br>
                    Roll No: {{ $child->roll_number }}
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('family.profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $family->user->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ $family->user->phone }}">
                    </div>
                    <div class="mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3">{{ $family->user->address }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label>Occupation</label>
                        <input type="text" name="occupation" class="form-control" value="{{ $family->occupation }}">
                    </div>
                    <div class="mb-3">
                        <label>Emergency Contact</label>
                        <input type="text" name="emergency_contact" class="form-control" value="{{ $family->emergency_contact }}">
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