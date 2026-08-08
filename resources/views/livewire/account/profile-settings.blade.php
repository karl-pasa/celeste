<div class="row g-3">

    <div class="col-lg-7">

        {{-- Still on the initial password ------------------------------------}}
        @if ($this->usingInitialPassword)
            <div class="card-celeste mb-3" style="border-left:3px solid var(--psu-gold)">
                <div class="p-3 d-flex gap-3">
                    <i class="bi bi-exclamation-triangle" style="font-size:1.4rem;color:var(--psu-gold)"></i>
                    <div>
                        <h6 class="mb-1">You are still using your student number as your password</h6>
                        <p class="mb-0 text-muted-celeste" style="font-size:.8125rem">
                            Your student number is printed on your ID and appears on every document issued
                            to you, so anyone holding one of your documents could sign in as you.
                            Change it below.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Profile ----------------------------------------------------------}}
        <div class="card-celeste mb-3">
            <div class="card-header">Your details</div>
            <div class="p-3 p-md-4">

                @if (session('profile-status'))
                    <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 small">
                        <i class="bi bi-check-circle"></i> {{ session('profile-status') }}
                    </div>
                @endif

                <div class="mb-3">
                    <label for="name" class="form-label"><i class="bi bi-person"></i> Full name</label>
                    <input type="text" id="name" wire:model="name"
                           class="form-control @error('name') is-invalid @enderror">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label"><i class="bi bi-envelope"></i> Email address</label>
                    <input type="email" id="email" wire:model="email"
                           class="form-control @error('email') is-invalid @enderror"
                           @disabled(! $this->canEditEmail)>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @unless ($this->canEditEmail)
                        <p class="text-muted-celeste mt-2 mb-0" style="font-size:.75rem">
                            This is your university email and the address you sign in with. It comes from
                            your student record, so the Office of the University Registrar changes it —
                            contact them at {{ config('celeste.institution.registrar_email') }} if it is wrong.
                        </p>
                    @endunless
                </div>

                @if ($this->canEditEmail)
                    <div class="mb-3">
                        <label for="username" class="form-label"><i class="bi bi-at"></i> Username</label>
                        <input type="text" id="username" wire:model="username"
                               class="form-control @error('username') is-invalid @enderror">
                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <p class="text-muted-celeste mt-2 mb-0" style="font-size:.75rem">
                            What you type on the Registrar tab when signing in.
                        </p>
                    </div>
                @endif

                <button wire:click="saveProfile" class="btn btn-psu" wire:loading.attr="disabled" wire:target="saveProfile">
                    <span wire:loading.remove wire:target="saveProfile"><i class="bi bi-check-lg"></i> Save details</span>
                    <span wire:loading wire:target="saveProfile">
                        <span class="spinner-border spinner-border-sm me-1"></span> Saving…
                    </span>
                </button>
            </div>
        </div>

        {{-- Password ---------------------------------------------------------}}
        <div class="card-celeste">
            <div class="card-header">Change your password</div>
            <div class="p-3 p-md-4">

                @if (session('password-status'))
                    <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 small">
                        <i class="bi bi-shield-check"></i> {{ session('password-status') }}
                    </div>
                @endif

                <div class="mb-3" x-data="{ show: false }">
                    <label for="current_password" class="form-label"><i class="bi bi-lock"></i> Current password</label>
                    <div class="input-group">
                        <input :type="show ? 'text' : 'password'" id="current_password"
                               wire:model="current_password" autocomplete="current-password"
                               class="form-control border-end-0 @error('current_password') is-invalid @enderror">
                        <button class="input-group-text" type="button" @click="show = !show"
                                :aria-label="show ? 'Hide password' : 'Show password'">
                            <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                    @error('current_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3" x-data="{ show: false }">
                    <label for="password" class="form-label"><i class="bi bi-key"></i> New password</label>
                    <div class="input-group">
                        <input :type="show ? 'text' : 'password'" id="password"
                               wire:model="password" autocomplete="new-password"
                               class="form-control border-end-0 @error('password') is-invalid @enderror">
                        <button class="input-group-text" type="button" @click="show = !show"
                                :aria-label="show ? 'Hide password' : 'Show password'">
                            <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                    @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    <p class="text-muted-celeste mt-2 mb-0" style="font-size:.75rem">
                        At least 8 characters, with letters and numbers.
                    </p>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label"><i class="bi bi-key-fill"></i> Confirm new password</label>
                    <input type="password" id="password_confirmation" wire:model="password_confirmation"
                           autocomplete="new-password" class="form-control">
                </div>

                <button wire:click="updatePassword" class="btn btn-psu" wire:loading.attr="disabled" wire:target="updatePassword">
                    <span wire:loading.remove wire:target="updatePassword"><i class="bi bi-shield-lock"></i> Change password</span>
                    <span wire:loading wire:target="updatePassword">
                        <span class="spinner-border spinner-border-sm me-1"></span> Updating…
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Account summary -------------------------------------------------------}}
    <div class="col-lg-5">
        <div class="card-celeste mb-3">
            <div class="card-header">Account</div>
            <div class="p-3 p-md-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="avatar" style="width:52px;height:52px;font-size:1.1rem">{{ $this->user->initials() }}</div>
                    <div style="min-width:0">
                        <div class="fw-semibold text-truncate">{{ $this->user->name }}</div>
                        <span class="badge-celeste badge-type">{{ $this->user->roleLabel() }}</span>
                    </div>
                </div>

                <dl class="mb-0">
                    <div class="detail-row"><dt>Sign-in</dt><dd class="text-truncate" style="max-width:60%">{{ $this->user->username }}</dd></div>
                    @if ($this->user->student_number)
                        <div class="detail-row"><dt>Student number</dt><dd class="serial">{{ $this->user->student_number }}</dd></div>
                    @endif
                    @if ($this->user->program)
                        <div class="detail-row"><dt>Program</dt><dd style="text-align:right">{{ $this->user->program }}</dd></div>
                    @endif
                    <div class="detail-row">
                        <dt>Password changed</dt>
                        <dd>{{ $this->user->password_changed_at?->diffForHumans() ?? 'Never' }}</dd>
                    </div>
                    <div class="detail-row">
                        <dt>Last sign-in</dt>
                        <dd>{{ $this->user->last_login_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                    </div>
                    @if ($this->user->last_login_ip)
                        <div class="detail-row"><dt>From</dt><dd class="serial">{{ $this->user->last_login_ip }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="card-celeste">
            <div class="card-header">Keeping your account safe</div>
            <div class="p-3 p-md-4">
                <ul class="ps-3 mb-0" style="font-size:.8125rem;line-height:1.8;color:var(--ink-muted)">
                    <li>Sign out when you leave a shared computer. Closing the tab signs you out too, but signing out is immediate.</li>
                    <li>Never reuse a password you use elsewhere.</li>
                    @unless ($this->user->isRegistrar())
                        <li>Nobody from the Registrar's office will ask for your password.</li>
                        <li>If a document of yours verifies as altered, report it at once — someone may have changed a record.</li>
                    @else
                        <li>Every generation, revocation, and sign-in is recorded in the audit trail against your account.</li>
                    @endunless
                </ul>
            </div>
        </div>
    </div>
</div>