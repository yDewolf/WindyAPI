<?php

function getValidationErrors(array $data, bool $not_empty = false, array $required_fields = ["username", "email", "password"]): array {
    $errors = [];

    if ($not_empty) {
        if (empty($data)) {
            $errors["error"] = "At least one field should be filled in body";
        }

        // return $errors;
    }

    for ($i = 0; $i < count($required_fields); $i++) {
        if (empty($data[$required_fields[$i]])) {
            $formatted = str_replace("_", " ", ucwords($required_fields[$i]));
            $errors["$required_fields[$i]"] = "{$formatted} is required in body";
        }
    }

    return $errors;
}

function handleValidationErrors(array $data, bool $not_empty = false, array $required_fields = ["username", "email", "password"]): bool {
    $errors = getValidationErrors($data, $not_empty, $required_fields);

    if (!empty($errors)) {
        http_response_code(response_code: 222);
        echo json_encode(["errors" => $errors]);
        return false;
    }

    return true;
}

function handleTokenValidation(UsersGateway $users_gateway, String $user_id, String $token): bool {
    if (!$users_gateway->validateToken($user_id, $token)) {
        http_response_code(response_code: 401);
        echo json_encode([
            "message" => "You don't have permission to perform this action",
            "error" => "Invalid token"
        ]);
        return false;
    }
    
    return true;

}

function handlePasswordValidation(UsersGateway $users_gateway, String $user_id, String $password): bool{
    if (!$users_gateway->checkCorrectPassword($user_id, $password)) {
        http_response_code(401);
        echo json_encode([
            "message" => "Incorrect password"
        ]);
    
        return false;
    }

    return true;
}