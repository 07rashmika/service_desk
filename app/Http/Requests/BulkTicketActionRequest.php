<?php

namespace App\Http\Requests;

use App\Http\Controllers\Support\SupportTicketActionController;
use App\Models\Ticket;
use App\Models\TicketPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkTicketActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewQueue', Ticket::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['take', 'assign', 'priority'])],
            'tickets' => ['required', 'array', 'max:100'],
            'tickets.*' => ['integer', Rule::exists(Ticket::class, 'id')->whereNull('deleted_at')],
            'priority_id' => ['required_if:action,priority', 'nullable', 'integer', Rule::exists(TicketPriority::class, 'id')],
            'technician_id' => ['required_if:action,assign', 'nullable', 'integer', SupportTicketActionController::technicianRule()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tickets.required' => 'Select at least one ticket first.',
            'priority_id.required_if' => 'Choose the new priority.',
            'technician_id.required_if' => 'Choose who to assign the tickets to.',
        ];
    }
}
