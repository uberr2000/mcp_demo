<?php

namespace App\Providers;

use App\MCP\Adapters\InMemoryAdapter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use OPGG\LaravelMcpServer\Protocol\MCPProtocol;
use OPGG\LaravelMcpServer\Server\MCPServer;
use OPGG\LaravelMcpServer\Server\ServerCapabilities;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;
use OPGG\LaravelMcpServer\Transports\SseAdapters\RedisAdapter;
use OPGG\LaravelMcpServer\Transports\SseTransport;

/**
 * Custom MCP Server Service Provider
 * 
 * This provider extends the default MCP server functionality to support
 * additional SSE adapters like the InMemory adapter for testing.
 */
class CustomMcpServerServiceProvider extends ServiceProvider
{    public function register(): void
    {
        // Only override if we're using SSE and the memory adapter
        if (Config::get('mcp-server.server_provider') === 'sse' && 
            Config::get('mcp-server.sse_adapter') === 'memory') {
            
            $this->app->singleton(ToolRepository::class, function ($app) {
                $toolRepository = new ToolRepository($app);

                $tools = Config::get('mcp-server.tools', []);
                $toolRepository->registerMany($tools);

                return $toolRepository;
            });

            $this->app->singleton(MCPServer::class, function ($app) {
                $transport = new SseTransport;

                // Create our custom in-memory adapter
                $adapter = new InMemoryAdapter();
                $adapterConfig = Config::get('mcp-server.adapters.memory', []);
                $adapter->initialize($adapterConfig);

                $transport->setAdapter($adapter);

                $protocol = new MCPProtocol($transport);

                $serverInfo = Config::get('mcp-server.server');

                $capabilities = new ServerCapabilities;

                $toolRepository = app(ToolRepository::class);
                $capabilities->withTools(['schemas' => $toolRepository->getToolSchemas()]);

                return MCPServer::create(
                    protocol: $protocol, 
                    name: $serverInfo['name'], 
                    version: $serverInfo['version'], 
                    capabilities: $capabilities
                )->registerToolRepository(toolRepository: $toolRepository);
            });
        }
    }
}
