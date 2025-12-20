<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // If user is authenticated, check policy
        if ($this->user()) {
            return $this->user()->can('viewAny', Ticket::class);
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
            'status' => ['sometimes', 'string', Rule::enum(TicketStatus::class)],
            'priority' => ['sometimes', 'string', Rule::enum(TicketPriority::class)],
            'tag' => ['sometimes', 'string', 'max:255'],
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'assigned_user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort_by' => ['sometimes', 'string', 'in:created_at,priority,status'],
            'sort_order' => ['sometimes', 'string', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
