<?php

class SocialsGateway {
    private PDO $conn;

    public function __construct() {
        $database = new Database("localhost", "windy_db", "root", "");
        
        $this->conn = $database->getConnection();
    }

    public function sendFriendRequest(string $sender_id, string $receiver_id) {
        $sql = "INSERT INTO friend_requests (sender, receiver)
        VALUES (:sender, :receiver)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":sender", $sender_id, PDO::PARAM_STR);
        $stmt->bindValue(":receiver", $receiver_id, PDO::PARAM_STR);

        $stmt->execute();
    }

    public function checkRequestExists(string $sender_id, string $receiver_id): bool {
        $sql = "SELECT COUNT(*) FROM friend_requests 
                WHERE receiver = :receiver AND sender = :sender";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":sender", $sender_id, PDO::PARAM_STR);
        $stmt->bindValue(":receiver", $receiver_id, PDO::PARAM_STR);

        $stmt->execute();
        
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC)["COUNT(*)"];
    }
}