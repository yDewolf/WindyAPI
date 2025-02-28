<?php

class UsersGateway {
    private PDO $conn;

    public function __construct(Database $database) {
        $this->conn = $database->getConnection();
    }

    public function getAll(): Array {
        $sql = "SELECT nickname FROM users";
        
        $stmt = $this->conn->query($sql);

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            # Append row to data variable
            $data[] = $row;
        }

        return $data;
    }
}