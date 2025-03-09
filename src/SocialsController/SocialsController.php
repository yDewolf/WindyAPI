<?php

require_once __DIR__ . "/SocialsGateway.php";

class SocialsController implements RequestHandler {
    private SocialsGateway $socials_gateway;
    private UsersGateway $users_gateway;

    function __construct() {
        $this->socials_gateway = new SocialsGateway();
        $this->users_gateway = new UsersGateway();
    }

    public function sendFriendRequest($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["token", "sender_id", "receiver_id"])) {
            return;
        }

        if (!$this->users_gateway->validateToken($body_data["sender_id"], $body_data["token"])) {
            http_response_code(401);
            echo json_encode([
                "message" => "You don't have permission to perform this action",
                "error" => "Invalid token"
            ]);
            return;
        }

        if ($this->socials_gateway->alreadyFriendsWith($body_data["sender_id"], $body_data["receiver_id"])) {
            http_response_code(409);
            echo json_encode([
                "message" => "You are already friends with that user",
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
        
        # Accept request if the receiver of this request already sent a request to this sender
        $request_id = $this->socials_gateway->checkRequestExists($body_data["receiver_id"], $body_data["sender_id"]);
        if ($request_id != null) {
            $body_data["request_id"] = $request_id;

            # Swap sender and receiver id so the token validation works
            $sender_id = $body_data["receiver_id"];
            $body_data["receiver_id"] = $body_data["sender_id"];
            $body_data["sender_id"] = $sender_id;

            echo json_encode([
                "message" => "Another request from the user you are trying to request friendship already exists. System will automatically accept that request"
            ]);

            $this->acceptFriendRequest($parameters, $body_data);    
            return;
        }

        $this->socials_gateway->sendFriendRequest($body_data["sender_id"], $body_data["receiver_id"]);
        echo json_encode([
            "message" => "Friend request sent successfully"
        ]);
    }

    public function getFriendRequests($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["token", "receiver_id"])) {
            return;
        }

        if (!$this->users_gateway->validateToken($body_data["receiver_id"], $body_data["token"])) {
            http_response_code(401);
            echo json_encode([
                "message" => "You don't have permission to make this action",
                "error" => "Invalid token"
            ]);
            return;
        }

        $requests = $this->socials_gateway->getFriendRequests($body_data["receiver_id"]);
        echo json_encode([
            "friend_requests" => $requests
        ]);
    }

    public function acceptFriendRequest($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["token", "receiver_id", "request_id"])) {
            return;
        }

        if (!$this->users_gateway->validateToken($body_data["receiver_id"], $body_data["token"])) {
            http_response_code(401);
            echo json_encode([
                "message" => "You don't have permission to perform this action",
                "error" => "Invalid token"
            ]);
            return;
        }

        $this->socials_gateway->acceptFriendRequest($body_data["request_id"]);

        http_response_code(200);
        echo json_encode([
            "message" => "Request accepted successfully"
        ]);
    }
}