<?php

class Route {
    public function __construct(private string $url, private array $params, private RequestHandler $handler) {

    }

    public function getURL(): string {
        return $this->url;
    }

    public function getParams(): array {
        return $this->params;
    }

    public function processRequest(string $method, array $parameters) {
        return $this->handler->processRequest($method, $parameters);
    }
}