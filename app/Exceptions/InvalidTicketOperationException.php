<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class InvalidTicketOperationException extends \Exception
{
    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage() ?: 'Invalid ticket operation.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
