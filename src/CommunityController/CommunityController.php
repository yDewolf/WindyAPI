<?php

require_once __DIR__ . "/CommunityGateway.php";

enum CommunityRoles: int {
    case MEMBER = 1;
    case MODERATOR = 2;
    case ADMIN = 3;
    case CO_OWNER = 4;
    case OWNER = 5;
};

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

    public function updateCommunity($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["community_id", "user_id", "token"])) {
            return;
        }

        if (!handleTokenValidation($this->users_gateway, $body_data)) { return; }

        $perm_level = $this->community_gateway->getUserRole($body_data["user_id"], $body_data["community_id"])["perm_level"];
        if ($perm_level < 3) {
            http_response_code(401);
            echo json_encode([
                "message" => "You don't have permission to perform this action",
            ]);
            return;
        }

        $data = $this->community_gateway->getcommunity($body_data["community_id"]);
        $this->community_gateway->updateCommunity($body_data["community_id"], $body_data["description"] ?? $data["description"]);
        echo json_encode([
            "message" => "Your community has been updated succesfully"
        ]);
    }

    public function deleteCommunity($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["community_id", "user_id", "token", "password"])) {
            return;
        }

        if (!$this->users_gateway->checkCorrectPassword($body_data["user_id"], $body_data["password"])) {
            http_response_code(401);
            echo json_encode([
                "message" => "Incorrect password"
            ]);

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

        $perm_level = $this->community_gateway->getUserRole($body_data["user_id"], $body_data["community_id"])["perm_level"];
        if ($perm_level < 3) {
            http_response_code(401);
            echo json_encode([
                "message" => "You don't have permission to perform this action",
            ]);
            return;
        }

        $this->community_gateway->deleteCommunity($body_data["community_id"]);
        echo json_encode([
            "message" => "Your community has been deleted succesfully"
        ]);
    }

    public function getCommunities($parameters, $body_data) {
        $data = $this->community_gateway->getCommunities();
        echo json_encode($data);
    }

    public function getCommunity($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["community_id"])) {
            return;
        }

        $data = $this->community_gateway->getCommunity($body_data["community_id"]);
        if (empty($data)) {
            http_response_code(404);
            echo json_encode([
                "message" => "Couldn't find any community with this name"
            ]);
            return;
        }

        echo json_encode($data);
    }

    public function getCommunityMembers($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["community_id"])) {
            return;
        }

        if (!$this->handleCommunityExists($body_data["community_id"])) { return; }

        $data = $this->community_gateway->getCommunityMembers($body_data["community_id"]);
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
        echo json_encode([
            "message" => "You joined the community succesfully"
        ]);
    }

    public function leaveCommunity($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["token", "user_id", "community_id"])) {
            return;
        }

        if (!handleTokenValidation($this->users_gateway, $body_data)) { return; }

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
        echo json_encode([
            "message" => "You left the community succesfully"
        ]);
    }

    public function updateMemberRole($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["community_id", "user_id", "token", "target_user_id", "new_role_id"])) {
            return;
        }

        if (!$this->handleCommunityExists($body_data["community_id"])) { return; }

        if (!handleTokenValidation($this->users_gateway, $body_data)) { return; }

        if ((int) $body_data["new_role_id"] == CommunityRoles::OWNER->value) {
            http_response_code(400);
            echo json_encode([
                "message" => "Please use the 'transfer-ownership' route to transfer ownership",
                "error" => "Wrong route"
            ]);
        }

        if ((int) $body_data["user_id"] == (int) $body_data["target_user_id"]) {
            http_response_code(response_code: 401);
            echo json_encode([
                "message" => "You can't change your role",
                "error" => "You don't have permission to perform this action"
            ]);
            return;
        }

        if (!$this->community_gateway->alreadyMemberOf($body_data["target_user_id"], $body_data["community_id"])) {
            http_response_code(response_code: 400);
            echo json_encode([
                "message" => "The user has to be at least a member of the community",
                "error" => "Bad Request"
            ]);
            return;
        }

        $new_role = $body_data["new_role_id"];
        $role_data = $this->community_gateway->getRole($new_role);
        // Check if the role is valid
        if ((int) $new_role < 0 || empty($role_data)) {
            http_response_code(422);
            echo json_encode([
                "message" => "Invalid role id"
            ]);
            return;
        }

        $user_role = $this->community_gateway->getUserRole($body_data["target_user_id"], $body_data["community_id"]);
        if ((int) $user_role["role_id"] == (int) $body_data["new_role_id"]) {
            http_response_code(409);
            echo json_encode([
                "message" => "The user already has the role"
            ]);
            return;
        }

        $user_perm_level = $user_role["perm_level"];
        if ((int) $user_perm_level <= (int) $new_role) {
            http_response_code(response_code: 401);
            echo json_encode([
                "message" => "Can't change the role of a member to a role with higher or equal permission level than you",
                "error" => "You don't have permission to perform this action"
            ]);
            return;
        }

        if ((int) $user_perm_level <= (int) $this->community_gateway->getUserRole($body_data["target_user_id"], $body_data["community_id"])["perm_level"]) {
            http_response_code(response_code: 401);
            echo json_encode([
                "message" => "You can only change roles of members with less permission level than you",
                "error" => "You don't have permission to perform this action"
            ]);
            return;
        }


        $this->community_gateway->updateMemberRole($body_data["community_id"], $body_data["target_user_id"], $new_role);
        echo json_encode([
            "message" => "Changed the role of user {$body_data['target_user_id']} succesfully"
        ]);
    }

    public function transferOwnership($parameters, $body_data) {
        if (!handleValidationErrors($body_data, true, ["community_id", "user_id", "token", "password", "target_user_id"])) {
            return;
        }

        if (!$this->handleCommunityExists($body_data["community_id"])) { return; }

        if (!handleTokenValidation($this->users_gateway, $body_data)) { return; }
        
        if (!$this->users_gateway->checkCorrectPassword($body_data["user_id"], $body_data["password"])) {
            http_response_code(401);
            echo json_encode([
                "message" => "Incorrect password"
            ]);

            return;
        }
       
        if (!$this->community_gateway->alreadyMemberOf($body_data["target_user_id"], $body_data["community_id"])) {
            http_response_code(response_code: 400);
            echo json_encode([
                "message" => "The user has to be at least a member of the community",
                "error" => "Bad Request"
            ]);
            return;
        }

        $user_role = $this->community_gateway->getUserRole($body_data["user_id"], $body_data["community_id"]);
        if ($user_role["role_id"] != CommunityRoles::OWNER->value) {
            http_response_code(response_code: 401);
            echo json_encode([
                "message" => "You have to be the owner of the community to transfer ownership",
                "error" => "You don't have permission to perform this action"
            ]);
            return;
        }

        if ((int) $body_data["user_id"] == (int) $body_data["target_user_id"]) {
            http_response_code(response_code: 400);
            echo json_encode([
                "message" => "You are already owner of this community",
                "error" => "Bad Request"
            ]);
            return;
        }

        $this->community_gateway->transferOwnership($body_data["community_id"], $body_data["target_user_id"]);
        $this->community_gateway->updateMemberRole($body_data["community_id"], $body_data["user_id"], CommunityRoles::CO_OWNER->value);
        
        echo json_encode([
            "message" => "Transferred ownership to {$body_data['target_user_id']} successfully | You are now a co-owner of the community"
        ]);
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
