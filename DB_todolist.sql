CREATE TABLE users (
       id int primary key auto_increment,
       nick varchar(80) unique,
       token varchar(255),
       password varchar(255),
       avatar varchar(64) unique,
       admin bool

);

CREATE TABLE tareas (
       id int primary key auto_increment,
       content text,
       color enum('white','gray','yellow'),
       user_id int
);
