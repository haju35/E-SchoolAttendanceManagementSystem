@extends('layouts.admin')

@section('title', 'Bulk Import Students')
@section('page-title', 'Import Students from Excel/CSV')

@section('page-actions')
<a href="{{ route('admin.students.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Back
</a>
@endsection

@section('admin-content')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Upload File</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                    @csrf
                    <div class="mb-3">
                        <label>Select Excel/CSV File</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.xlsx,.xls" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Max file size: 10MB</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="skip_first_row" class="form-check-input" id="skipFirstRow" checked>
                            <label class="form-check-label" for="skipFirstRow">Skip first row (headers)</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="importBtn">
                        <i class="fas fa-upload"></i> Import Students
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Instructions</h5>
            </div>
            <div class="card-body">
                <p>1. Download the template file</p>
                <p>2. Fill in student data following the format</p>
                <p>3. Upload the completed file</p>
                <p>4. Review import results</p>
                
                <a href="{{ route('admin.students.template') }}" class="btn btn-success mt-2">
                    <i class="fas fa-download"></i> Download Template
                </a>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>File Format Requirements</h5>
            </div>
            <div class="card-body">
                <ul>
                    <li>File must be in CSV or Excel format</li>
                    <li>Required columns: name, email, admission_number, date_of_birth, gender, class_name, section_name, admission_date</li>
                    <li>Optional columns: roll_number, phone, address, status, password</li>
                    <li>Default password will be set if not provided</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4" id="importProgress" style="display: none;">
    <div class="card-header">
        <h5>Import Progress</h5>
    </div>
    <div class="card-body">
        <div class="progress mb-3">
            <div id="importProgressBar" class="progress-bar" style="width: 0%">0%</div>
        </div>
        <div id="importLogs" style="max-height: 300px; overflow-y: auto;">
            <div class="alert alert-info">Starting import...</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $('#importForm').submit(function(e) {
        e.preventDefault();
        
        $('#importProgress').show();
        $('#importBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing...');
        
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('#importProgressBar').css('width', percentComplete + '%').text(Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                $('#importProgressBar').css('width', '100%').text('100%');
                $('#importLogs').html('<div class="alert alert-success">' + response.message + '</div>');
                if(response.errors && response.errors.length > 0) {
                    var errorHtml = '<div class="alert alert-warning mt-2">Errors encountered:</div><ul>';
                    response.errors.forEach(function(error) {
                        errorHtml += '<li>Row ' + error.row + ': ' + error.errors.join(', ') + '</li>';
                    });
                    errorHtml += '</ul>';
                    $('#importLogs').append(errorHtml);
                }
                setTimeout(function() {
                    window.location.href = '{{ route("admin.students.index") }}';
                }, 3000);
            },
            error: function(xhr) {
                $('#importLogs').html('<div class="alert alert-danger">Error: ' + xhr.responseJSON.message + '</div>');
                $('#importBtn').prop('disabled', false).html('<i class="fas fa-upload"></i> Import Students');
            }
        });
    });
</script>
@endpush
@endsection