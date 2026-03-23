@extends('layouts.student')

@section('page-title', 'My Profile')
@section('title', 'Profile')

@section('student-content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                @if($student->user->profile_photo)
                    <img src="{{ asset('storage/'.$student->user->profile_photo) }}" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                @else
                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                         style="width: 150px; height: 150px;">
                        <i class="fas fa-user-graduate fa-5x text-white"></i>
                    </div>
                @endif
                <h3 class="mt-3">{{ $student->user->name }}</h3>
                <p class="text-muted">Student</p>
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
                        <td>{{ $student->user->name }}</td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td>{{ $student->user->email }}</td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td>{{ $student->user->phone ?? 'Not provided' }}</td>
                    </tr>
                    <tr>
                        <th>Address:</th>
                        <td>{{ $student->user->address ?? 'Not provided' }}</td>
                    </tr>
                    <tr>
                        <th>Admission Number:</th>
                        <td>{{ $student->admission_number }}</td>
                    </tr>
                    <tr>
                        <th>Roll Number:</th>
                        <td>{{ $student->roll_number }}</td>
                    </tr>
                    <tr>
                        <th>Class:</th>
                        <td>{{ $student->currentClass->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Section:</th>
                        <td>{{ $student->currentSection->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Date of Birth:</th>
                        <td>{{ date('d-m-Y', strtotime($student->date_of_birth)) }}</td>
                    </tr>
                    <tr>
                        <th>Gender:</th>
                        <td>{{ ucfirst($student->gender) }}</td>
                    </tr>
                    <tr>
                        <th>Admission Date:</th>
                        <td>{{ date('d-m-Y', strtotime($student->admission_date)) }}</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <span class="badge bg-{{ $student->status == 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($student->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('student.profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $student->user->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ $student->user->phone }}">
                    </div>
                    <div class="mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3">{{ $student->user->address }}</textarea>
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