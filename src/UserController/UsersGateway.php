<?php

class UsersGateway {
    private PDO $conn;

    public function __construct() {
        $database = new Database("localhost", "windy_db", "root", "");
        $this->conn = $database->getConnection();
    }

    public function getAll(): Array {
        $sql = "SELECT id, username, nickname FROM users";
        
        $stmt = $this->conn->query($sql);

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            # Append row to data variable
            $id = $row["id"];
            unset($row["id"]);

            $data[$id] = $row;
        }

        return $data;
    }

    public function createUser(array $data) {
        $sql = "INSERT INTO users (username, email, password, nickname, token)
                VALUES (:username, :email, :password, :nickname, :token)";
        
        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":username", $data["username"], PDO::PARAM_STR);
        $stmt->bindValue(":email", password_hash($data["email"], PASSWORD_DEFAULT), PDO::PARAM_STR);
        $stmt->bindValue(":password", password_hash($data["password"], PASSWORD_DEFAULT), PDO::PARAM_STR);
        $stmt->bindValue(":nickname", $data["nickname"] ?? $data["username"], PDO::PARAM_STR);

        $stmt->bindValue(":token", random_text('alnum', 16), PDO::PARAM_STR);

        $stmt->execute();

        $sql = "SELECT id, token FROM users
                WHERE username = :username";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":username", $data["username"], PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUser(string $id): array | false {
        $sql = "SELECT * FROM users WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        # Get return values
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    public function updateUser(array $current_data, array $new_data): int {
        $sql = "UPDATE users
                SET nickname = :nickname, password = :password
                WHERE id = :id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(":password", password_hash($new_data["password"], PASSWORD_DEFAULT) ?? $current_data["password"], PDO::PARAM_STR);
        $stmt->bindValue(":nickname", password_hash($new_data["nickname"], PASSWORD_DEFAULT) ?? $current_data["nickname"], PDO::PARAM_STR);
        
        $stmt->bindValue(":id", $current_data["id"], PDO::PARAM_INT);
        
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function deleteUser(string $id): int {
        $sql = "DELETE FROM users
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
    
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);

        $stmt->execute();
        
        return $stmt->rowCount();
    }

    public function getUserToken(string $username, string $password): array {
        $sql = "SELECT password, token FROM users
                WHERE username = :username";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":username", $username, PDO::PARAM_STR);
        
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $data["password"])) {
            return $data;
        }

        return [];
    }

    public function checkCorrectPassword(string $id, string $password): bool {
        $sql = "SELECT COUNT(*) FROM users
                WHERE id = :id && password = :password";
        
        $stmt = $this->conn->prepare($sql);
    
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->bindValue(":password", $password, PDO::PARAM_STR);

        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC)["COUNT(*)"];
    }

    public function validateToken(string $id, string $token): bool {
        $sql = "SELECT COUNT(*) FROM users
                WHERE id = :id && token = :token";
        
        $stmt = $this->conn->prepare($sql);
    
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->bindValue(":token", $token, PDO::PARAM_STR);

        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC)["COUNT(*)"];
    }

    public function checkDuplicateUserField(string $field, string $value) {
        $sql = "SELECT COUNT(*) FROM users
                WHERE $field = :$field";
        
        $stmt = $this->conn->prepare($sql);
    
        $stmt->bindValue(":$field", $value, PDO::PARAM_STR);

        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC)["COUNT(*)"];
    }
}