<?php

require_once __DIR__ . "/UsersGateway.php";

class UserController implements RequestHandler {
    private UsersGateway $gateway;

    public function __construct() {
        $this->gateway = new UsersGateway();
    }

    /*
        API Methods
    */
    
    public function getUsers($parameters, $body_data) {
        echo json_encode($this->gateway->getAll());
    }

    public function createUser($parameters, $body_data) {
        $errors = getValidationErrors($body_data);
        
        foreach (["username", "email"] as $field) {
            if (!key_exists($field, $body_data)) {
                continue;
            }

            if ($this->gateway->checkDuplicateUserField($field, $body_data[$field])) {
                $errors[$field] = "This $field is already in use";
            }
        }

        if (!empty($errors)) {
            http_response_code(222);
            echo json_encode(["errors" => $errors]);
            return;
        }

        $data = $this->gateway->createUser($body_data);


        http_response_code(201);
        echo json_encode([
            "message" => "User created Successfully",
            "id" => $data["id"],
            "token" => $data["token"]
        ]);
    }

    public function getUser($parameters, $body_data) {
        $user_data = $this->getUserData($parameters["id"]);
        if (empty($user_data)) {
            return;
        }

        echo json_encode([
            "id" => $user_data["id"],
            "username" => $user_data["username"],
            "nickname" => $user_data["nickname"]
        ]);
    }

    public function updateUser($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["user_id", "token"])) {
            return;
        }
        
        $id = $body_data["user_id"];
        $user_data = $this->getUserData($id);

        handleTokenValidation($this->gateway, $body_data["user_id"], $body_data["token"]);

        $rows_affected = $this->gateway->updateUser($user_data, $body_data);

        echo json_encode([
            "message" => "User {$id} updated Successfully",
            "rows" => $rows_affected
        ]);
    }

    public function deleteUser($parameters, $body_data) {
        if (!handleValidationErrors($body_data, false, ["user_id", "password", "token"])) { return; }
        
        $id = $body_data["user_id"];

        handleTokenValidation($this->gateway, $body_data["user_id"], $body_data["token"]);

        if (!$this->gateway->checkCorrectPassword($id, $body_data["password"])) {
            http_response_code(401);
            echo json_encode([
                "message" => "Incorrect password"
            ]);

            return;
        }
        
        $rows_affected = $this->gateway->deleteUser($id);

        echo json_encode([
            "message" => "User {$id} deleted Successfully",
            "rows" => $rows_affected
        ]);
    }

    public function logInAccount($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["username", "password"])) {
            return;
        }

        $data = $this->gateway->getUserToken($body_data["username"], $body_data["password"]);
        if (empty($data)) {
            http_response_code(401);
            echo json_encode([
                "message" => "Invalid username or password"
            ]);
            
            return;
        }

        http_response_code(200);
        echo json_encode([
            "message" => "Logged in successfully",
            "id" => $data["id"],
            "token" => $data["token"]
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
}