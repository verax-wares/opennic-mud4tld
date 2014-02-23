CREATE TABLE oz_domains (
domain VARCHAR(64) PRIMARY KEY,
name VARCHAR(20) NOT NULL,
email VARCHAR(50) NOT NULL,
ns1 VARCHAR(30) NOT NULL,
ns2 VARCHAR(30) NOT NULL,
ns1_ip VARCHAR(16),
ns2_ip VARCHAR(16),
registered DATE NOT NULL,
expires DATE,
updated DATE NOT NULL,
userid INT NOT NULL);

create table users (
userid INTEGER PRIMARY KEY,
username VARCHAR(20) NOT NULL,
password VARHCAR(32) NOT NULL,
name VARCHAR(50) NOT NULL,
email VARCHAR(50) NOT NULL,
country VARCHAR(2),
registered DATE NOT NULL,
verified INT NOT NULL);

create table registrars (
r_userid INTEGER PRIMARY KEY,
r_user VARCHAR(10) NOT NULL,
r_userkey VARCHAR(16) NOT NULL,
r_name VARCHAR(20) NOT NULL,
r_contact VARCHAR(20) NOT NULL,
r_email VARCHAR(50) NOT NULL,
r_url VARCHAR(50) NOT NULL);