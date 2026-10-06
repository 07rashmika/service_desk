<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public const MAX_ATTACHMENTS = 5;

    public const MAX_ATTACHMENT_KB = 5120;

    /**
     * File types people can attach to tickets and replies.
     *
     * @var array<int, string>
     */
    public const ATTACHMENT_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'pdf', 'txt', 'log'];

    public function authorize(): bool
    {
        return $this->user()->can('create', Ticket::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:10', 'max:200'],
            'category_id' => ['required', 'integer', Rule::exists(TicketCategory::class, 'id')->where('is_active', true)],
            'priority_id' => ['required', 'integer', Rule::exists(TicketPriority::class, 'id')],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            ...self::attachmentRules(),
        ];
    }

    /**
     * Validation rules for the optional `attachments[]` upload field.
     *
     * @return array<string, array<int, string>>
     */
    public static function attachmentRules(): array
    {
        $extensions = implode(',', self::ATTACHMENT_EXTENSIONS);

        return [
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_ATTACHMENTS],
            'attachments.*' => [
                'file',
                'max:'.self::MAX_ATTACHMENT_KB,
                'extensions:'.$extensions,
                'mimes:png,jpg,jpeg,gif,webp,pdf,txt',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'priority_id' => 'priority',
            'attachments.*' => 'attachment',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.min' => 'Please give a more descriptive summary (at least :min characters).',
            'description.min' => 'Please describe the problem in a bit more detail (at least :min characters).',
            'attachments.max' => 'You can attach up to :max files.',
        ];
    }
}
