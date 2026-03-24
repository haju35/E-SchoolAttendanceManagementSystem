@extends('layouts.admin')

@section('title', 'Student Management')
@section('page-title', 'Manage Students')

@section('page-actions')
<div class="btn-group">
    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
        <i class="fas fa-plus"></i> Add Student
    </button>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="{{ route('admin.students.create') }}">Add Single Student</a></li>
        <li><a class="dropdown-item" href="{{ route('admin.students.import.form') }}">Bulk Import (Excel/CSV)</a></li>
    </ul>
</div>
<a href="{{ route('admin.students.export') }}" class="btn btn-success">
    <i class="fas fa-download"></i> Export
</a>
@endsection

@section('admin-content')
<div class="card">
    <div class="card-body">
        <!-- Advanced Filters -->
        <div class="row mb-4">
            <div class="col-md-3">
                <label>Class</label>
                <select id="classFilter" class="form-control">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Section</label>
                <select id="sectionFilter" class="form-control">
                    <option value="">All Sections</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Status</label>
                <select id="statusFilter" class="form-control">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="graduated">Graduated</option>
                    <option value="transferred">Transferred</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Search</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Name, Admission No, Roll No...">
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="students-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Admission No</th>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Parent</th>
                        <th>Attendance %</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                    <tr>
                        <td><input type="checkbox" class="student-checkbox" value="{{ $student->id }}"></td>
                        <td>{{ $student->admission_number }}</td>
                        <td>{{ $student->roll_number }}</td>
                        <td>
                            <strong>{{ $student->user->name }}</strong><br>
                            <small class="text-muted">{{ $student->user->email }}</small>
                        </td>
                        <td>{{ $student->currentClass->name ?? 'N/A' }}</td>
                        <td>{{ $student->currentSection->name ?? 'N/A' }}</td>
                        <td>
                            @if($student->family)
                                {{ $student->family->user->name }}<br>
                                <small>{{ $student->family->user->phone }}</small>
                            @else
                                <span class="text-muted">Not assigned</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $total = $student->attendances->count();
                                $present = $student->attendances->where('status', 'present')->count();
                                $percentage = $total > 0 ? round(($present/$total)*100, 2) : 0;
                                $badgeClass = $percentage >= 75 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
                            @endphp
                            <div class="progress" style="height: 5px; margin-bottom: 5px;">
                                <div class="progress-bar bg-{{ $badgeClass }}" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="badge bg-{{ $badgeClass }}">{{ $percentage }}%</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $student->status == 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($student->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-info" onclick="viewStudent({{ $student->id }})" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="{{ route('admin.students.edit', $student->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-primary" onclick="markAttendance({{ $student->id }})" title="Mark Attendance">
                                    <i class="fas fa-calendar-check"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteStudent({{ $student->id }})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <button class="btn btn-danger" onclick="bulkDelete()" disabled id="bulkDeleteBtn">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
                <button class="btn btn-success" onclick="bulkPromote()" disabled id="bulkPromoteBtn">
                    <i class="fas fa-arrow-up"></i> Promote to Next Class
                </button>
            </div>
            <div class="col-md-6">
                {{ $students->links() }}
            </div>
        </div>
    </div>
</div>

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="studentDetails">
                Loading...
            </div>
        </div>
    </div>
</div>

<!-- Quick Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="attendanceForm">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="student_id" id="attendanceStudentId">
                    <div class="mb-3">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label>Subject</label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Remarks</label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="Reason for absence/late..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let selectedStudents = [];
    
    $(document).ready(function() {
        $('#students-table').DataTable({
            pageLength: 25,
            order: [[1, 'asc']],
            searching: false,
            paging: false,
            info: false
        });
        
        $('#selectAll').change(function() {
            $('.student-checkbox').prop('checked', $(this).prop('checked'));
            updateBulkButtons();
        });
        
        $('.student-checkbox').change(function() {
            updateBulkButtons();
        });
        
        $('#classFilter').change(function() {
            var classId = $(this).val();
            if(classId) {
                $.ajax({
                    url: '/admin/sections/by-class/' + classId,
                    success: function(data) {
                        $('#sectionFilter').empty().append('<option value="">All Sections</option>');
                        $.each(data, function(key, section) {
                            $('#sectionFilter').append('<option value="'+ section.id +'">'+ section.name +'</option>');
                        });
                    }
                });
            }
            applyFilters();
        });
        
        $('#sectionFilter, #statusFilter, #searchInput').on('change keyup', function() {
            applyFilters();
        });
    });
    
    function updateBulkButtons() {
        selectedStudents = [];
        $('.student-checkbox:checked').each(function() {
            selectedStudents.push($(this).val());
        });
        
        $('#bulkDeleteBtn, #bulkPromoteBtn').prop('disabled', selectedStudents.length === 0);
    }
    
    function applyFilters() {
        let classId = $('#classFilter').val();
        let sectionId = $('#sectionFilter').val();
        let status = $('#statusFilter').val();
        let search = $('#searchInput').val();
        
        window.location.href = '{{ route("admin.students.index") }}?class=' + classId + '&section=' + sectionId + '&status=' + status + '&search=' + search;
    }
    
    function viewStudent(id) {
        $.get('/admin/students/' + id, function(data) {
            let attendanceHtml = '';
            if(data.attendances && data.attendances.length > 0) {
                attendanceHtml = '<h6 class="mt-3">Recent Attendance</h6><table class="table table-sm"> <thead><tr><th>Date</th><th>Subject</th><th>Status</th><th>Remarks</th></tr></thead><tbody>';
                data.attendances.forEach(att => {
                    attendanceHtml += `<tr>
                        <td>${new Date(att.date).toLocaleDateString()}</td>
                        <td>${att.subject?.name || 'N/A'}</td>
                        <td><span class="badge bg-${att.status == 'present' ? 'success' : (att.status == 'absent' ? 'danger' : 'warning')}">${att.status}</span></td>
                        <td>${att.reason || '-'}</td>
                    </tr>`;
                });
                attendanceHtml += '</tbody></table>';
            }
            
            $('#studentDetails').html(`
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                    <i class="fas fa-user-graduate fa-4x text-white"></i>
                                </div>
                                <h5 class="mt-3">${data.user.name}</h5>
                                <p class="text-muted">${data.admission_number}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header"><strong>Personal Information</strong></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6"><strong>Email:</strong> ${data.user.email}</div>
                                    <div class="col-md-6"><strong>Phone:</strong> ${data.user.phone || 'N/A'}</div>
                                    <div class="col-md-6"><strong>Date of Birth:</strong> ${new Date(data.date_of_birth).toLocaleDateString()}</div>
                                    <div class="col-md-6"><strong>Gender:</strong> ${data.gender}</div>
                                    <div class="col-md-6"><strong>Class:</strong> ${data.current_class?.name || 'N/A'}</div>
                                    <div class="col-md-6"><strong>Section:</strong> ${data.current_section?.name || 'N/A'}</div>
                                    <div class="col-md-6"><strong>Roll Number:</strong> ${data.roll_number || 'N/A'}</div>
                                    <div class="col-md-6"><strong>Admission Date:</strong> ${new Date(data.admission_date).toLocaleDateString()}</div>
                                    <div class="col-md-12"><strong>Address:</strong> ${data.user.address || 'N/A'}</div>
                                </div>
                            </div>
                        </div>
                        ${attendanceHtml}
                    </div>
                </div>
            `);
            new bootstrap.Modal(document.getElementById('viewStudentModal')).show();
        });
    }
    
    function markAttendance(id) {
        $('#attendanceStudentId').val(id);
        new bootstrap.Modal(document.getElementById('attendanceModal')).show();
    }
    
    function deleteStudent(id) {
        if(confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
            $.ajax({
                url: '/admin/students/' + id,
                type: 'DELETE',
                data: {_token: '{{ csrf_token() }}'},
                success: function() {
                    location.reload();
                }
            });
        }
    }
    
    function bulkDelete() {
        if(confirm('Delete ' + selectedStudents.length + ' students?')) {
            $.ajax({
                url: '/admin/students/bulk-delete',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    students: selectedStudents
                },
                success: function() {
                    location.reload();
                }
            });
        }
    }
    
    function bulkPromote() {
        if(confirm('Promote ' + selectedStudents.length + ' students to next class?')) {
            $.ajax({
                url: '/admin/students/bulk-promote',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    students: selectedStudents
                },
                success: function() {
                    location.reload();
                }
            });
        }
    }
    
    $('#attendanceForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: '/teacher/attendance',
            type: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function() {
                alert('Attendance marked successfully!');
                bootstrap.Modal.getInstance(document.getElementById('attendanceModal')).hide();
            },
            error: function() {
                alert('Error marking attendance');
            }
        });
    });
</script>
@endpush
@endsection