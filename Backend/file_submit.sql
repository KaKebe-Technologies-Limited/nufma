CREATE DATABASE film_submissions;

USE film_submissions;

CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    company VARCHAR(255),
    email VARCHAR(255) NOT NULL,
    category VARCHAR(255) NOT NULL,
    file VARCHAR(255),
    projectLink VARCHAR(255),
    summary TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);