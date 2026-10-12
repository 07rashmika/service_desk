<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\LookupResource;
use App\Http\Resources\V1\UserSummaryResource;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    /**
     * The reference data a client needs to build forms and filters.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['data' => [
            'categories' => LookupResource::collection(TicketCategory::query()->active()->ordered()->get()),
            'priorities' => LookupResource::collection(TicketPriority::query()->ordered()->get()),
            'statuses' => LookupResource::collection(TicketStatus::query()->ordered()->get()),
            'technicians' => $request->user()->can('viewQueue', Ticket::class)
                ? UserSummaryResource::collection(User::query()->technicians()->orderBy('name')->get())
                : [],
        ]]);
    }
}
