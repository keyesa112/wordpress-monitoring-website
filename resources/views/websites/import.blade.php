@extends('adminlte::page')

@section('title', 'Import Websites dari CSV')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-file-csv text-success"></i> Import Websites dari CSV
        </h1>
        <a href="{{ route('websites.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        {{-- Main Upload Form --}}
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success">
                    <h3 class="card-title">
                        <i class="fas fa-upload"></i> Upload CSV File
                    </h3>
                </div>
                <div class="card-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        @csrf
                        
                        {{-- File Input --}}
                        <div class="form-group">
                            <label for="csv_file">
                                <i class="fas fa-file text-info"></i>
                                Pilih File CSV
                                <span class="text-danger">*</span>
                            </label>
                            <div class="custom-file">
                                <input type="file" 
                                       class="custom-file-input" 
                                       id="csv_file" 
                                       name="csv_file"
                                       accept=".csv" 
                                       required>
                                <label class="custom-file-label" for="csv_file">
                                    Pilih file...
                                </label>
                            </div>
                            <small class="form-text text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i>
                                Format: CSV dengan kolom <strong>Name, URL</strong>
                            </small>
                        </div>
                    </form>
                </div>
                
                <div class="card-footer bg-light">
                    <button type="submit" form="uploadForm" class="btn btn-success btn-lg" id="uploadBtn">
                        <i class="fas fa-sync"></i> Preview Data
                    </button>
                    <a href="{{ route('websites.index') }}" class="btn btn-secondary btn-lg text-white">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </div>

            {{-- Preview Section --}}
            <div id="previewSection" style="display: none;" class="mt-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary">
                        <h3 class="card-title">
                            <i class="fas fa-eye"></i> Preview Data
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-success badge-lg mr-2">
                                <i class="fas fa-check-circle"></i> 
                                <span id="validBadge">0 valid</span>
                            </span>
                            <span class="badge badge-danger badge-lg">
                                <i class="fas fa-exclamation-circle"></i> 
                                <span id="errorBadge">0 error</span>
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        {{-- Valid Data Table --}}
                        <h5 class="mb-3">
                            <i class="fas fa-check-circle text-success"></i> 
                            <strong>Data Valid</strong>
                        </h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover table-striped table-sm" id="validTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Website Name</th>
                                        <th>URL</th>
                                    </tr>
                                </thead>
                                <tbody id="validBody">
                                </tbody>
                            </table>
                        </div>

                        {{-- Error Data Table --}}
                        <div id="errorSection" style="display: none;">
                            <h5 class="mb-2">
                                <i class="fas fa-exclamation-triangle text-danger"></i> 
                                <strong>Data Gagal Validasi</strong>
                            </h5>
                            <div class="alert alert-danger alert-dismissible fade show mb-3">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <ul id="errorList" class="mb-0" style="font-size: 0.9rem;">
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-lg" id="cancelBtn">
                            <i class="fas fa-times"></i> Batal
                        </button>
                        <button type="button" class="btn btn-success btn-lg float-right" id="confirmBtn">
                            <i class="fas fa-check"></i> Confirm & Import
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            {{-- Format Card --}}
            <div class="card shadow-sm">
                <div class="card-header bg-info">
                    <h3 class="card-title">
                        <i class="fas fa-file-alt"></i> Format CSV
                    </h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Format yang benar:</strong>
                    </p>
                    <div style="background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 0.85rem; overflow-x: auto;">
                        <code style="white-space: pre; display: block; line-height: 1.6;">Website A,https://websitea.com
Website B,https://websiteb.com
Website C,https://websitec.com</code>
                    </div>
                </div>
            </div>

            {{-- Download Template Card --}}
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-warning">
                    <h3 class="card-title">
                        <i class="fas fa-download"></i> Template
                    </h3>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted mb-3">
                        Gunakan template kami untuk mempermudah pembuatan CSV
                    </p>
                    <a href="{{ route('websites.download-template') }}" class="btn btn-info btn-block">
                        <i class="fas fa-download"></i> Download Template CSV
                    </a>
                </div>
            </div>

            {{-- Requirements Card --}}
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-secondary">
                    <h3 class="card-title">
                        <i class="fas fa-list-check"></i> Requirements
                    </h3>
                </div>
                <div class="card-body">
                    <ul style="font-size: 0.9rem; padding-left: 1.5rem;">
                        <li class="mb-2">
                            <i class="fas fa-file-csv text-success"></i> 
                            File maksimal <strong>5MB</strong>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-list text-info"></i> 
                            Maksimal <strong>1000 website</strong> per import
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-ban text-warning"></i> 
                            <strong>Duplikat</strong> akan di-skip otomatis
                        </li>
                        <li>
                            <i class="fas fa-link text-primary"></i> 
                            URL harus valid dengan <strong>http/https</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .card {
        border-radius: 0.5rem;
        border: none;
    }

    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }

    /* FONT SIZES - Konsisten */
    label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .badge-lg {
        font-size: 0.85rem;
        padding: 0.35rem 0.6rem;
    }

    small, .form-text {
        font-size: 0.8rem;
    }

    .alert {
        font-size: 0.9rem;
    }

    .card-title {
        font-size: 1rem;
    }

    .content-header h1 {
        font-size: 1.5rem;
    }

    /* FILE INPUT CUSTOM */
    .custom-file {
        position: relative;
        display: inline-block;
        width: 100%;
        height: calc(1.5em + 0.75rem + 2px);
        margin-bottom: 0;
    }

    .custom-file-input {
        position: relative;
        z-index: 2;
        width: 100%;
        height: calc(1.5em + 0.75rem + 2px);
        margin: 0;
        opacity: 0;
        cursor: pointer;
    }

    .custom-file-label {
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        z-index: 1;
        height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 0.75rem;
        font-weight: 400;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        display: flex;
        align-items: center;
    }

    .custom-file-input:focus ~ .custom-file-label {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .custom-file-input:lang(en) ~ .custom-file-label::after {
        content: "Pilih file...";
    }

    /* BUTTON SIZING */
    .btn-lg {
        font-size: 0.95rem;
        padding: 0.5rem 1rem;
    }

    .btn-block {
        font-size: 0.9rem;
    }

    /* TABLE STYLING */
    .table-hover tbody tr:hover {
        background-color: #f8f9fa !important;
        cursor: pointer;
    }

    .table-sm th,
    .table-sm td {
        font-size: 0.9rem;
    }

    /* CODE BLOCK */
    code {
        color: #d63384;
        word-break: break-word;
    }

    /* FLEXBOX */
    .d-flex {
        display: flex;
    }

    .justify-content-between {
        justify-content: space-between;
    }

    .align-items-center {
        align-items: center;
    }

    .mr-2 {
        margin-right: 0.5rem !important;
    }

    .mb-3 {
        margin-bottom: 1rem !important;
    }

    .mb-4 {
        margin-bottom: 1.5rem !important;
    }

    .mt-2 {
        margin-top: 0.5rem !important;
    }

    .mt-3 {
        margin-top: 1rem !important;
    }

    .float-right {
        float: right;
    }
</style>
@stop

@section('js')
<script>
let previewData = [];

// File input label update
document.getElementById('csv_file').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || 'Pilih file...';
    const label = document.querySelector('.custom-file-label');
    label.textContent = fileName;
});

// Upload form submit
document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(document.getElementById('uploadForm'));
    const uploadBtn = document.getElementById('uploadBtn');
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    try {
        const response = await fetch("{{ route('websites.import-preview') }}", {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            previewData = result.data;
            displayPreview(result);
            document.getElementById('previewSection').style.display = 'block';
            document.querySelector('html, body').scrollTop = 0;
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error uploading file: ' + error.message);
    } finally {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-sync"></i> Preview Data';
    }
});

// Display preview
function displayPreview(result) {
    const validBody = document.getElementById('validBody');
    validBody.innerHTML = '';
    
    result.data.forEach((item, idx) => {
        const row = `
            <tr>
                <td class="text-center font-weight-bold">${idx + 1}</td>
                <td><strong>${item.name}</strong></td>
                <td><small class="text-muted"><i class="fas fa-link"></i> ${item.url}</small></td>
            </tr>
        `;
        validBody.innerHTML += row;
    });
    
    const errorList = document.getElementById('errorList');
    const errorSection = document.getElementById('errorSection');
    
    if (result.errors.length > 0) {
        errorList.innerHTML = '';
        result.errors.forEach(err => {
            errorList.innerHTML += `<li>${err}</li>`;
        });
        errorSection.style.display = 'block';
    } else {
        errorSection.style.display = 'none';
    }
    
    document.getElementById('validBadge').textContent = result.valid_count + ' valid';
    document.getElementById('errorBadge').textContent = result.error_count + ' error';
}

// Cancel preview
document.getElementById('cancelBtn').addEventListener('click', () => {
    document.getElementById('previewSection').style.display = 'none';
    document.getElementById('uploadForm').reset();
    document.querySelector('.custom-file-label').textContent = 'Pilih file...';
    previewData = [];
});

// Confirm import
document.getElementById('confirmBtn').addEventListener('click', async () => {
    if (previewData.length === 0) {
        alert('Tidak ada data untuk di-import');
        return;
    }
    
    const websites = previewData.map(item => ({
        name: item.name,
        url: item.url
    }));
    
    const confirmBtn = document.getElementById('confirmBtn');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';
    
    try {
        const response = await fetch("{{ route('websites.import-store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ websites: websites })
        });
        
        if (response.ok) {
            window.location.href = "{{ route('websites.index') }}";
        } else {
            const error = await response.json();
            alert('Error: ' + (error.message || 'Import gagal'));
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> Confirm & Import';
        }
    } catch (error) {
        alert('Error importing: ' + error.message);
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-check"></i> Confirm & Import';
    }
});
</script>
@stop
