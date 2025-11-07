@extends('adminlte::page')

@section('title', 'Daftar Website')

@section('content_header')
    <div class="row align-items-center mb-3">
        <div class="col">
            <h1 class="m-0">
                <i class="fas fa-globe text-primary"></i> Daftar Website
            </h1>
        </div>
        <div class="col-auto">
            {{-- Dropdown Button Group --}}
            <div class="btn-group mr-2" role="group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-plus"></i> Tambah Website
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="{{ route('websites.create') }}">
                        <i class="fas fa-keyboard text-primary"></i> Tambah Manual
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('websites.import-form') }}">
                        <i class="fas fa-file-csv text-success"></i> Import CSV
                    </a>
                </div>
            </div>
            
            {{-- Scan All Button --}}
            <form action="{{ route('websites.scanAll') }}" method="POST" style="display:inline;" id="scanAllForm">
                @csrf
                <button type="submit" class="btn btn-info" id="scanAllBtn">
                    <i class="fas fa-sync"></i> Scan Semua
                </button>
            </form>
        </div>
    </div>
@stop

@section('content')
    {{-- Loading Alert --}}
    <div class="alert alert-warning alert-dismissible fade" id="loadingAlert" style="display:none;">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm mr-2" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <strong>Scanning in progress...</strong> Halaman akan auto-refresh setelah selesai.
        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    {{-- Error Message --}}
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    @if ($websites->count())
        <div class="card shadow-sm">
            <div class="card-header bg-primary">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Website Monitoring
                </h3>
                <div class="card-tools">
                    <span class="badge badge-light">{{ $websites->count() }} website</span>
                </div>
            </div>
            
            {{-- Search & Filter --}}
            <div class="card-body border-bottom">
                <div class="row">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" id="searchInput" class="form-control" placeholder="Cari website...">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8 text-right">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="all">
                                <i class="fas fa-globe"></i> Semua
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" data-filter="online">
                                <i class="fas fa-check-circle"></i> Online
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-filter="offline">
                                <i class="fas fa-times-circle"></i> Offline
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning" data-filter="suspicious">
                                <i class="fas fa-exclamation-triangle"></i> Mencurigakan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th>Website</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 120px;">Response</th>
                                <th style="width: 150px;">Konten</th>
                                <th style="width: 140px;">Last Check</th>
                                <th style="width: 220px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="websiteTable">
                            @php $counter = 1; @endphp
                            @foreach ($websites as $website)
                                @if($website->user_id === auth()->id())
                                    <tr data-website-name="{{ strtolower($website->name) }}" 
                                        data-website-url="{{ strtolower($website->url) }}"
                                        data-status="{{ $website->status }}"
                                        data-suspicious="{{ $website->has_suspicious_content ? 'yes' : 'no' }}">
                                        <td class="text-center font-weight-bold">{{ $counter++ }}</td>
                                        <td>
                                            <div>
                                                <strong class="d-block">{{ $website->name }}</strong>
                                                <small class="text-muted">
                                                    <i class="fas fa-link"></i> {{ Str::limit($website->url, 50) }}
                                                </small>
                                                @if (!$website->is_active)
                                                    <span class="badge badge-secondary badge-sm ml-2">Nonaktif</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($website->status === 'checking')
                                                <span class="badge badge-warning badge-pill">
                                                    <i class="fas fa-spinner fa-spin"></i> Checking
                                                </span>
                                            @elseif($website->status === 'online')
                                                <span class="badge badge-success badge-pill">
                                                    <i class="fas fa-check-circle"></i> Online
                                                </span>
                                            @elseif($website->status === 'offline')
                                                <span class="badge badge-danger badge-pill">
                                                    <i class="fas fa-times-circle"></i> Offline
                                                </span>
                                            @else
                                                <span class="badge badge-secondary badge-pill">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($website->response_time)
                                                <span class="badge badge-info">{{ $website->response_time }} ms</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($website->has_suspicious_content)
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> 
                                                    {{ $website->suspicious_posts_count }} post
                                                </span>
                                            @else
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Clean
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($website->last_checked_at)
                                                <small class="text-muted">
                                                    <i class="far fa-clock"></i> {{ $website->last_checked_at->diffForHumans() }}
                                                </small>
                                            @else
                                                <small class="text-muted">Belum dicek</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center" style="gap: 5px;">
                                                <a href="{{ route('websites.show', $website) }}" 
                                                   class="btn btn-info btn-sm" 
                                                   data-toggle="tooltip" 
                                                   title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <form action="{{ route('websites.check', $website) }}" 
                                                      method="POST" 
                                                      style="display:inline; margin: 0;">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-primary btn-sm" 
                                                            data-toggle="tooltip" 
                                                            title="Scan">
                                                        <i class="fas fa-sync"></i>
                                                    </button>
                                                </form>
                                                
                                                <a href="{{ route('websites.edit', $website) }}" 
                                                   class="btn btn-warning btn-sm" 
                                                   data-toggle="tooltip" 
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <form action="{{ route('websites.destroy', $website) }}" 
                                                      method="POST" 
                                                      style="display:inline; margin: 0;" 
                                                      onsubmit="return confirm('Yakin ingin menghapus website ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-danger btn-sm" 
                                                            data-toggle="tooltip" 
                                                            title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer bg-light">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Klik tombol <strong>Scan</strong> untuk update status individual, atau <strong>Scan Semua</strong> untuk bulk scan.
                </small>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-globe fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Belum Ada Website</h4>
                <p class="text-muted">Mulai monitoring dengan menambahkan website pertama Anda.</p>
                <div class="btn-group mt-3" role="group">
                    <a href="{{ route('websites.create') }}" class="btn btn-primary">
                        <i class="fas fa-keyboard"></i> Tambah Manual
                    </a>
                    <a href="{{ route('websites.import-form') }}" class="btn btn-success">
                        <i class="fas fa-file-csv"></i> Import CSV
                    </a>
                </div>
            </div>
        </div>
    @endif
@stop

@section('css')
<style>
    .table tbody tr:hover {
        background-color: #f8f9fa !important;
        cursor: pointer;
    }
    
    .badge-pill {
        padding: 0.35em 0.65em;
    }
    
    /* FLEXBOX GAP SOLUTION */
    .d-flex {
        display: flex;
    }

    .justify-content-center {
        justify-content: center;
    }

    /* Modern gap property */
    [style*="gap: 3px"] {
        gap: 3px;
    }

    /* Fallback for older browsers */
    .d-flex > * {
        margin-right: 3px;
    }

    .d-flex > *:last-child {
        margin-right: 0;
    }

    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .card {
        border-radius: 0.5rem;
    }
    
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.2em;
    }
    
    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }
    
    thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
</style>
@stop

@section('js')
<script>
// Polling for auto-refresh
let scanCheckInterval = null;

function startScanPolling() {
    if (scanCheckInterval) return;
    
    document.getElementById('loadingAlert').style.display = 'block';
    console.log('✓ Polling started');
    
    scanCheckInterval = setInterval(function() {
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newCheckingCount = doc.querySelectorAll('table .badge-warning .fa-spinner').length;
                
                console.log('Current checking count:', newCheckingCount);
                
                if (newCheckingCount === 0) {
                    console.log('✓ Scan selesai! Reloading...');
                    clearInterval(scanCheckInterval);
                    scanCheckInterval = null;
                    location.reload();
                }
            })
            .catch(error => console.error('Fetch error:', error));
    }, 3000);
}

// Search functionality
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#websiteTable tr');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-website-name') || '';
        const url = row.getAttribute('data-website-url') || '';
        
        if (name.includes(searchTerm) || url.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Filter functionality
document.querySelectorAll('[data-filter]').forEach(button => {
    button.addEventListener('click', function() {
        const filter = this.getAttribute('data-filter');
        const rows = document.querySelectorAll('#websiteTable tr');
        
        // Update active button
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.classList.remove('active');
        });
        this.classList.add('active');
        
        // Filter rows
        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            const suspicious = row.getAttribute('data-suspicious');
            
            if (filter === 'all') {
                row.style.display = '';
            } else if (filter === 'suspicious' && suspicious === 'yes') {
                row.style.display = '';
            } else if (filter === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

// Initialize tooltips
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
});

// Auto-start polling on page load
window.addEventListener('load', function() {
    const checkingCount = document.querySelectorAll('table .badge-warning .fa-spinner').length;
    console.log('Page load. Checking count:', checkingCount);
    
    if (checkingCount > 0) {
        startScanPolling();
    }
});

// Start polling on scan all submit
document.getElementById('scanAllForm')?.addEventListener('submit', function(e) {
    console.log('Form submitted, starting polling...');
    setTimeout(() => startScanPolling(), 500);
});
</script>
@stop
