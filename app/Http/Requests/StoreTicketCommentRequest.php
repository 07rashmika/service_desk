<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->boolean('internal') ? 'addInternalNote' : 'comment';

        return $this->user()->can($ability, $this->route('ticket'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'internal' => ['sometimes', 'boolean'],
            ...StoreTicketRequest::attachmentRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => 'reply',
            'attachments.*' => 'attachment',
        ];
    }
}
