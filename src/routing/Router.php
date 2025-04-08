<?php 

require_once __DIR__ . "/Route.php";

class Router {
    private array $route_config;
    private array $route_urls;

    public function __construct(private String $route_ini_path) {
        $this->route_config = parse_ini_file($this->route_ini_path, true);
        $this->parseRouteUrls();
    }

    public function parseRouteUrls() {
        $this->route_urls = [];
        foreach (array_keys($this->route_config) as $key) {
            $url = "api/{$this->route_config[$key]['url']}";
            $this->route_urls[$url] = $key;
        }
    }

    public function parseRoute(string $url): Route {
        $key = $this->route_urls[$url];
        $key_parts = explode(".", $key);
        $class = new $key_parts[0]();

        $route_parameters = [];

        $parameters_ini = $this->route_config[$key]["parameters"];
        if ($parameters_ini != "[]") {
            foreach (array_keys($parameters_ini) as $param_key) {
                $route_parameters[$param_key] = (bool) $parameters_ini[$param_key];
            }
        }

        $method = "GET";
        if (key_exists("method", $this->route_config[$key])) {
            $method = $this->route_config[$key]["method"];
        }
        
        $function_url = str_replace(".", "/", explode("$key_parts[0].", $key)[1]);

        return new Route(
            $method,
            "api/{$url}",
            $function_url,
            $route_parameters,
            $class
        );
    }

    public function selectRoute(string $uri): Route | false {
        $exploded = explode("?", $uri);
        $url_parts = array_slice(explode("/", $exploded[0]), 2);

        foreach (array_keys($this->route_urls) as $route_url) {
            $exploded_route = explode("/", ($route_url));
            
            $wrong_route = false;
            for ($i = 0; $i < count($url_parts); $i++) {
                if ($exploded_route[$i] != $url_parts[$i]) {
                    $wrong_route = true;
                    break;
                }
            }
            if (!$wrong_route) { return $this->parseRoute($route_url); }
        }

        return false;
    }

    public function parseURI(string $method, string $uri) {
        # Get parts behind the query
        $exploded_uri = explode("?", $uri);
        $route = $this->selectRoute($uri);
        
        if ($route == null) {
            $current_route = array_slice(explode("/", $exploded_uri[0]), 2);

            http_response_code(404);
            echo json_encode([
                "message" => "No routes were found",
                "current_route" => $current_route,
                "valid" => array_keys($this->route_urls)
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

        $body_data = (array) json_decode(file_get_contents("php://input"), true);
        if (empty($body_data) & !empty($_REQUEST)) {
            $body_data = $_REQUEST;
        }

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
}