<?php 

class Router {
    private array $routes;

    public function __construct(array $routes) {
        foreach ($routes as $route) {
            $this->addRoute($route);
        }
    }

    public function parseRouteIni(string $ini_path) {
        $route_config = parse_ini_file($ini_path, true);

        $classes = [];

        foreach (array_keys($route_config) as $key) {
            $key_parts = explode(".", $key);
            if (!key_exists($key_parts[0], $classes)) {
                // Create an instance of the class based on the path
                $class = new $key_parts[0]();
                $classes[$key_parts[0]] = $class;
            }
            $class = $classes[$key_parts[0]];
            $route_parameters = [];

            $parameters_ini = $route_config[$key]["parameters"];
            if ($parameters_ini != "[]") {
                foreach (array_keys($parameters_ini) as $param_key) {
                    $route_parameters[$param_key] = (bool) $parameters_ini[$param_key];
                }
            }

            $validateQuery = true;
            if (key_exists("validateQuery", $route_config[$key])) {
                $validateQuery = (bool) $route_config[$key]["validateQuery"];
            }
            $method = "GET";
            if (key_exists("method", $route_config[$key])) {
                $method = $route_config[$key]["method"];
            }

            $route = new Route(
                $method,
                str_replace(".", "/", explode("$key_parts[0].", $key)[1]),
                $validateQuery,
                $route_parameters,
                $class
            );

            $this->addRoute($route);
        }
    }

    public function addRoute(Route $route) {
        $this->routes[$route->getURL()] = $route;
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
            $current_route = array_slice(explode("/", $exploded_uri[0]), 2);

            http_response_code(404);
            echo json_encode([
                "message" => "No routes were found",
                "current_route" => $current_route,
                "valid" => $valid_routes
            ]);

            return;
        }

        if ($route->getMethod() != $method) {
            http_response_code(405);
            echo json_encode([
                "message" => "Wrong method for route '{$route->getUrl()}'",
                "correct_method" => $route->getMethod()
            ]);
        
            return;
        }

        $parameters = $this->queryToAssociativeArray($exploded_uri[1] ?? null);

        # Check for parameter errors if the route uses query parameters
        if ($route->getValidateQuery()) {
            $errors = $this->validateRouteQuery($parameters, $route);
            if (!empty($errors)) {
                http_response_code(422);
                echo json_encode([
                    "errors" => $errors
                ]);
                
                return;
            }
            
        }

        $body_data = (array) json_decode(file_get_contents("php://input"), true);

        return $route->processRequest($parameters, $body_data);
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