<?php

require __DIR__ . "src/utils/RandomTokenGen.php";

class UsersGateway {
    private PDO $conn;

    public function __construct() {
        $database = new Database("localhost", "windy_db", "root", "");
        $this->conn = $database->getConnection();
    }

    public function getAll(): Array {
        $sql = "SELECT id, username FROM users";
        
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
        $stmt->bindValue(":email", $data["email"], PDO::PARAM_STR);
        $stmt->bindValue(":password", $data["password"], PDO::PARAM_STR);
        $stmt->bindValue(":nickname", $data["nickname"] ?? $data["username"], PDO::PARAM_STR);

        $stmt->bindValue(":token", random_text('alnum', 16), PDO::PARAM_STR);

        $stmt->execute();

        # Check this later (Kokuro method)
        return $this->conn->lastInsertId();
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

        $stmt->bindValue(":password", $new_data["password"] ?? $current_data["password"], PDO::PARAM_STR);
        $stmt->bindValue(":nickname", $new_data["nickname"] ?? $current_data["nickname"], PDO::PARAM_STR);
        
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

    public function checkCorrectPassword(string $id, string $password): bool {
        $sql = "SELECT COUNT(*) FROM users
                WHERE id = :id && password = :password";
        
        $stmt = $this->conn->prepare($sql);
    
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->bindValue(":password", $password, PDO::PARAM_STR);

        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC)["COUNT(*)"];
    }
}