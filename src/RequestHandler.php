<?php

interface RequestHandler {
    public function processRequest(string $method, array $parameters);
}