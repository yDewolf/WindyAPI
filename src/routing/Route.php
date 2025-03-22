<?php

class Route {
    public function __construct(
        private string $method, 
        private string $url,
        private string $function_url,
        private bool $validateQuery, 
        private array $params, 
        private RequestHandler $handler
        ) {
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

    public function processRequest(array $parameters, array $body_data) {
        $func_url = $this->function_url;
        return $this->handler->$func_url($parameters, $body_data);
    }
}