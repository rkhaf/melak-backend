<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumReport;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * FR-09.1.2 / NFR-02.7 — moderasi laporan forum.
 */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $statuses = ['pending', 'reviewed', 'dismissed'];

        if (! in_array($status, [...$statuses, 'all'], true)) {
            $status = 'pending';
        }

        $reports = ForumReport::with(['reporter:id,name,username', 'reviewer:id,name'])
            ->with('reportable')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->reason))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = collect($statuses)
            ->mapWithKeys(fn ($s) => [$s => ForumReport::where('status', $s)->count()])
            ->all();
        $counts['all'] = ForumReport::count();

        $reasons = ForumReport::select('reason')->distinct()->orderBy('reason')->pluck('reason');

        return view('admin.reports.index', compact('reports', 'status', 'counts', 'reasons'));
    }

    public function review(ForumReport $report)
    {
        return $this->resolve($report, 'reviewed');
    }

    public function dismiss(ForumReport $report)
    {
        return $this->resolve($report, 'dismissed');
    }

    private function resolve(ForumReport $report, string $status): RedirectResponse
    {
        if ($report->status !== 'pending') {
            return back()->with('info', 'Laporan sudah diproses sebelumnya.');
        }

        $report->update([
            'status' => $status,
            'reviewed_by' => auth()->id(),
        ]);

        Audit::log(
            'report.'.$status,
            'Laporan #'.$report->id.' ('.$report->reason.') '.($status === 'reviewed' ? 'diterima' : 'ditolak').'.',
            $report
        );

        return back()->with('success', 'Laporan '.($status === 'reviewed' ? 'diterima' : 'ditolak').'.');
    }
}
