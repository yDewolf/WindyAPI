<?php 

class Router {
    private array $routes;

    public function __construct(array $routes) {
        foreach ($routes as $route) {
            $this->routes[$route->getURL()] = $route;
        }
    }

    public function parseURI(string $method, string $uri) {
        # Get parts behind the query
        $exploded_uri = explode("?", $uri);
        $route = $this->getRoute($exploded_uri[0]);
        
        if ($route == null) {
            $valid_routes = [];

            foreach ($this->routes as $route) {
                $valid_routes[] = $route->getURL();
            }
            
            http_response_code(404);
            echo json_encode([
                "message" => "No routes were found",
                "valid" => $valid_routes
            ]);

            return;
        }

        $parameters = $this->queryToAssociativeArray($exploded_uri[1] ?? null);

        $errors = $this->validateRouteQuery($parameters, $route);
        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode([
                "errors" => $errors
            ]);
            
            return;
        }

        return $route->processRequest($method, $parameters);
    }
    
    /*  
        Values will always return as string
        Value conversion should be done when the value is used
    */
    private function queryToAssociativeArray(?string $query): array {
        $parameters = [];
        if ($query == null) {
            return $parameters;
        }

        $exploded_query = explode("&", $query);
        foreach ($exploded_query as $param_str) {
            $param = explode("=", $param_str);

            $key = $param[0];
            $value = $param[1];
            $parameters[$key] = $value;
        }

        return $parameters;
    }

    private function getRoute(string $url): Route | null {
        # 2 -> offset the url so it ignores the start
        $url_parts = array_slice(explode("/", $url), 2);

        foreach ($this->routes as $route) {
            $route_url_parts = explode("/", $route->getURL());

            $wrong_route = false;
            for ($i = 0; $i < count($url_parts); $i++) {
                if ($route_url_parts[$i] != $url_parts[$i]) {
                    $wrong_route = true;
                    break;
                }
            }
            
            if ($wrong_route) {
                continue;
            }

            return $route;
        }

        return null;
    }

    private function validateRouteQuery(array $parameters, Route $route): array {
        $errors = [];

        $route_params = $route->getParams();
        foreach (array_keys($route->getParams()) as $key) {
            # Key is needed and key doesn't exist on provided parameters
            if ($route_params[$key] && !key_exists($key, $parameters)) {
                $errors[$key] = "Missing value";
            }
        }

        return $errors;
    }
}