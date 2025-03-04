<?php

class Route {
    public function __construct(private string $url, private bool $bodyInput, private array $params, private RequestHandler $handler) {

    }

    public function getURL(): string {
        return $this->url;
    }

    public function getBodyInput(): bool {
        return $this->bodyInput;
    }

    public function getParams(): array {
        return $this->params;
    }

    public function processRequest(string $method, array $parameters) {
        return $this->handler->processRequest($method, $parameters);
    }
}