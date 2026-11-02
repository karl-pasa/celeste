<div>
    @if ($notice)
        <div class="alert alert-{{ $noticeType }} d-flex justify-content-between align-items-start gap-3 py-2 px-3 small" role="alert">
            <span>{{ $notice }}</span>
            <button type="button" class="btn-close" style="font-size:.7rem" aria-label="Close" wire:click="$set('notice', null)"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card-celeste">
                <div class="card-header">Who moves up</div>
                <div class="p-3 p-md-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">College</label>
                            <select class="form-select @error('college') is-invalid @enderror" wire:model.live="college">
                                <option value="">Choose a college</option>
                                @foreach ($colleges as $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('college') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Program</label>
                            <select class="form-select" wire:model.live="program" @disabled($college === '')>
                                <option value="">{{ $college === '' ? 'Choose a college first' : 'All programs in this college' }}</option>
                                @foreach ($programs as $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Graduating year</label>
                            <select class="form-select" wire:model.live="finalLevel">
                                @foreach ($levels as $level)
                                    <option value="{{ $level }}">{{ $level }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Students in this year are marked Graduated instead of moving up. Use 5th Year for five-year programs.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="mb-2 fw-semibold small">New school year (optional)</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Academic year</label>
                            <input type="text" class="form-control @error('academicYear') is-invalid @enderror"
                                   wire:model="academicYear" placeholder="2027-2028" maxlength="9">
                            @error('academicYear') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Semester</label>
                            <select class="form-select @error('semester') is-invalid @enderror" wire:model="semester">
                                <option value="">Keep as it is</option>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-text mt-2">Left blank, the academic year and semester stay as they are.</div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-celeste">
                <div class="card-header">Preview</div>
                <div class="p-3 p-md-4">
                    @if ($college === '')
                        <p class="text-muted-celeste small mb-0">Choose a college to see what will change.</p>
                    @else
                        <table class="table table-sm align-middle mb-3">
                            <thead>
                                <tr><th>Now</th><th>Becomes</th><th class="text-end">Students</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($plan['rows'] as $row)
                                    <tr>
                                        <td>{{ $row['from'] }}</td>
                                        <td>{{ $row['to'] }}</td>
                                        <td class="text-end">{{ number_format($row['count']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <p class="small mb-1"><strong>{{ number_format($plan['total']) }}</strong> {{ $plan['total'] === 1 ? 'student' : 'students' }} will change.</p>
                        @if ($plan['notEnrolled'] > 0)
                            <p class="small text-muted-celeste mb-1">{{ number_format($plan['notEnrolled']) }} not currently enrolled (graduated, transferred, irregular) are left alone.</p>
                        @endif
                        @if ($plan['other'] > 0)
                            <p class="small text-muted-celeste mb-1">{{ number_format($plan['other']) }} enrolled with no year level, or above the graduating year, are left alone.</p>
                        @endif
                        <p class="small text-muted-celeste">Sections stay the same. Graduation dates are not set by this tool.</p>

                        <button type="button" class="btn btn-psu w-100"
                                wire:click="promote"
                                wire:confirm="Move these students up now? This changes their records."
                                wire:loading.attr="disabled" wire:target="promote"
                                @disabled($plan['total'] === 0)>
                            <span wire:loading.remove wire:target="promote">Move {{ number_format($plan['total']) }} {{ $plan['total'] === 1 ? 'student' : 'students' }} up</span>
                            <span wire:loading wire:target="promote">Saving…</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>