@extends('adminlte::page')

@section('title', 'Daftar Website')

@section('content_header')
    <h1>
        Daftar Website
        <a href="{{ route('websites.create') }}" class="btn btn-primary float-right">
            <i class="fas fa-plus"></i> Tambah Website
        </a>
    </h1>
@stop

@section('content')
    @if ($websites->count())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Website Monitoring</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Website</th>
                            <th>Status</th>
                            <th>Response Time</th>
                            <th>Konten Mencurigakan</th>
                            <th>Terakhir Dicek</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($websites as $website)
                            {{-- Only show if user owns it --}}
                            @if($website->user_id === auth()->id())
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $website->name }}</strong><br>
                                        <small class="text-muted">{{ $website->url }}</small>
                                        @if (!$website->is_active)
                                            <span class="badge badge-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>{!! $website->status_badge !!}</td>
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
                                        <!-- View Button -->
                                        <a href="{{ route('websites.show', $website) }}" class="btn btn-info btn-sm" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- Check Button (POST) -->
                                        <form action="{{ route('websites.check', $website) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm" title="Check Website">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </form>

                                        <!-- Edit Button -->
                                        <a href="{{ route('websites.edit', $website) }}" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <!-- Delete Button -->
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
        .btn-group-vertical .btn {
            margin-bottom: 5px;
        }
    </style>
@stop
