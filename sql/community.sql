CREATE TABLE communities (
    id int AUTO_INCREMENT,
    owner_id int NOT NULL,
    name varchar(32) NOT NULL,
    description varchar(256),
    creation_date date DEFAULT CURRENT_DATE,
    PRIMARY KEY (id),
    CONSTRAINT FK_OwnerId FOREIGN KEY (owner_id) REFERENCES users(id),
    CONSTRAINT UC_Name UNIQUE (name)
);

CREATE TABLE community_roles (
    id int AUTO_INCREMENT,
    perm_level int NOT NULL check (perm_level between 0 AND 3),
    role_name varchar(32) NOT NULL,
    PRIMARY KEY (id)
);

INSERT INTO community_roles (role_name, perm_level) VALUES
    ("Member", 0),
    ("Moderator", 1),
    ("Admin", 2),
    ("Co-Owner", 3);
    ("Owner", 4);

CREATE TABLE community_members (
    user_id int NOT NULL,
    community_id int NOT NULL,
    role_id int NOT NULL,
    join_date date DEFAULT CURRENT_DATE,
    CONSTRAINT FK_UserId FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT FK_CommunityId FOREIGN KEY (community_id) REFERENCES communities(id),
    CONSTRAINT FK_CommunityRole FOREIGN KEY (role_id) REFERENCES community_roles(id)
);

CREATE TABLE community_posts (
    id int AUTO_INCREMENT,
    user_id int NOT NULL,
    community_id int NOT NULL,
    post_timestamp timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (community_id) REFERENCES communities(id)
);
