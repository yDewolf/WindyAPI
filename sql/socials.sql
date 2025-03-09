CREATE TABLE friend_requests (
    request_id INT AUTO_INCREMENT,
    sender INT NOT NULL,
    receiver INT NOT NULL,
    PRIMARY KEY (request_id),
    CONSTRAINT FK_SenderId FOREIGN KEY (sender) REFERENCES users(id),
    CONSTRAINT Fk_ReceiverId FOREIGN KEY (receiver) REFERENCES users(id)
);

CREATE TABLE friendships (
    user_1 INT NOT NULL,
    user_2 INT NOT NULL,
    CONSTRAINT FK_UserID1 FOREIGN KEY (user_1) REFERENCES users(id),
    CONSTRAINT Fk_UserID2 FOREIGN KEY (user_2) REFERENCES users(id)
);