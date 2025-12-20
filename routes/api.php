<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketUpdateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
 * Note: Authentication is assumed for all API requests.
 * In tests, use actingAs() to simulate authenticated users.
 * For production, add authentication middleware (e.g., Sanctum, Passport).
 */

// Customers
Route::post('/customers', [CustomerController::class, 'store']);

// Tickets
Route::post('/tickets', [TicketController::class, 'store']);
Route::get('/tickets', [TicketController::class, 'index']);
Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
Route::patch('/tickets/{ticket}', [TicketController::class, 'update']);

// Ticket Updates
Route::post('/tickets/{ticket}/updates', [TicketUpdateController::class, 'store']);

// Tags
Route::put('/tickets/{ticket}/tags/{tag}', [TagController::class, 'attach']);
Route::delete('/tickets/{ticket}/tags/{tag}', [TagController::class, 'detach']);

