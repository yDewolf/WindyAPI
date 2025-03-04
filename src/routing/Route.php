<?php

class Route {
    public function __construct(
        private string $method, 
        private string $url,
        private bool $validateQuery, 
        private array $params, 
        private RequestHandler $handler) {

    }

    public function getURL(): string {
        return $this->url;
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getValidateQuery(): bool {
        return $this->validateQuery;
    }

    public function getParams(): array {
        return $this->params;
    }

    public function processRequest(array $parameters) {
        $url = $this->url;
        return $this->handler->$url($parameters);
    }
}