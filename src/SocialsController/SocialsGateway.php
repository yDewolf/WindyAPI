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

    public function getFriendRequests(string $receiver_id): array {
        $sql = "SELECT F.request_id, U.nickname FROM users U 
                INNER JOIN friend_requests F ON U.id = F.sender 
                WHERE F.receiver = :receiver";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":receiver", $receiver_id, PDO::PARAM_INT);

        $stmt->execute();

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[$row["nickname"]] = $row["request_id"];
        }

        return $data;
    }

    public function acceptFriendRequest(string $request_id) {
        # Get the request data
        $get_request = "SELECT * FROM friend_requests WHERE request_id = :request_id";

        $stmt = $this->conn->prepare($get_request);
        $stmt->bindValue(":request_id", $request_id, PDO::PARAM_INT);

        $stmt->execute();
        $request_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        # Delete the friend request
        $this->deleteFriendRequest($request_id);

        # Create a new friendship between the two users
        $sql = "INSERT INTO friendships (user_1, user_2) 
                VALUES (:receiver_id, :sender_id)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":receiver_id", $request_data["receiver"], PDO::PARAM_INT);
        $stmt->bindValue(":sender_id", $request_data["sender"], PDO::PARAM_INT);

        $stmt->execute();
    }

    public function deleteFriendRequest(string $request_id) {
        $del_request = "DELETE FROM friend_requests
                        WHERE request_id = :request_id";

        $stmt = $this->conn->prepare($del_request);
        $stmt->bindValue(":request_id", $request_id, PDO::PARAM_INT);

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

    public function getRequestId(string $sender_id, string $receiver_id): string {
        $sql = "SELECT request_id FROM friend_requests 
                WHERE receiver = :receiver AND sender = :sender";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":sender", $sender_id, PDO::PARAM_STR);
        $stmt->bindValue(":receiver", $receiver_id, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC)["request_id"] ?? null;
    }

    public function getFriendships(string $user_id) {
        $sql = "SELECT nickname FROM users U 
                INNER JOIN friendships F ON U.id = F.user_1 OR U.id = F.user_2 
                WHERE :user_id IN (F.user_1, F.user_2) AND NOT U.id = :user_id1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_STR);
        $stmt->bindValue(":user_id1", $user_id, PDO::PARAM_STR);

        $stmt->execute();

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row["nickname"];
        }

        return $data;
    }

    public function removeFriendship(string $user_id, string $other_user) {
        $sql = "DELETE FROM friendships 
                WHERE :user_id IN (user_1, user_2) AND :other_user IN (user_1, user_2)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_STR);
        $stmt->bindValue(":other_user", $other_user, PDO::PARAM_STR);

        $stmt->execute();
    }   

    public function alreadyFriendsWith(string $user_id, string $other_user): bool {
        $sql = "SELECT COUNT(*) FROM friendships 
                WHERE :user_id IN (user_1, user_2) AND :other_user IN (user_1, user_2)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_STR);
        $stmt->bindValue(":other_user", $other_user, PDO::PARAM_STR);

        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC)["COUNT(*)"];
    }
}