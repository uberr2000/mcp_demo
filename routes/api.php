<?php

use App\Http\Controllers\MCPController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// MCP API Routes for external integrations (n8n, etc.)
Route::prefix('mcp')->group(function () {
    Route::post('/initialize', [MCPController::class, 'initialize']);
    Route::get('/tools', [MCPController::class, 'listTools']);
    Route::post('/tools/call', [MCPController::class, 'callTool']);
    Route::get('/ping', [MCPController::class, 'ping']);
    Route::get('/info', [MCPController::class, 'getServerInfo']);
});
