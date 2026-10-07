<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResolveTicketRequest extends FormRequest
{
    /**
     * Error bag name, so a failed resolve reopens the resolve dialog.
     *
     * @var string
     */
    protected $errorBag = 'resolveTicket';

    public function authorize(): bool
    {
        return $this->user()->can('resolve', $this->route('ticket'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'solution' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'solution.min' => 'Describe the fix in a little more detail (at least :min characters). The requester will see it.',
        ];
    }
}
