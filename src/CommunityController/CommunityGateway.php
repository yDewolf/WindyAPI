<?php

class CommunityGateway {
    private PDO $conn;

    public function __construct() {
        $database = new Database("localhost", "windy_db", "root", "");
        
        $this->conn = $database->getConnection();
    }

    public function getCommunities(): array {
        $sql = "SELECT * FROM communities";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    public function getCommunity(string $community_name) : array | false {
        $sql = "SELECT * FROM communities
                WHERE name = :community_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":community_name", $community_name, PDO::PARAM_STR);

        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    public function alreadyMemberOf(string $user_id, string $community_id) {
        $sql = "SELECT COUNT(*) FROM community_members 
                WHERE user_id = :user_id AND community_id = :community_id;";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindValue(":community_id", $community_id, PDO::PARAM_INT);

        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC)["COUNT(*)"];
    }

    public function joinCommunity(string $user_id, string $community_id, int $role_id = 0) {
        $sql = "INSERT INTO community_members (user_id, community_id, role_id) VALUES
                (:user_id, :community_id, :role_id)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindValue(":community_id", $community_id, PDO::PARAM_INT);
        $stmt->bindValue(":role_id", $role_id, PDO::PARAM_INT);

        $stmt->execute();
    }

    public function leaveCommunity(string $user_id, string $community_id) {
        $sql = "DELETE FROM community_members
                WHERE user_id = :user_id AND community_id = :community_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindValue(":community_id", $community_id, PDO::PARAM_INT);

        $stmt->execute();
    }

    public function createCommunity(string $owner_id, string $name, string $description) {
        $sql = "INSERT INTO communities (owner_id, name, description) VALUES
                (:owner_id, :name, :description)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":owner_id", $owner_id, PDO::PARAM_INT);
        $stmt->bindValue(":name", $name, PDO::PARAM_STR);
        $stmt->bindValue(":description", $description, PDO::PARAM_STR);

        $stmt->execute();

        $sql = "SELECT id FROM communities
                WHERE name = :name";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":name", $name, PDO::PARAM_STR);

        $stmt->execute();

        $this->joinCommunity($owner_id, $stmt->fetch(PDO::FETCH_ASSOC)["id"], 4);
    }

    public function communityExists(string $community_id) {
        $sql = "SELECT COUNT(*) FROM communities
                WHERE id = :community_id";
        
        $stmt = $this->conn->prepare($sql);
    
        $stmt->bindValue(":community_id", $community_id, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC)["COUNT(*)"];
    }
}