<?php

class UserController {

    public function __construct(private UsersGateway $gateway) {

    }

    public function processRequest(string $method, ?string $id): void {
        if ($id) {
            $this->processResourceRequest($method, $id);

        } else {
            $this->processCollectionRequest($method);
        }
    }

    private function processResourceRequest(string $method, string $id): void {
        $user_data = $this->gateway->getUser($id);

        if (!$user_data) {
            http_response_code(404);
            echo json_encode([
                "message" => "User not found"
            ]);
            return;
        }

        switch ($method) {
            case "GET":
                unset($user_data["password"]);
                unset($user_data["email"]);

                echo json_encode($user_data);
                break;

            
            case "PATCH":
                $data = (array) json_decode(file_get_contents("php://input"), true);
                $errors = $this->getValidationErrors($data, false);

                if (!empty($errors)) {
                    http_response_code(222);
                    echo json_encode(["errors" => $errors]);
                    break;
                }
                
                $rows_affected = $this->gateway->updateUser($user_data, $data);

                echo json_encode([
                    "message" => "User {$id} updated Successfully",
                    "rows" => $rows_affected
                ]);

                break;
            
            case "DELETE":
                $data = (array) json_decode(file_get_contents("php://input"), true);
                $errors = $this->getValidationErrors($data, false, ["password"]);
                if (!empty($errors)) {
                    http_response_code(222);
                    echo json_encode(["errors" => $errors]);
                    break;
                }

                if (!$this->gateway->checkCorrectPassword($id, $data["password"])) {
                    http_response_code(401);
                    echo json_encode([
                        "message" => "Incorrect password"
                    ]);

                    break;
                }
                
                // $rows_affected = $this->gateway->deleteUser($id, $data);

                echo json_encode([
                    "message" => "User {$id} deleted Successfully",
                    "rows" => 0 #$rows_affected;
                ]);

                break;
        }

    }

    private function processCollectionRequest(string $method): void {
        switch ($method) {
            case "GET":
                echo json_encode($this->gateway->getAll());
                break;
            
            case "POST":
                $data = (array) json_decode(file_get_contents("php://input"), true);
                $errors = $this->getValidationErrors($data);
                if (!empty($errors)) {
                    http_response_code(222);
                    echo json_encode(["errors" => $errors]);
                    break;
                }
                
                $id = $this->gateway->createUser($data);

                http_response_code(201);
                echo json_encode([
                    "message" => "User created Successfully",
                    "id" => $id
                ]);

                break;
            
            default:
                http_response_code(405);
                header("Allow: GET, POST");
        }
    }

    private function getValidationErrors(array $data, bool $not_empty = false, array $required_fields = ["username", "email", "password"]): array {
        $errors = [];

        if ($not_empty) {
            if (empty($data)) {
                $errors["error"] = "At least one field should be filled";
            }

            return $errors;
        }

        for ($i = 0; $i < count($required_fields); $i++) {
            if (empty($data[$required_fields[$i]])) {
                $formatted = ucwords($required_fields[$i]);
                $errors["$required_fields[$i]"] = "{$formatted} is required";
            }
        }

        return $errors;
    }
}