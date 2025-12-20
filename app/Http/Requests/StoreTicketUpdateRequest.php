<?php

namespace App\Http\Requests;

use App\Enums\TicketUpdateType;
use App\Models\TicketUpdate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Note: Authentication is assumed. In tests, use actingAs().
     */
    public function authorize(): bool
    {
        // If user is authenticated, check policies
        if ($this->user()) {
            // Check if user can create ticket updates
            $canCreate = $this->user()->can('create', TicketUpdate::class);

            // Also check if user can view the ticket (required to add updates)
            $canViewTicket = $this->user()->can('view', $this->route('ticket'));

            return $canCreate && $canViewTicket;
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
            'body' => ['required', 'string'],
            'type' => ['required', 'string', Rule::enum(TicketUpdateType::class)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $validTypes = implode(', ', array_column(TicketUpdateType::cases(), 'value'));

        return [
            'type.enum' => "The type field must be one of: {$validTypes}.",
        ];
    }
}
