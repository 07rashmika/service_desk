<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Renders every UI component with sample data so the design system can be
 * checked against the Stitch designs. Only registered in local and testing.
 */
class StyleguideController extends Controller
{
    public function __invoke(Request $request): View
    {
        $tickets = collect([
            ['ref' => 'SD-000048', 'title' => 'Root certificate expired on staging cluster', 'requester' => 'Elena Rostova', 'department' => 'Security Ops', 'category' => 'Security', 'priority' => ['Critical', 'red'], 'status' => ['In Progress', 'amber'], 'assignee' => 'Kasun Perera', 'due' => now()->subMinutes(125)],
            ['ref' => 'SD-000047', 'title' => 'Database failover replication lagging by 35 minutes', 'requester' => 'Alex Kumar', 'department' => 'DevOps', 'category' => 'Infrastructure', 'priority' => ['High', 'orange'], 'status' => ['Open', 'blue'], 'assignee' => 'Sarah Jenkins', 'due' => now()->addMinutes(18)],
            ['ref' => 'SD-000045', 'title' => 'Okta MFA token reset required', 'requester' => 'David Vance', 'department' => 'Product', 'category' => 'Email & Accounts', 'priority' => ['Medium', 'blue'], 'status' => ['Assigned', 'violet'], 'assignee' => null, 'due' => now()->addMinutes(105)],
            ['ref' => 'SD-000042', 'title' => 'Outlook keeps asking for password', 'requester' => 'Nimal Perera', 'department' => 'Design', 'category' => 'Software', 'priority' => ['Low', 'slate'], 'status' => ['Waiting for User', 'orange'], 'assignee' => 'Kenji Sato', 'due' => null, 'paused' => true],
            ['ref' => 'SD-000036', 'title' => 'YubiKey replacement for remote engineer', 'requester' => 'Siddharth Nair', 'department' => 'Embedded', 'category' => 'Hardware', 'priority' => ['Medium', 'blue'], 'status' => ['Resolved', 'emerald'], 'assignee' => 'Kasun Perera', 'due' => null, 'met' => true],
            ['ref' => 'SD-000035', 'title' => 'Weekly backup job failure logs archival', 'requester' => 'Zoe Chen', 'department' => 'Compliance', 'category' => 'Infrastructure', 'priority' => ['Low', 'slate'], 'status' => ['Closed', 'slate'], 'assignee' => 'Kenji Sato', 'due' => null, 'met' => true],
        ]);

        $paginator = new LengthAwarePaginator(
            items: $tickets,
            total: 43,
            perPage: $tickets->count(),
            currentPage: $request->integer('page', 1),
            options: ['path' => $request->url(), 'pageName' => 'page'],
        );

        return view('styleguide', [
            'tickets' => $paginator,
            'statuses' => [
                'Open' => 'blue',
                'Assigned' => 'violet',
                'In Progress' => 'amber',
                'Waiting for User' => 'orange',
                'Resolved' => 'emerald',
                'Closed' => 'slate',
            ],
            'priorities' => [
                'Low' => 'slate',
                'Medium' => 'blue',
                'High' => 'orange',
                'Critical' => 'red',
            ],
        ]);
    }
}
