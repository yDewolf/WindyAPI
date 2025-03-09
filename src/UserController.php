<?php

class UserController implements RequestHandler {
    private UsersGateway $gateway;

    public function __construct() {
        $this->gateway = new UsersGateway();
    }

    /*
        API Methods
    */
    
    public function getUsers($parameters) {
        echo json_encode($this->gateway->getAll());
    }

    public function createUser($parameters) {
        $data = (array) json_decode(file_get_contents("php://input"), true);
        $errors = $this->getValidationErrors($data);
        if (!empty($errors)) {
            http_response_code(222);
            echo json_encode(["errors" => $errors]);
            return;
        }
        
        $id = $this->gateway->createUser($data);

        http_response_code(201);
        echo json_encode([
            "message" => "User created Successfully",
            "id" => $id
        ]);
    }

    public function getUser($parameters) {
        $user_data = $this->getUserData($parameters["id"]);

        echo json_encode([
            "id" => $user_data["id"],
            "nickname" => $user_data["nickname"]
        ]);
    }

    public function updateUser($parameters) {
        $id = $parameters["id"];
        $user_data = $this->getUserData($id);

        $data = (array) json_decode(file_get_contents("php://input"), true);
        $errors = $this->getValidationErrors($data, false);

        if (!empty($errors)) {
            http_response_code(222);
            echo json_encode(["errors" => $errors]);
            return;
        }
        
        $rows_affected = $this->gateway->updateUser($user_data, $data);

        echo json_encode([
            "message" => "User {$id} updated Successfully",
            "rows" => $rows_affected
        ]);
    }

    public function deleteUser($parameters) {
        $id = $parameters["id"];

        $data = (array) json_decode(file_get_contents("php://input"), true);
        $errors = $this->getValidationErrors($data, false, ["password"]);
        if (!empty($errors)) {
            http_response_code(222);
            echo json_encode(["errors" => $errors]);
            return;
        }

        if (!$this->gateway->checkCorrectPassword($id, $data["password"])) {
            http_response_code(401);
            echo json_encode([
                "message" => "Incorrect password"
            ]);

            return;
        }
        
        $rows_affected = $this->gateway->deleteUser($id, $data);

        echo json_encode([
            "message" => "User {$id} deleted Successfully",
            "rows" => $rows_affected
        ]);
    }

    /*
        Utility functions
    */

    private function getUserData($id) {
        $user_data = $this->gateway->getUser($id);

        if (!$user_data) {
            http_response_code(404);
            echo json_encode([
                "message" => "User not found"
            ]);
            return;
        }

        return $user_data;
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
                $errors["$required_fields[$i]"] = "{$formatted} is required in body";
            }
        }

        return $errors;
    }
}