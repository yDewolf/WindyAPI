CREATE DATABASE windy_db;

USE windy_db;

CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    username varchar(32) NOT NULL,
    nickname varchar(32) NOT NULL,
    email varchar(64) NOT NULL,
    password varchar(128) NOT NULL,
    token char(16) NOT NULL,
    register_date date DEFAULT CURRENT_DATE,
    PRIMARY KEY (id),
    CONSTRAINT UC_Username UNIQUE (username),
    CONSTRAINT UC_Token UNIQUE (token),
    CONSTRAINT UC_Email UNIQUE (email)
);