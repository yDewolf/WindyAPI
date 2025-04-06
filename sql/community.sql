CREATE TABLE community (
    id int AUTO_INCREMENT,
    owner_id int NOT NULL,
    name varchar(100) NOT NULL,
    description varchar(250),
    PRIMARY KEY (id),
    CONSTRAINT FK_OwnerId FOREIGN KEY (owner_id) REFERENCES users(id)
);

CREATE TABLE community_roles (
    id int AUTO_INCREMENT,
    perm_level int NOT NULL check (perm_level between 0 AND 3),
    name varchar(50) NOT NULL,
    PRIMARY KEY (id)
);

INSERT INTO community_roles (name, perm_level) VALUES
    ("Member", 0),
    ("Moderator", 1),
    ("Admin", 2),
    ("Owner", 3);

CREATE TABLE community_members (
    user_id int NOT NULL,
    community_id int NOT NULL,
    role_id int NOT NULL,
    CONSTRAINT FK_UserId FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT FK_CommunityId FOREIGN KEY (community_id) REFERENCES community(id),
    CONSTRAINT FK_CommunityRole FOREIGN KEY (role_id) REFERENCES community_roles(id)
);

