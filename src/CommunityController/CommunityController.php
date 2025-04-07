<?php

require_once __DIR__ . "/CommunityGateway.php";

class CommunityController implements RequestHandler {
    private CommunityGateway $community_gateway;
    private UsersGateway $users_gateway;

    function __construct() {
        $this->community_gateway = new CommunityGateway();
        $this->users_gateway = new UsersGateway();
    }
    

    public function createCommunity($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["name", "user_id", "token"])) {
            return;
        }
        
        if (!$this->users_gateway->validateToken($body_data["user_id"], $body_data["token"])) {
            http_response_code(401);
            echo json_encode([
                "message" => "You don't have permission to perform this action",
                "error" => "Invalid token"
            ]);
            return;
        }

        if (!empty($this->community_gateway->getCommunity($body_data["name"]))) {
            http_response_code(409);
            echo json_encode([
                "message" => "This name is already in use"
            ]);
            return;
        }

        $description = "";
        if (key_exists("description", $body_data)) {
            $description = $body_data["description"];
        }

        $this->community_gateway->createCommunity($body_data["user_id"], $body_data["name"], $description);
        http_response_code(201);
        echo json_encode([
            "message" => "Community created successfully"
        ]);
    }

    public function getCommunities($parameters, $body_data) {
        $data = $this->community_gateway->getCommunities();
        echo json_encode($data);
    }

    public function getCommunity($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["community_name"])) {
            return;
        }

        $data = $this->community_gateway->getCommunity($body_data["community_name"]);
        if (empty($data)) {
            http_response_code(404);
            echo json_encode([
                "message" => "Couldn't find any community with this name"
            ]);
            return;
        }

        echo json_encode($data);
    }

    public function joinCommunity($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["token", "user_id", "community_id"])) {
            return;
        }

        if (!$this->users_gateway->validateToken($body_data["user_id"], $body_data["token"])) {
            return;
        }

        if (!$this->handleCommunityExists($body_data["community_id"])) {
            return;
        }

        if ($this->community_gateway->alreadyMemberOf($body_data["user_id"], $body_data["community_id"])) {
            http_response_code(409);
            echo json_encode([
                "message" => "You are already a member of this community"
            ]);
            return;
        }

        $this->community_gateway->joinCommunity($body_data["user_id"], $body_data["community_id"]);
    }

    public function leaveCommunity($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["token", "user_id", "community_id"])) {
            return;
        }

        if (!$this->handleCommunityExists($body_data["community_id"])) {
            return;
        }

        if (!$this->community_gateway->alreadyMemberOf($body_data["user_id"], $body_data["community_id"])) {
            http_response_code(404);
            echo json_encode([
                "message" => "You aren't a member of this community"
            ]);
            return;
        }

        $this->community_gateway->leaveCommunity($body_data["user_id"], $body_data["community_id"]);
    }

    public function isMemberOf($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["user_id", "community_id"])) {
            return;
        }

        if (!$this->handleCommunityExists($body_data["community_id"])) {
            return;
        }

        if ($this->community_gateway->alreadyMemberOf($body_data["user_id"], $body_data["community_id"])) {
            echo json_encode([
                "message" => "The user is a member of this community",
                "is_member" => true
            ]);
            return;
        }

        echo json_encode([
            "message" => "The user is not a member of this community",
            "is_member" => false
        ]);
    }


    function handleCommunityExists(String $community_id) : bool {
        if (!$this->community_gateway->communityExists($community_id)) {
            http_response_code(404);
            echo json_encode([
                "message" => "Couldn't find any community with this id"
            ]);
            return false;
        }

        return true;
    }
}
