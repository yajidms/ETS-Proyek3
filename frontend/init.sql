
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'mahasiswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE courses (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sks INTEGER NOT NULL,
    description TEXT
);

CREATE TABLE enrollments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE (user_id, course_id)
);

INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'furina@email.com', '$2a$10$c.OivY9w.9j3c.3j6E/xP.wL3j.5K5a5j5j5j5j5j5j5j5j5j5j5', 'admin'),
('Mahasiswa User', 'hutao@email.com', '$2a$10$c.OivY9w.9j3c.3j6E/xP.wL3j.5K5a5j5j5j5j5j5j5j5j5j5j5', 'mahasiswa');

INSERT INTO courses (name, sks, description) VALUES
('Kalkulus', 3, 'Mata kuliah dasar tentang limit, turunan, dan integral.'),
('Fisika Dasar', 3, 'Mata kuliah pengantar konsep-konsep dasar fisika.'),
('Algoritma dan Pemrograman', 4, 'Mata kuliah tentang logika pemrograman dan struktur data.'),
('Basis Data', 3, 'Mata kuliah tentang desain dan manajemen database relasional.');

INSERT INTO enrollments (user_id, course_id) VALUES
(2, 1),
(2, 4);
