<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Reports\TicketReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $report = $this->report($request);
        $previous = $report->previous();

        return view('admin.reports.index', [
            'report' => $report,
            'summary' => $report->summary(),
            'previousSummary' => $previous->summary(),
            'volume' => $report->dailyVolume(),
            'byStatus' => $report->byStatus(),
            'byCategory' => $report->byCategory(),
            'byDepartment' => $report->byDepartment(),
            'slaByPriority' => $report->slaByPriority(),
            'technicians' => $report->technicians(),
            'periods' => TicketReport::PERIODS,
        ]);
    }

    /**
     * Every ticket created in the range as a CSV file, for spreadsheets.
     */
    public function export(Request $request): StreamedResponse
    {
        $report = $this->report($request);
        $filename = sprintf('servicedesk-tickets-%s-to-%s.csv', $report->from->toDateString(), $report->to->toDateString());

        return response()->streamDownload(function () use ($report): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'Reference', 'Title', 'Category', 'Priority', 'Status', 'Requester', 'Department', 'Assigned to',
                'Created', 'First response', 'Resolved', 'Closed', 'Due', 'SLA met', 'Resolution hours',
            ]);

            $report->created()
                ->with(['category', 'priority', 'status', 'creator.department', 'assignee'])
                ->orderBy('tickets.id')
                ->chunk(500, function ($tickets) use ($output): void {
                    foreach ($tickets as $ticket) {
                        fputcsv($output, array_map(self::safeCell(...), $this->row($ticket)));
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function report(Request $request): TicketReport
    {
        $validated = $request->validate(['period' => ['nullable', Rule::in(array_keys(TicketReport::PERIODS))]]);

        return new TicketReport((int) ($validated['period'] ?? 30));
    }

    /**
     * @return array<int, string>
     */
    protected function row(Ticket $ticket): array
    {
        $dateTime = fn ($value): string => $value?->format('Y-m-d H:i') ?? '';

        return [
            $ticket->reference,
            $ticket->title,
            $ticket->category->name,
            $ticket->priority->name,
            $ticket->status->name,
            $ticket->creator->name,
            $ticket->creator->department?->name ?? '',
            $ticket->assignee?->name ?? '',
            $dateTime($ticket->created_at),
            $dateTime($ticket->first_response_at),
            $dateTime($ticket->resolved_at),
            $dateTime($ticket->closed_at),
            $dateTime($ticket->due_at),
            match (true) {
                $ticket->resolved_at === null || $ticket->due_at === null => '',
                $ticket->resolved_at->lte($ticket->due_at) => 'Yes',
                default => 'No',
            },
            $ticket->resolved_at ? (string) round($ticket->created_at->diffInMinutes($ticket->resolved_at) / 60, 1) : '',
        ];
    }

    /**
     * Stop spreadsheet apps treating a cell that starts with = + - @ as a formula.
     */
    protected static function safeCell(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
