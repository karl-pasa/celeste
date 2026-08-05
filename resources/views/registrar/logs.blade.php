@extends('layouts.app')

@section('title', 'Audit trail')
@section('subtitle', 'Every generation, download, revocation, and sign-in recorded by CELESTE')

@section('content')
<div class="card-celeste">
    <div class="table-responsive">
        <table class="table table-celeste">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>By</th>
                    <th>Subject</th>
                    <th>Details</th>
                    <th>IP</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($audits as $audit)
                    <tr>
                        <td><span class="badge-celeste badge-type">{{ $audit->action }}</span></td>
                        <td>{{ $audit->user?->name ?? 'Guest' }}</td>
                        <td class="text-muted-celeste">{{ class_basename($audit->subject_type) }} #{{ $audit->subject_id }}</td>
                        <td class="text-muted-celeste" style="font-size:.8125rem;max-width:280px">
                            @if ($audit->context)
                                {{ collect($audit->context)->map(fn ($v, $k) => "$k: " . (is_array($v) ? json_encode($v) : $v))->implode(' · ') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-muted-celeste">{{ $audit->ip_address }}</td>
                        <td class="text-muted-celeste">{{ $audit->created_at->format('M j, Y g:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">
                        <div class="empty">
                            <div class="empty-icon"><i class="bi bi-journal-text"></i></div>
                            <h6>The trail is empty</h6>
                            <p>Actions taken in CELESTE will be recorded here.</p>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top">{{ $audits->links() }}</div>
</div>
@endsection
