@extends('layouts.admin')

@section('title', 'User Management')
@section('page-title', 'Manage Users')

@section('page-actions')
<div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New User
    </a>
</div>
@endsection

@section('admin-content')
<div class="card">
    <div class="card-body">
        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-3">
                <select id="roleFilter" class="form-control">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="teacher">Teacher</option>
                    <option value="student">Student</option>
                    <option value="family">Parent</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="statusFilter" class="form-control">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-6">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by name, email...">
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered" id="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : ($user->role == 'teacher' ? 'primary' : ($user->role == 'student' ? 'success' : 'info')) }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>{{ $user->phone ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewUser({{ $user->id }})" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="editUser({{ $user->id }})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="resetPassword({{ $user->id }})" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="toggleStatus({{ $user->id }})" title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                <i class="fas fa-{{ $user->is_active ? 'ban' : 'check' }}"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{ $users->links() }}
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetails">
                Loading...
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="resetPasswordForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reset User Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentUserId = null;
    
    function viewUser(id) {
        $.get('/admin/users/' + id, function(data) {
            $('#userDetails').html(`
                <table class="table">
                    <tr><th width="150">Name:</th><td>${data.user.name}</td></tr>
                    <tr><th>Email:</th><td>${data.user.email}</td></tr>
                    <tr><th>Role:</th><td>${data.user.role}</td></tr>
                    <tr><th>Phone:</th><td>${data.user.phone || 'N/A'}</td></tr>
                    <tr><th>Address:</th><td>${data.user.address || 'N/A'}</td></tr>
                    <tr><th>Status:</th><td>${data.user.is_active ? 'Active' : 'Inactive'}</td></tr>
                    <tr><th>Joined:</th><td>${new Date(data.user.created_at).toLocaleDateString()}</td></tr>
                    <tr><th>Last Login:</th><td>${data.user.last_login_at ? new Date(data.user.last_login_at).toLocaleString() : 'Never'}</td></tr>
                </table>
                ${data.profile ? `<h6 class="mt-3">${data.user.role.toUpperCase()} Profile</h6>
                <table class="table">${Object.entries(data.profile).map(([k,v]) => `<tr><th>${k}:</th><td>${v || 'N/A'}</td></tr>`).join('')}</table>` : ''}
            `);
            new bootstrap.Modal(document.getElementById('viewUserModal')).show();
        });
    }
    
    function resetPassword(id) {
        currentUserId = id;
        $('#resetPasswordForm').attr('action', '/admin/users/' + id + '/reset-password');
        new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
    }
    
    function toggleStatus(id) {
        if(confirm('Are you sure you want to change user status?')) {
            $.post('/admin/users/' + id + '/toggle-status', {
                _token: '{{ csrf_token() }}'
            }, function() {
                location.reload();
            });
        }
    }
    
    $('#roleFilter, #statusFilter, #searchInput').on('change keyup', function() {
        let role = $('#roleFilter').val();
        let status = $('#statusFilter').val();
        let search = $('#searchInput').val();
        window.location.href = '{{ route("admin.users.index") }}?role=' + role + '&status=' + status + '&search=' + search;
    });
</script>
@endpush
@endsection