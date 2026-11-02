<?php

namespace App\Livewire\Registrar;

use App\Models\AuditLog;
use App\Models\StudentRecord;
use App\Models\User;
use App\Services\StudentLoginManager;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The Registrar's list of students and their logins.
 */
class StudentLogins extends Component
{
    use WithPagination;

    /**
     * Each login costs a password hash, which is slow on purpose. Ten at a
     * time stays inside the host's time limit; the button is pressed again
     * for the next ten.
     */
    private const BATCH = 10;

    protected string $paginationTheme = 'bootstrap';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $college = '';

    #[Url]
    public string $program = '';

    /** '' = everyone, 'none' = no login yet, 'has' = has a login */
    #[Url]
    public string $status = '';

    /** The login whose password is being changed, and the password typed for it. */
    public ?int $resetId = null;
    public string $newPassword = '';

    public ?string $notice = null;
    public string $noticeType = 'success';

    /** Runs on every request, including each button press. */
    public function boot(): void
    {
        abort_unless(auth()->user()?->isRegistrar(), 403);
    }

    public function updatedSearch(): void  { $this->resetPage(); }
    public function updatedProgram(): void { $this->resetPage(); }
    public function updatedStatus(): void  { $this->resetPage(); }

    public function updatedCollege(): void
    {
        $this->program = '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'college', 'program', 'status');
        $this->resetPage();
    }

    // ------------------------------------------------------------------
    // Actions
    // ------------------------------------------------------------------

    public function create(int $recordId, StudentLoginManager $logins): void
    {
        $record = StudentRecord::findOrFail($recordId);
        $name = trim($record->first_name . ' ' . $record->last_name);

        switch ($logins->create($record)) {
            case StudentLoginManager::CREATED:
                $this->audit('student_login.created', ['student_number' => $record->student_number]);
                $this->say("Login created for {$name}. Email: {$logins->emailFor($record)}. First password: {$record->student_number}");
                break;
            case StudentLoginManager::EXISTS:
                $this->say("{$name} already has a login.", 'warning');
                break;
            case StudentLoginManager::TAKEN:
                $this->say("No login was created for {$name}: that email address is already used by another account.", 'danger');
                break;
            default:
                $this->say("No login was created for {$name}: the record needs a first name, last name and a student number with at least 3 digits.", 'danger');
        }
    }

    public function createBatch(StudentLoginManager $logins): void
    {
        @set_time_limit(120);

        $ids = $this->query()->whereNull('users.id')->limit(self::BATCH)->pluck('student_records.id');

        $made = 0;
        $skipped = 0;

        StudentRecord::whereIn('id', $ids)->get()->each(function ($record) use ($logins, &$made, &$skipped) {
            $logins->create($record) === StudentLoginManager::CREATED ? $made++ : $skipped++;
        });

        if ($made > 0) {
            $this->audit('student_login.batch_created', ['count' => $made]);
        }

        $left = $this->query()->whereNull('users.id')->count();

        $text = "{$made} " . ($made === 1 ? 'login' : 'logins') . ' created.';
        if ($skipped) {
            $text .= " {$skipped} skipped (email already used or record incomplete).";
        }
        $text .= $left > 0 ? " {$left} more without a login in this list." : ' Everyone in this list now has a login.';

        $this->say($text, $made > 0 ? 'success' : 'warning');
    }

    /** Opens the box where the Registrar types the new password. */
    public function openReset(int $loginId): void
    {
        User::where('role', User::ROLE_STUDENT)->findOrFail($loginId);

        $this->resetId = $loginId;
        $this->newPassword = '';
        $this->resetValidation();
    }

    public function cancelReset(): void
    {
        $this->resetId = null;
        $this->newPassword = '';
        $this->resetValidation();
    }

    public function savePassword(StudentLoginManager $logins): void
    {
        $this->validate([
            'newPassword' => ['required', 'string', 'min:8', 'max:100'],
        ], [
            'newPassword.required' => 'Type the new password.',
            'newPassword.min'      => 'Use at least 8 characters.',
            'newPassword.max'      => 'Use 100 characters or fewer.',
        ]);

        $user = User::where('role', User::ROLE_STUDENT)->findOrFail($this->resetId);
        $logins->setPassword($user, $this->newPassword);

        $this->audit('student_login.password_reset', ['student_number' => $user->student_number]);
        $this->say("Password changed for {$user->name}. Give them the new password; they will be asked to choose their own when they sign in.");

        $this->cancelReset();
    }

    // ------------------------------------------------------------------

    private function say(string $text, string $type = 'success'): void
    {
        $this->notice = $text;
        $this->noticeType = $type;
    }

    private function audit(string $action, array $meta): void
    {
        AuditLog::record($action, auth()->user(), $meta);
    }

    /** Students joined to their login (if any), narrowed by the filters. */
    private function query()
    {
        $like = '%' . mb_strtolower(trim($this->search)) . '%';

        return StudentRecord::query()
            ->leftJoin('users', function ($join) {
                $join->on('users.student_number', '=', 'student_records.student_number')
                     ->where('users.role', '=', User::ROLE_STUDENT);
            })
            ->select(
                'student_records.id',
                'student_records.student_number',
                'student_records.first_name',
                'student_records.last_name',
                'student_records.college',
                'student_records.program',
                'student_records.year_level',
                'student_records.section',
                'users.id as login_id',
                'users.email as login_email',
                'users.is_active as login_active',
                'users.password_changed_at as login_pw_changed',
            )
            ->when($this->college !== '', fn ($q) => $q->where('student_records.college', $this->college))
            ->when($this->program !== '', fn ($q) => $q->where('student_records.program', $this->program))
            ->when($this->status === 'none', fn ($q) => $q->whereNull('users.id'))
            ->when($this->status === 'has', fn ($q) => $q->whereNotNull('users.id'))
            ->when(trim($this->search) !== '', function ($q) use ($like) {
                $q->where(function ($w) use ($like) {
                    $w->whereRaw('LOWER(student_records.student_number) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(student_records.first_name) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(student_records.last_name) LIKE ?', [$like]);
                });
            })
            ->orderBy('student_records.college')
            ->orderBy('student_records.program')
            ->orderBy('student_records.last_name')
            ->orderBy('student_records.first_name');
    }

    public function render(StudentLoginManager $logins)
    {
        $colleges = StudentRecord::query()->distinct()->orderBy('college')->pluck('college')->filter()->values();

        $programs = $this->college === ''
            ? collect()
            : StudentRecord::query()->where('college', $this->college)->distinct()->orderBy('program')->pluck('program')->filter()->values();

        return view('livewire.registrar.student-logins', [
            'rows'         => $this->query()->paginate(20),
            'withoutLogin' => $this->query()->whereNull('users.id')->count(),
            'colleges'     => $colleges,
            'programs'     => $programs,
            'batch'        => self::BATCH,
            'logins'       => $logins,
            'resetUser'    => $this->resetId ? User::find($this->resetId) : null,
        ]);
    }
}