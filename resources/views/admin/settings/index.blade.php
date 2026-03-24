@extends('layouts.admin')

@section('title', 'System Configuration')
@section('page-title', 'System Settings')

@section('admin-content')
<div class="row">
    <div class="col-md-6">
        <form action="{{ route('admin.config.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="card">
                <div class="card-header">
                    <h5>School Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>School Name</label>
                        <input type="text" name="school_name" class="form-control" value="{{ config('app.school_name', 'My School') }}">
                    </div>
                    <div class="mb-3">
                        <label>School Address</label>
                        <textarea name="school_address" class="form-control" rows="2">{{ config('app.school_address', '') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label>School Phone</label>
                        <input type="text" name="school_phone" class="form-control" value="{{ config('app.school_phone', '') }}">
                    </div>
                    <div class="mb-3">
                        <label>School Email</label>
                        <input type="email" name="school_email" class="form-control" value="{{ config('app.school_email', '') }}">
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Academic Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Current Academic Year</label>
                        <select name="current_academic_year" class="form-control">
                            @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ $year->is_current ? 'selected' : '' }}>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Current Term</label>
                        <select name="current_term" class="form-control">
                            @foreach($terms as $term)
                            <option value="{{ $term->id }}" {{ $term->is_current ? 'selected' : '' }}>{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Attendance Marking Deadline (Time)</label>
                        <input type="time" name="attendance_cutoff_time" class="form-control" value="{{ config('app.attendance_cutoff_time', '09:00') }}">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="allow_edit_attendance" class="form-check-input" id="allowEdit" {{ config('app.allow_edit_attendance', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="allowEdit">Allow teachers to edit attendance after submission</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Notification Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="enable_sms" class="form-check-input" id="enableSms" {{ config('app.enable_sms', false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enableSms">Enable SMS Notifications</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="enable_email" class="form-check-input" id="enableEmail" {{ config('app.enable_email', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="enableEmail">Enable Email Notifications</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Send Absence Alert After (days)</label>
                        <input type="number" name="absence_alert_days" class="form-control" value="{{ config('app.absence_alert_days', 3) }}" min="1">
                        <small>Send notification after consecutive absent days</small>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5>System Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Timezone</label>
                        <select name="timezone" class="form-control">
                            <option value="UTC" {{ config('app.timezone') == 'UTC' ? 'selected' : '' }}>UTC</option>
                            <option value="Asia/Kolkata" {{ config('app.timezone') == 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata</option>
                            <option value="America/New_York" {{ config('app.timezone') == 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                            <option value="Europe/London" {{ config('app.timezone') == 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Date Format</label>
                        <select name="date_format" class="form-control">
                            <option value="Y-m-d" {{ config('app.date_format') == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                            <option value="d-m-Y" {{ config('app.date_format') == 'd-m-Y' ? 'selected' : '' }}>DD-MM-YYYY</option>
                            <option value="m-d-Y" {{ config('app.date_format') == 'm-d-Y' ? 'selected' : '' }}>MM-DD-YYYY</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mt-3 mb-4">
                <button type="submit" class="btn btn-primary">Save Settings</button>
                <button type="button" class="btn btn-danger" onclick="backupDatabase()">Backup Database</button>
            </div>
        </form>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>System Information</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr><th>Laravel Version</th><td>{{ app()->version() }}</td></tr>
                    <tr><th>PHP Version</th><td>{{ phpversion() }}</td></tr>
                    <tr><th>Server Software</th><td>{{ $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' }}</td></tr>
                    <tr><th>Last Backup</th><td>{{ $lastBackup ?? 'Never' }}</td></tr>
                    <tr><th>Storage Used</th><td>{{ $storageUsed ?? '0 MB' }}</td></tr>
                    <tr><th>Database Size</th><td>{{ $dbSize ?? '0 MB' }}</td></tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <button class="btn btn-warning w-100" onclick="clearCache()">
                            <i class="fas fa-eraser"></i> Clear Cache
                        </button>
                    </div>
                    <div class="col-md-6 mb-2">
                        <button class="btn btn-info w-100" onclick="optimizeDatabase()">
                            <i class="fas fa-database"></i> Optimize Database
                        </button>
                    </div>
                    <div class="col-md-12">
                        <button class="btn btn-danger w-100" onclick="generateReport()">
                            <i class="fas fa-chart-line"></i> Generate System Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function backupDatabase() {
        if(confirm('Create a database backup?')) {
            $.post('/admin/config/backup', {_token: '{{ csrf_token() }}'}, function() {
                alert('Backup created successfully!');
            });
        }
    }
    
    function clearCache() {
        $.post('/admin/config/clear-cache', {_token: '{{ csrf_token() }}'}, function() {
            alert('Cache cleared successfully!');
        });
    }
    
    function optimizeDatabase() {
        $.post('/admin/config/optimize', {_token: '{{ csrf_token() }}'}, function() {
            alert('Database optimized successfully!');
        });
    }
    
    function generateReport() {
        window.open('/admin/config/system-report', '_blank');
    }
</script>
@endpush
@endsection