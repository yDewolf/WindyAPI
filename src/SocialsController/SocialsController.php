<?php

require_once __DIR__ . "/SocialsGateway.php";

class SocialsController implements RequestHandler {
    private SocialsGateway $socials_gateway;
    private UsersGateway $users_gateway;

    function __construct() {
        $this->socials_gateway = new SocialsGateway();
        $this->users_gateway = new UsersGateway();
    }

    function sendFriendRequest($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["token", "sender_id", "receiver_id"])) {
            return;
        }

        if (!$this->users_gateway->validateToken($body_data["sender_id"], $body_data["token"])) {
            http_response_code(401);
            echo json_encode([
                "message" => "You don't have permission to make this action",
                "error" => "Invalid token"
            ]);
            return;
        }

        if ($this->socials_gateway->checkRequestExists($body_data["sender_id"], $body_data["receiver_id"])) {
            http_response_code(409);
            echo json_encode([
                "message" => "You can't send two friend requests for the same user",
                "error" => "Duplicate friend request"
            ]);
            return;
        }
        
        $this->socials_gateway->sendFriendRequest($body_data["sender_id"], $body_data["receiver_id"]);
        echo json_encode([
            "message" => "Friend request sent successfully"
        ]);
    }
}