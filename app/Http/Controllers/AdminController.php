<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\StudentRecord;
use App\Models\User;
use App\Models\VerificationLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $withoutLogin = StudentRecord::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('users')
                ->whereColumn('users.student_number', 'student_records.student_number')
                ->where('users.role', User::ROLE_STUDENT);
        })->count();

        return view('admin.dashboard', [
            'records'      => StudentRecord::count(),
            'logins'       => User::where('role', User::ROLE_STUDENT)->count(),
            'active'       => User::where('role', User::ROLE_STUDENT)->where('is_active', true)->count(),
            'withoutLogin' => $withoutLogin,
            'registrars'   => User::where('role', User::ROLE_REGISTRAR)->count(),
            'certificates' => Certificate::count(),
            'switchedOff'  => User::where('role', User::ROLE_STUDENT)->where('is_active', false)->count(),
            'verifications' => VerificationLog::where('created_at', '>=', now()->subDays(30))->count(),
        ]);
    }

    public function logins(): View
    {
        return view('admin.logins');
    }

    public function students(): View
    {
        return view('admin.students');
    }
}