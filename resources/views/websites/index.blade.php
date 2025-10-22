@extends('adminlte::page')

@section('title', 'Daftar Website')

@section('content_header')
    <h1>Daftar Website</h1>
@stop

@section('content')
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Kelola Website Monitoring</h3>
            <div class="card-tools">
                <a href="{{ route('websites.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tambah Website
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 10px">#</th>
                        <th>Website</th>
                        <th>Status</th>
                        <th>Response Time</th>
                        <th>Konten Mencurigakan</th>
                        <th>Terakhir Dicek</th>
                        <th style="width: 200px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($websites as $website)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $website->name }}</strong><br>
                            <small class="text-muted">{{ $website->url }}</small>
                            @if(!$website->is_active)
                                <br><span class="badge badge-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>{!! $website->status_badge !!}</td>
                        <td>{{ $website->formatted_response_time }}</td>
                        <td>{!! $website->suspicious_badge !!}</td>
                        <td>
                            <small>{{ $website->last_checked_human }}</small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('websites.show', $website) }}" 
                                   class="btn btn-info" 
                                   title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('websites.edit', $website) }}" 
                                   class="btn btn-warning"
                                   title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('websites.check', $website) }}" 
                                      method="POST" 
                                      style="display:inline;">
                                    @csrf
                                    <button type="submit" 
                                            class="btn btn-success"
                                            title="Cek Ulang">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </form>
                                <form action="{{ route('websites.destroy', $website) }}" 
                                      method="POST" 
                                      style="display:inline;"
                                      onsubmit="return confirm('Yakin ingin menghapus website ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="btn btn-danger"
                                            title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            <p class="mt-3 mb-3">Belum ada website yang ditambahkan</p>
                            <a href="{{ route('websites.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Website Pertama
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop