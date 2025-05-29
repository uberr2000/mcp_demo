<?php

namespace App\Contracts;

interface MCPToolInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function getInputSchema(): array;
    public function execute(array $parameters): array;
}
