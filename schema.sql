CREATE DATABASE IF NOT EXISTS sebmus;
CREATE USER IF NOT EXISTS 'sebmus'@'%' IDENTIFIED BY 'ecbuPyfV';
GRANT ALL PRIVILEGES ON sebmus.* TO 'sebmus'@'%';

CREATE DATABASE IF NOT EXISTS filestorage;
CREATE USER IF NOT EXISTS 'filestorage'@'%' IDENTIFIED BY 'filestorage';
GRANT ALL PRIVILEGES ON filestorage.* TO 'filestorage'@'%';
