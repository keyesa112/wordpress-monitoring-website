berarti ini harus pakai websocket ya?

@extends('adminlte::page')


@section('title', 'Daftar Website')


@section('content_header')
    <div class="row align-items-center">
        <div class="col">
            <h1>Daftar Website</h1>
        </div>
        <div class="col-auto">
            <a href="{{ route('websites.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Website
            </a>
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
    <!-- Loading Alert (Hidden by default) -->
    <div class="alert alert-warning alert-dismissible fade" id="loadingAlert" style="display:none;">
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <span id="loadingText">Sedang melakukan scan pada semua website... Silakan tunggu!</span>
        </div>
    </div>


    @if ($websites->count())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Website Monitoring</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr style="background-color: #f8f9fa;">
                            <th style="width: 40px;">#</th>
                            <th>Website</th>
                            <th>Status</th>
                            <th>Response Time</th>
                            <th>Konten Mencurigakan</th>
                            <th>Terakhir Dicek</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $counter = 1; @endphp
                        @foreach ($websites as $website)
                            @if($website->user_id === auth()->id())
                                <tr style="background-color: #ffffff; border-bottom: 1px solid #dee2e6;">
                                    <td style="text-align: center; font-weight: bold;">{{ $counter++ }}</td>
                                    <td>
                                        <strong>{{ $website->name }}</strong><br>
                                        <small class="text-muted">{{ $website->url }}</small>
                                        @if (!$website->is_active)
                                            <span class="badge badge-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($website->status === 'checking')
                                            <span class="badge badge-warning">
                                                <i class="fas fa-spinner fa-spin"></i> Checking...
                                            </span>
                                        @elseif($website->status === 'online')
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i> Online
                                            </span>
                                        @elseif($website->status === 'offline')
                                            <span class="badge badge-danger">
                                                <i class="fas fa-times-circle"></i> Offline
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">Unknown</span>
                                        @endif
                                    </td>
                                    <td>{{ $website->formatted_response_time ?? '-' }}</td>
                                    <td>{!! $website->suspicious_badge !!}</td>
                                    <td>
                                        @if ($website->last_checked_at)
                                            <small>{{ $website->last_checked_at->diffForHumans() }}</small>
                                        @else
                                            <small class="text-muted">Belum dicek</small>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('websites.show', $website) }}" class="btn btn-info btn-sm" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('websites.check', $website) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm" title="Check Website">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('websites.edit', $website) }}" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('websites.destroy', $website) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-info" role="alert">
            <h4 class="alert-heading">
                <i class="fas fa-info-circle"></i> Belum Ada Website
            </h4>
            <p>Tidak ada website yang ditambahkan. Mulai dengan menambahkan website baru.</p>
            <hr>
            <a href="{{ route('websites.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Website Pertama
            </a>
        </div>
    @endif
@stop


@section('css')
    <style>
        .table {
            margin-bottom: 0;
        }
        
        .table tbody tr:hover {
            background-color: #f5f5f5 !important;
        }
        
        .btn-group-vertical .btn {
            margin-bottom: 5px;
        }


        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }


        .me-2 {
            margin-right: 0.5rem;
        }


        .d-flex {
            display: flex;
        }


        .align-items-center {
            align-items: center;
        }
    </style>
@stop


@section('js')
<script>
let scanCheckInterval = null;

function startScanPolling() {
    if (scanCheckInterval) return;
    
    console.log('✓ Polling started');
    
    scanCheckInterval = setInterval(function() {
        // FETCH HTML terbaru dari server
        fetch(window.location.href)
            .then(response => response.text())
            .then(html => {
                // Parse HTML, hitung spinner baru
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newCheckingCount = doc.querySelectorAll('table .badge-warning .fa-spinner').length;
                
                console.log('Current checking count:', newCheckingCount);
                
                // Jika tidak ada spinner = semua selesai
                if (newCheckingCount === 0) {
                    console.log('✓ Scan selesai! No more spinners. Reloading...');
                    clearInterval(scanCheckInterval);
                    scanCheckInterval = null;
                    location.reload();
                }
            })
            .catch(error => console.error('Fetch error:', error));
    }, 3000);
}

window.addEventListener('load', function() {
    // Cek apakah ada spinner di halaman awal
    const checkingCount = document.querySelectorAll('table .badge-warning .fa-spinner').length;
    console.log('Page load. Checking count:', checkingCount);
    
    if (checkingCount > 0) {
        startScanPolling();
    }
});

const form = document.getElementById('scanAllForm');
if (form) {
    form.addEventListener('submit', function(e) {
        console.log('Form submitted, starting polling...');
        setTimeout(() => startScanPolling(), 500);
    });
}
</script>
@stop


