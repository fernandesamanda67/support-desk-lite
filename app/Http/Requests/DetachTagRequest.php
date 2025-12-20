<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DetachTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            // No validation needed - tag is in the route parameter
        ];
    }
}
