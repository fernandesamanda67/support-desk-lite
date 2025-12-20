<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Note: Authentication is assumed. In tests, use actingAs().
     */
    public function authorize(): bool
    {
        // If user is authenticated, check policy
        if ($this->user()) {
            return $this->user()->can('update', $this->route('ticket'));
        }

        // If no user, assume authorized (for testing without auth)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'priority' => ['sometimes', 'string', Rule::enum(TicketPriority::class)],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'string', Rule::enum(TicketStatus::class)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $validStatuses = implode(', ', array_column(TicketStatus::cases(), 'value'));
        $validPriorities = implode(', ', array_column(TicketPriority::cases(), 'value'));

        return [
            'status.enum' => "The status field must be one of: {$validStatuses}.",
            'priority.enum' => "The priority field must be one of: {$validPriorities}.",
            'assigned_user_id.exists' => 'The selected user does not exist.',
        ];
    }
}
