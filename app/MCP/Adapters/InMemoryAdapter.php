<?php

namespace App\MCP\Adapters;

use OPGG\LaravelMcpServer\Transports\SseAdapters\SseAdapterInterface;

/**
 * In-Memory SSE Adapter for testing purposes
 * 
 * This adapter stores messages in memory and should only be used for development/testing.
 * In production, use a persistent adapter like Redis or NATS.
 */
class InMemoryAdapter implements SseAdapterInterface
{
    /**
     * In-memory storage for messages
     * Format: ['clientId' => ['message1', 'message2', ...]]
     */
    private static array $messages = [];

    /**
     * Configuration storage
     */
    private array $config = [];

    public function pushMessage(string $clientId, string $message): void
    {
        if (!isset(self::$messages[$clientId])) {
            self::$messages[$clientId] = [];
        }
        
        self::$messages[$clientId][] = $message;
        
        // Apply TTL-like behavior by limiting message count per client
        $maxMessages = $this->config['max_messages_per_client'] ?? 100;
        if (count(self::$messages[$clientId]) > $maxMessages) {
            array_shift(self::$messages[$clientId]);
        }
    }

    public function removeAllMessages(string $clientId): void
    {
        unset(self::$messages[$clientId]);
    }

    public function receiveMessages(string $clientId): array
    {
        return self::$messages[$clientId] ?? [];
    }

    public function popMessage(string $clientId): ?string
    {
        if (empty(self::$messages[$clientId])) {
            return null;
        }
        
        return array_shift(self::$messages[$clientId]);
    }

    public function hasMessages(string $clientId): bool
    {
        return !empty(self::$messages[$clientId]);
    }

    public function getMessageCount(string $clientId): int
    {
        return count(self::$messages[$clientId] ?? []);
    }

    public function initialize(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Debug method to get all stored messages (for testing)
     */
    public function getAllMessages(): array
    {
        return self::$messages;
    }

    /**
     * Debug method to clear all messages (for testing)
     */
    public function clearAllMessages(): void
    {
        self::$messages = [];
    }
}
