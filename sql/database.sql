CREATE DATABASE windy_db;

CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    username varchar(64) NOT NULL,
    nickname varchar(64) NOT NULL,
    email varchar(200) NOT NULL,
    password varchar(256) NOT NULL,
    PRIMARY KEY (id)
);