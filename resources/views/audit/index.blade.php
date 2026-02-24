@extends('layouts.app')

@section('title', 'Journal d\'Audit')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <form method="GET" class="card card-body">
            <div class="row g-2">
                <div class="col-md-4"><input class="form-control" name="action" value="{{ request('action') }}" placeholder="Filtre action"></div>
                <div class="col-md-4"><input class="form-control" name="entity_type" value="{{ request('entity_type') }}" placeholder="Filtre entité"></div>
                <div class="col-md-4"><button class="btn btn-primary w-100">Filtrer</button></div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Entité</th>
                    <th>Détails</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? 'Système' }}</td>
                    <td><code>{{ $log->action }}</code></td>
                    <td>{{ class_basename($log->entity_type) }} #{{ $log->entity_id }}</td>
                    <td><small>{{ json_encode($log->details ?? [], JSON_UNESCAPED_UNICODE) }}</small></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-muted">Aucun log</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $logs->links() }}
    </div>
</div>
@endsection
