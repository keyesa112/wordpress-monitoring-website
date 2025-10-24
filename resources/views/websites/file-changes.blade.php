@extends('adminlte::page')

@section('title', 'File Changes - ' . $website->name)

@section('content_header')
    <h1>File Changes History</h1>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('websites.index') }}">Websites</a></li>
        <li class="breadcrumb-item"><a href="{{ route('websites.show', $website) }}">{{ $website->name }}</a></li>
        <li class="breadcrumb-item active">File Changes</li>
    </ol>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-code"></i> File Changes for {{ $website->name }}
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-danger">{{ $suspiciousCount }} Suspicious</span>
                        <span class="badge badge-secondary">{{ $changes->total() }} Total</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th style="width: 40%">File Path</th>
                                <th style="width: 10%">Change Type</th>
                                <th style="width: 10%">Severity</th>
                                <th style="width: 10%">Status</th>
                                <th style="width: 20%">Detected At</th>
                                <th style="width: 10%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($changes as $change)
                            <tr class="{{ $change->is_suspicious ? 'table-danger' : '' }}">
                                <td>
                                    <small><code>{{ Str::limit($change->file_path, 60) }}</code></small>
                                    @if($change->is_suspicious)
                                        <i class="fas fa-exclamation-triangle text-danger ml-1"></i>
                                    @endif
                                </td>
                                <td>{!! $change->change_type_badge !!}</td>
                                <td>{!! $change->severity_badge !!}</td>
                                <td>
                                    @if($change->is_suspicious)
                                        <span class="badge badge-danger">Suspicious</span>
                                    @else
                                        <span class="badge badge-success">Clean</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $change->created_at->format('d M Y H:i') }}</small><br>
                                    <small class="text-muted">{{ $change->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <button class="btn btn-xs btn-info" 
                                            data-toggle="modal" 
                                            data-target="#changeModal{{ $change->id }}">
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No file changes detected yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($changes->hasPages())
                <div class="card-footer">
                    {{ $changes->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modals --}}
    @foreach($changes as $change)
    <div class="modal fade" id="changeModal{{ $change->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-{{ $change->severity === 'critical' ? 'danger' : ($change->severity === 'warning' ? 'warning' : 'info') }}">
                    <h5 class="modal-title">File Change Details</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">File Path:</dt>
                        <dd class="col-sm-9"><code>{{ $change->file_path }}</code></dd>
                        
                        <dt class="col-sm-3">Change Type:</dt>
                        <dd class="col-sm-9">{!! $change->change_type_badge !!}</dd>
                        
                        <dt class="col-sm-3">Severity:</dt>
                        <dd class="col-sm-9">{!! $change->severity_badge !!}</dd>
                        
                        <dt class="col-sm-3">Suspicious:</dt>
                        <dd class="col-sm-9">
                            @if($change->is_suspicious)
                                <span class="badge badge-danger">YES</span>
                            @else
                                <span class="badge badge-success">NO</span>
                            @endif
                        </dd>
                        
                        @if($change->suspicious_patterns)
                        <dt class="col-sm-3">Patterns:</dt>
                        <dd class="col-sm-9">
                            @foreach($change->suspicious_patterns as $pattern)
                                <span class="badge badge-danger mr-1 mb-1">{{ $pattern }}</span>
                            @endforeach
                        </dd>
                        @endif
                        
                        <dt class="col-sm-3">Recommendation:</dt>
                        <dd class="col-sm-9">{{ $change->recommendation }}</dd>
                        
                        @if($change->file_preview)
                        <dt class="col-sm-3">Preview:</dt>
                        <dd class="col-sm-9">
                            <pre class="bg-light p-2 border" style="max-height: 300px; overflow-y: auto; font-size: 0.85rem;">{{ $change->file_preview }}</pre>
                        </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
    @endforeach
@stop
