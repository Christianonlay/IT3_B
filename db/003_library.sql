CREATE TABLE IF NOT EXISTS student (
    student_id INT AUTO_INCREMENT PRIMARY KEY,

    student_first_name VARCHAR(50) NOT NULL,
    student_last_name VARCHAR(50) NOT NULL,
    student_course VARCHAR(50) NOT NULL,

    student_created_at TIMWSTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP

)ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,

    book_title VARCHAR(100) NOT NULL,
    book_author VARCHAR(100) NOT NULL,
    book_category VARCHAR(50) NOT NULL,

    book_created_at TIMESTAMP NOT NULL
    DEFAULT CURRENT_TIMESTAMP

)ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS borrow (
     borrow_id INT AUTO_INCREMENT PRIMARY KEY,

     student_id INT NOT NULL,
     book_id INT NOT NULL,

     borrow_date TIMWSTAMP NOT NULL
     DEFAULT CURRENT_TIMESTAMP,

     borrow_return_date TIMESTAMP NULL
     DEFAULT NULL,

     CONSTRAINT fk_borrow_student
     FOREIGN KEY (student_id)
     REFERENCES student(student_id)
     ON UPDATE CASCADE
     ON DELETE RESTRICT, 

     CONSTRAINT fk_borrow_book
     FOREIGN KEY (book_id)
     REFERENCES books(book_id)
     ON UPDATE CASCADE
     ON DELETE RESTRICT



)ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;


INSERT INTO student(
    student_first_name,
    student_last_name,
    student_course
) VALUES
    ('CHRISTAIN JAY','ONLAY','BSIT'),
    ('DODONG','CUIZON','ITE'),
    ('JAYVIE','OMONGOS','BA');



INSERT INTO books(
    book_title,
    book_author,
    book_category
) VALUES
    ('THE HABIT','J.R.R TOLKIEN','FANTASY'),
    ('THE ILIAD','HOMER','EPIC POETRY'),
    ('DRADULA','BRAM STOKER','HORROR');

    INSERT INTO borrow(
        student_id,
        book_id
    )VALUES
    (1,4),
    (1,3),
    (1,3);