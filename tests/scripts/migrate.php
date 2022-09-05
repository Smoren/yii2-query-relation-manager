<?php

$config = require __DIR__.'/../unit/Config/db.php';

$dbh = new PDO(
    $config['dsn'],
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Connected. Creating tables...\n";

$dbh->query(
    'CREATE TABLE `city` (
        `id` int NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;'
);

$dbh->query(
    'CREATE TABLE `address` (
        `id` int NOT NULL AUTO_INCREMENT,
        `city_id` int NOT NULL,
        `name` varchar(255) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx-address-city_id` (`city_id`),
        CONSTRAINT `fk-address-city_id-city-id` FOREIGN KEY (`city_id`) REFERENCES `city` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;'
);

$dbh->query(
    'CREATE TABLE `place` (
        `id` int NOT NULL AUTO_INCREMENT,
        `address_id` int NOT NULL,
        `name` varchar(255) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx-place-address_id` (`address_id`),
        CONSTRAINT `fk-place-address_id-address-id` FOREIGN KEY (`address_id`) REFERENCES `address` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;'
);

$dbh->query(
    'CREATE TABLE `comment` (
        `id` int NOT NULL AUTO_INCREMENT,
        `place_id` int NOT NULL,
        `username` varchar(255) NOT NULL,
        `mark` tinyint NOT NULL,
        `text` text NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx-comment-place_id` (`place_id`),
        CONSTRAINT `fk-comment-place_id-place-id` FOREIGN KEY (`place_id`) REFERENCES `place` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;'
);

echo "Inserting data...\n";

$dbh->query(
    "INSERT INTO city (id,name) VALUES
         (1,'Moscow'),
         (2,'St. Petersburg'),
         (3,'Samara'),
         (4,'Barnaul'),
         (5,'Ivanovo');"
);

$dbh->query(
    "INSERT INTO address (id,city_id,name) VALUES
         (1,1,'Tverskaya st., 7'),
         (2,1,'Schipok st., 1'),
         (3,2,'Mayakovskogo st., 12'),
         (4,2,'Galernaya st., 3');"
);

$dbh->query(
    "INSERT INTO place (id,address_id,name) VALUES
         (1,1,'TC Tverskoy'),
         (2,1,'Tverskaya cafe'),
         (3,2,'Stasova music school'),
         (4,3,'Hostel on Mayakovskaya'),
         (5,3,'Mayakovskiy Store'),
         (6,4,'Cafe on Galernaya');"
);

$dbh->query(
    "INSERT INTO comment (id,place_id,username,mark,`text`) VALUES
         (1,1,'Ivan Mustafaevich',3,'Not bad, not good'),
         (2,1,'Peter',5,'Good place'),
         (3,1,'Mark',1,'Bad place'),
         (4,3,'Ann',5,'The best music school!'),
         (5,5,'Stas',4,'Rather good place'),
         (6,6,'Stas',3,'Small menu, long wait');"
);

echo "Migration complete!\n";
