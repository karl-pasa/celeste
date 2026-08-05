<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\StudentRecord;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected AnalyticsService $analytics) {}

    public function registrar(): View
    {
        return view('registrar.dashboard', [
            'summary'  => $this->analytics->summary(),
            'series'   => $this->analytics->volumeSeries(14),
            'flags'    => $this->analytics->decisionFlags(),
            'recent'   => Certificate::with('studentRecord')->latest()->limit(8)->get(),
            'activity' => $this->analytics->recentActivity(8),
            'issuance' => $this->analytics->issuanceByType(),
        ]);
    }

    public function analytics(Request $request): View
    {
        $days = (int) $request->integer('days', 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;

        return view('registrar.analytics', [
            'days'        => $days,
            'summary'     => $this->analytics->summary($days),
            'series'      => $this->analytics->volumeSeries($days),
            'byType'      => $this->analytics->byDocumentType($days),
            'byMethod'    => $this->analytics->byMethod($days),
            'flags'       => $this->analytics->decisionFlags($days),
            'mostChecked' => $this->analytics->mostVerified(),
            'activity'    => $this->analytics->recentActivity(15),
        ]);
    }

    public function logs(): View
    {
        return view('registrar.logs', [
            'audits' => AuditLog::with('user')->latest()->paginate(25),
        ]);
    }

    public function student(Request $request): View
    {
        $record = StudentRecord::where('student_number', $request->user()->student_number)->first();

        $certificates = $record
            ? $record->certificates()->latest()->get()
            : collect();

        return view('student.dashboard', [
            'record'       => $record,
            'certificates' => $certificates,
            'available'    => Certificate::types(),
        ]);
    }

    public function documents(Request $request): View
    {
        $record = StudentRecord::where('student_number', $request->user()->student_number)->first();

        return view('student.documents', [
            'record'       => $record,
            'certificates' => $record ? $record->certificates()->latest()->get() : collect(),
        ]);
    }
}
