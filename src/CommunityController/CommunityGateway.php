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
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = $row["id"];
            unset($row["id"]);
            
            $data[$id] = $row;
        }

        return $data;
    }

    public function getCommunity(string $community_id) : array | false {
        $sql = "SELECT * FROM communities
                WHERE id = :community_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":community_id", $community_id, PDO::PARAM_INT);

        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data;
    }

    public function getCommunityMembers(string $community_id) : array {
        $sql = "SELECT U.id, U.nickname, CR.role_name FROM communities C
                INNER JOIN community_members CM, users U, community_roles CR
                WHERE CM.community_id = C.id AND C.id = :community_id AND U.id = CM.user_id AND CR.id = CM.role_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":community_id", $community_id, PDO::PARAM_INT);

        $stmt->execute();
        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = $row["id"];
            unset($row["id"]);

            $data[$id] = $row;
        }

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

    public function joinCommunity(string $user_id, string $community_id, int $role_id = 1) {
        $sql = "INSERT INTO community_members (user_id, community_id, role_id)
                VALUES (:user_id, :community_id, :role_id)";

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

    public function updateCommunity(string $community_id, string $description) {
        $sql = "UPDATE communities
                SET description = :description
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $community_id, PDO::PARAM_STR);
        $stmt->bindValue(":description", $description, PDO::PARAM_STR);

        $stmt->execute();
    }

    public function deleteCommunity(string $community_id) {
        $sql = "DELETE FROM community_members
                WHERE community_id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $community_id, PDO::PARAM_STR);

        $stmt->execute();

        $sql = "DELETE FROM communities
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $community_id, PDO::PARAM_STR);

        $stmt->execute();
    }


    public function updateMemberRole(string $community_id, string $user_id, string $new_role_id) {
        $sql = "UPDATE community_members
                SET role_id = :role_id
                WHERE 
                    community_id = :community_id AND
                    user_id = :user_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":community_id", $community_id, PDO::PARAM_INT);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindValue(":role_id", $new_role_id, PDO::PARAM_INT);

        $stmt->execute();
    }

    public function transferOwnership(string $community_id, string $user_id) {
        $this->updateMemberRole($community_id, $user_id, CommunityRoles::OWNER->value);
        
        $sql = "UPDATE communities
                SET owner_id = :owner_id
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":id", $community_id, PDO::PARAM_INT);
        $stmt->bindValue(":owner_id", $user_id, PDO::PARAM_INT);

        $stmt->execute();
    }
    

    public function getRole(string $role_id) {
        $sql = "SELECT * FROM community_roles WHERE id = :role_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":role_id", $role_id, PDO::PARAM_INT);

        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserRole(string $user_id, string $community_id): array | false {
        $sql = "SELECT R.perm_level, R.role_name, M.role_id FROM community_members M
                INNER JOIN community_roles R
                WHERE R.id = M.role_id AND M.user_id = :user_id AND M.community_id = :community_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->bindValue(":community_id", $community_id, PDO::PARAM_INT);

        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
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