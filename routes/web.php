<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MCPController;
use App\Http\Controllers\MCPSSEController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/chat', [ChatController::class, 'chat'])->name('chat');
Route::get('/mcp-test', function () {
    return view('mcp-test');
})->name('mcp.test');

// SSE Connection Test Page
Route::get('/sse-test', function () {
    return view('sse-test');
})->name('sse.test');

// MCP Service Routes
Route::prefix('mcp')->withoutMiddleware(['web'])->group(function () {
    Route::post('/initialize', [MCPController::class, 'initialize'])->name('mcp.initialize');
    Route::get('/tools', [MCPController::class, 'listTools'])->name('mcp.tools.list');
    Route::post('/tools/call', [MCPController::class, 'callTool'])->name('mcp.tools.call');
    Route::get('/ping', [MCPController::class, 'ping'])->name('mcp.ping');
    Route::get('/info', [MCPController::class, 'getServerInfo'])->name('mcp.info');
    
    // SSE 端點供 n8n MCP 客戶端使用
    Route::post('/sse', [MCPSSEController::class, 'sse'])->name('mcp.sse');
    Route::any('/websocket', [MCPSSEController::class, 'websocket'])->name('mcp.websocket');
    Route::get('/stdio', [MCPSSEController::class, 'stdio'])->name('mcp.stdio');
});
