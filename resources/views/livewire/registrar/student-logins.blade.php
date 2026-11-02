<div>
    @if ($notice)
        <div class="alert alert-{{ $noticeType }} d-flex justify-content-between align-items-start gap-3 py-2 px-3 small" role="alert">
            <span>{{ $notice }}</span>
            <button type="button" class="btn-close" style="font-size:.7rem" aria-label="Close" wire:click="$set('notice', null)"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-3">
                    <label class="form-label small mb-1">Search</label>
                    <input type="search" class="form-control" placeholder="Name or student number"
                           wire:model.live.debounce.400ms="search">
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label small mb-1">College</label>
                    <select class="form-select" wire:model.live="college">
                        <option value="">All colleges</option>
                        @foreach ($colleges as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label small mb-1">Program</label>
                    <select class="form-select" wire:model.live="program" @disabled($college === '')>
                        <option value="">{{ $college === '' ? 'Choose a college first' : 'All programs' }}</option>
                        @foreach ($programs as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-8 col-lg-2">
                    <label class="form-label small mb-1">Account</label>
                    <select class="form-select" wire:model.live="status">
                        <option value="">All</option>
                        <option value="none">No account yet</option>
                        <option value="has">Has an account</option>
                    </select>
                </div>
                <div class="col-4 col-lg-1">
                    <button type="button" class="btn btn-psu-outline w-100" wire:click="clearFilters">Clear</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="small text-muted-celeste">
            {{ number_format($rows->total()) }} {{ $rows->total() === 1 ? 'student' : 'students' }} in this list ·
            {{ number_format($withoutLogin) }} without an account
        </div>
        <button type="button" class="btn btn-psu btn-sm"
                wire:click="createBatch" wire:loading.attr="disabled" wire:target="createBatch"
                @disabled($withoutLogin === 0)>
            <span wire:loading.remove wire:target="createBatch">
                <i class="bi bi-person-plus"></i> Create the next {{ min($batch, $withoutLogin) }} {{ min($batch, $withoutLogin) === 1 ? 'account' : 'accounts' }}
            </span>
            <span wire:loading wire:target="createBatch">Creating accounts…</span>
        </button>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Program</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="login-{{ $row->id }}">
                            <td>
                                <div class="fw-semibold">{{ $row->last_name }}, {{ $row->first_name }}</div>
                                <div class="small text-muted-celeste">{{ $row->student_number }}</div>
                            </td>
                            <td class="small">
                                {{ $row->program }}
                                <div class="text-muted-celeste">{{ $row->year_level }}{{ $row->section ? ' · Section ' . $row->section : '' }}</div>
                            </td>
                            <td class="small">
                                @if ($row->login_id)
                                    {{ $row->login_email }}
                                @else
                                    <span class="text-muted-celeste">{{ $logins->emailFor($row) ?? 'Name or number incomplete' }}</span>
                                @endif
                            </td>
                            <td>
                                @if (! $row->login_id)
                                    <span class="text-muted-celeste">No account</span>
                                @elseif (! $row->login_active)
                                    <span class="text-danger">Disabled</span>
                                @elseif ($row->login_last_at === null)
                                    <span class="text-warning-emphasis">Not yet signed in</span>
                                @else
                                    <span class="text-success">Active</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if (! $row->login_id)
                                    <button type="button" class="btn btn-sm btn-psu" wire:click="create({{ $row->id }})"
                                            wire:loading.attr="disabled" wire:target="create({{ $row->id }})">
                                        Create account
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-psu-outline"
                                            wire:click="openReset({{ $row->login_id }})">
                                        Reset password
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted-celeste py-4">No students match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $rows->links() }}
    </div>

    @if ($resetUser)
        <div class="modal d-block" tabindex="-1" role="dialog" aria-modal="true" style="background:rgba(10,20,50,.55)">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" wire:submit="savePassword">
                    <div class="modal-header">
                        <h6 class="modal-title">Reset password</h6>
                        <button type="button" class="btn-close" aria-label="Close" wire:click="cancelReset"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small mb-3">
                            New password for <strong>{{ $resetUser->name }}</strong>
                            <span class="text-muted-celeste">({{ $resetUser->student_number }})</span>
                        </p>
                        <label for="newPassword" class="form-label small mb-1">New password</label>
                        <input type="text" id="newPassword" wire:model="newPassword" autocomplete="off" autofocus
                               class="form-control @error('newPassword') is-invalid @enderror"
                               placeholder="At least 8 characters">
                        @error('newPassword')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <p class="small text-muted-celeste mt-2 mb-0">
                            Tell the student the new password after saving.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-psu-outline" wire:click="cancelReset">Cancel</button>
                        <button type="submit" class="btn btn-psu" wire:loading.attr="disabled" wire:target="savePassword">Save password</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>