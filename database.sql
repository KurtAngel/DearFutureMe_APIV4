create database if not exists `dearfutureme`;
use `dearfutureme`;

create table if not exists `users` (
    `id` int primary key auto_increment,
    `name` varchar(255) not null,
    `email` varchar(255) not null,
    `password` varchar(255) not null,
    `created_at` timestamp default current_timestamp,
    `updated_at` timestamp default current_timestamp on update current_timestamp
);

create table if not exists 'draft_capsules' (
    `id` int primary key auto_increment,
    `user_id` int not null,
    `title` varchar(255) not null,
    `content` text not null,
    'image' varchar(255) null,
    `created_at` timestamp default current_timestamp,
    `updated_at` timestamp default current_timestamp on update current_timestamp
);

create table if not exists 'received_capsules' (
    'id' int primary key auto_increment,
    'user_id' null,
    'title' varchar (255) null,
    'content' text null,
    'image ' varchar(255) not null,
    'created_at' timestamp default current_timestamp,
    'updated_at' timestamp default current_timestamp on update current_timestamp
);

create table if not exists 'sent_capsules' (
    'id' int primary key auto_increment,
    'user_id' null,
    'title' varchar (255) null,
    'content' text null,
    'image ' varchar(255) not null,
    'created_at' timestamp default current_timestamp,
    'updated_at' timestamp default current_timestamp on update current_timestamp
);

create table if not exists 'images' (
    'id' int primary key auto_increment,
    'user_id' null,
    'title' varchar (255) null,
    'content' text null,
    'image ' varchar(255) not null,
    'created_at' timestamp default current_timestamp,
    'updated_at' timestamp default current_timestamp on update current_timestamp
);

create table if not exists 'received_images' (
    'id' int primary key auto_increment,
    'user_id' null,
    'title' varchar (255) null,
    'content' text null,
    'image ' varchar(255) not null,
    'created_at' timestamp default current_timestamp,
    'updated_at' timestamp default current_timestamp on update current_timestamp
);