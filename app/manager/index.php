<?php

// Database Connection
$host = 'localhost';
$db = 'it3b_lab_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host; dbname=$db; charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false
];

try{
    $pdo = new PDO($dsn,$user,$pass,$options);
}catch(PDOException $e){
    die("Database connection failed" . $e->getMessage());
}

// Session
session_start();

// Determin current section
$section = $_GET['section'] ?? 'student';

// CRUD Operations
$action = $_GET['action'] ?? '';

//-----------------------------------------------------------
// Students
//-----------------------------------------------------------

// Fetch students
if($section === 'student'){
    $stmt = $pdo->query("
        SELECT * 
        FROM student
        ORDER by student_id DESC
    ");

    $student = $stmt->fetchAll();
}

// Create Student
if($section==='student' && $action==='create'){

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $firstName = trim($_POST['student_first_name'] ?? '');
        $lastName = trim($_POST['student_last_name'] ?? '');
        $course = trim($_POST['student_course'] ?? '');

        if($firstName !== '' && $lastName !=='' && $course!==''){

            $sql=("
                INSERT INTO student (
                    student_first_name,
                    student_last_name,
                    student_course
                )
                VALUES (?,?,?)
            ");

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $firstName,
                $lastName,
                $course
            ]);

            //$_SESSION['alert'] = 'Student Saved Successfully';

            header("Location: index.php?section=student");
            exit;

        }
    }

}

// Update Student
if($section==='student'&& $action==='update'){
    
    $studentId = (int) ($_GET['id'] ?? 0);

    // Retrieve Student Information
    $stmt =  $pdo->prepare("
        SELECT *
        FROM student
        WHERE student_id =?
    ");

    $stmt->execute([$studentId]);

    $student = $stmt->fetch();

    // Update Student Info 
    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $firstName = trim($_POST['student_first_name'] ?? '');
        $lastName = trim($_POST['student_last_name'] ?? '');
        $course = trim($_POST['student_course'] ?? '');

        if($firstName !== '' && $lastName !=='' && $course!==''){

            $sql=("
                UPDATE student
                SET
                    student_first_name=?,
                    student_last_name=?,
                    student_course =?
                WHERE student_id=?
            ");

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $firstName,
                $lastName,
                $course,
                $studentId
            ]);

            $_SESSION['alert'] = 'Student Updated Successfully';

            header("Location: index.php?section=student");
            exit;

        }
    }

}


// Fetch books
if ($section === 'books') {

    $stmt = $pdo->query("
        SELECT *
        FROM books
        ORDER BY book_id DESC
    ");

    $books = $stmt->fetchAll();
}


// BOOKS CREATE
if ($section === 'books' && $action === 'create') {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $title    = trim($_POST['book_title'] ?? '');
        $author   = trim($_POST['book_author'] ?? '');
        $category = trim($_POST['book_category'] ?? '');

        if ($title !== '' && $author !== '' && $category !== '') {

            $stmt = $pdo->prepare("
                INSERT INTO books (
                    book_title,
                    book_author,
                    book_category
                )
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $title,
                $author,
                $category
            ]);

            header("Location: index.php?section=books");
            exit;
        }
    }
}

// BOOKS UPDATE
if ($section === 'books' && $action === 'update') {

    $bookId = (int) ($_GET['id'] ?? 0);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $title    = trim($_POST['book_title'] ?? '');
        $author   = trim($_POST['book_author'] ?? '');
        $category = trim($_POST['book_category'] ?? '');

        $stmt = $pdo->prepare("
            UPDATE books
            SET
                book_title = ?,
                book_author = ?,
                book_category = ?
            WHERE book_id = ?
        ");

        $stmt->execute([
            $title,
            $author,
            $category,
            $bookId
        ]);

        header("Location: index.php?section=books");
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM books
        WHERE book_id = ?
    ");

    $stmt->execute([$bookId]);

    $book = $stmt->fetch();

    if (!$book) {
        die("Book not found.");
    }
}

// Fetch Borrow Records
if($section === 'borrow'){

    // Fetch Student for borrow form
    $stmt=$pdo->query("
        SELECT
            student_id,
            student_first_name,
            student_last_name
        FROM student
        ORDER BY student_last_name,student_first_name
    ");

    $student  =  $stmt->fetchAll();

    // Fetch Books for borrow form
    $stmt=$pdo->query("
        SELECT
            book_id,
            book_title,
            book_author
        FROM books
        ORDER By book_title
    ");

    $books = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT
            borrow.borrow_id,
            borrow.borrow_date,
            borrow.borrow_return_date,

            student.student_first_name,
            student.student_last_name,

            books.book_title,
            books.book_author

        FROM borrow

        INNER JOIN student
            ON borrow.student_id = student.student_id

        INNER JOIN books
            ON borrow.book_id = books.book_id

        ORDER BY borrow.borrow_id DESC
    ");

    $borrowRecords = $stmt->fetchAll();
}

// Borrow a book
if($section==='borrow' && $action==="create"){

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        
        $studentId = (int)($_POST['student_id'] ?? 0);
        $bookId = (int)($_POST['book_id'] ?? 0);

        if($studentId > 0  && $bookId >0){

            // Check if student has an unreturned book
            $stmt = $pdo->prepare("
                SELECT borrow_id
                FROM borrow
                WHERE  student_id=?
                    AND borrow_return_date is NULL
                LIMIT 1
            ");

            $stmt->execute([$studentId]);
            $studentBorrow = $stmt->fetch();

            if($studentBorrow){
                $_SESSION['alert'] = 'This student cannot borrow another book because a previous book has not been returned';
            } else {

                // Check if book is already returned
                $stmt = $pdo->prepare("
                    SELECT borrow_id
                    FROM borrow
                    WHERE book_id=?
                        AND borrow_return_date is NULL
                    LIMIT 1
                ");

                $stmt->execute([$bookId]);
                $bookBorrow = $stmt->fetch();

                if($bookBorrow){
                    $_SESSION['alert'] = 'This book cannot be borrowed because it has not been returned';
                }else{

                    // Create borrow record finally hehehehe
                    $stmt = $pdo->prepare("
                        INSERT INTO borrow(
                            student_id,
                            book_id
                        )
                        VALUES(?,?)
                    ");

                    $stmt->execute([
                        $studentId,
                        $bookId
                    ]);

                    $_SESSION['alert'] = 'Book borrowed successfully';
                }
            }

            header("Location: index.php?section=borrow");
            exit;
        }

    }

}

// Return a book
if($section==='borrow' && $action==='return'){
    $borrowId = (int) ($_GET['id'] ??0);

    $stmt = $pdo->prepare("
        UPDATE borrow
        SET borrow_return_date = CURRENT_TIMESTAMP
        WHERE borrow_id = ?
    ");

    $stmt->execute([$borrowId]);

    $_SESSION['alert'] = "Book returned successfully.";

    header("Location: index.php?section=borrow");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System</title>
</head>
<body>
    <h1>Simple Library System</h1>
    <nav>
        <a href="index.php?section=student">Student</a>
        <a href="index.php?section=books">Books</a>
        <a href="index.php?section=borrow">Borrow</a>
    </nav>
    <hr>
    <?php if($section==='student'): ?>
        <h1>Student</h1>
        <p>
            <a href="index.php?section=student&action=create">
                Add Student
            </a>
        </p>

        <?php if($action==='create'): ?>

            <h2>Add student</h2>

            <form method="POST">
                <p>
                    <label>First Name</label>
                    <br>
                    <input type="text"
                           name="student_first_name"
                           required
                    />
                </p>
                <p>
                    <label>Last Name</label>
                    <br>
                    <input type="text"
                           name="student_last_name"
                           required
                    />
                </p>
                
                <p>
                    <label>Course</label>
                    <br>
                    <input type="text"
                           name="student_course"
                           required
                    />
                </p>

                <button type="submit">
                    Save
                </button>

                <a href="index.php?section=student">
                    Cancel
                </a>
            </form>

        <?php elseif($action==='update'): ?>

            <h2>Update Student</h2>

            <form method="POST">
                <p>
                    <label>First Name</label>
                    <br>
                    <input type="text"
                           name="student_first_name"
                           value="<?=  htmlspecialchars($student['student_first_name'])?>"
                           required
                    />
                </p>
                <p>
                    <label>Last Name</label>
                    <br>
                    <input type="text"
                           name="student_last_name"
                           value="<?=  htmlspecialchars($student['student_last_name'])?>"
                           required
                    />
                </p>
                
                <p>
                    <label>Course</label>
                    <br>
                    <input type="text"
                           name="student_course"
                           value="<?=  htmlspecialchars($student['student_course'])?>"
                           required
                    />
                </p>

                <button type="submit">

                    Update
                </button>

                <a href="index.php?section=student">
                    Cancel
                    
                </a>
            </form>

        <?php else: ?>
            
            <table>
                <table border="1" cellpadding="8">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Course</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>

                </thead>
                <tbody>
                    <?php foreach($student as $student): ?>
                        <tr>

                            <td>
                                <?= htmlspecialchars($student['student_id']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($student['student_first_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($student['student_last_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($student['student_course']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($student['student_created_at']) ?>
                            </td>
                            <td>
                                <a href="index.php?section=students&action=update&id=<?= $student['student_id'] ?>">Edit</a>
                                |
                                <a>Delete</a>
                            </td>

                        </tr>
                    <?php endforeach?>
                </tbody>
            </table>

        <?php endif; ?>

       
    <?php endif; ?>

    <?php if($section==='books'): ?>

            <h2>Books</h2>

            <p>
                <a href="index.php?section=books&action=create">
                    Add Book
                </a>
            </p>

            <?php if ($action === 'create'): ?>

                <h3>Add Book</h3>

                <form method="POST">

                    <p>
                        <label>Title:</label><br>
                        <input
                            type="text"
                            name="book_title"
                            required
                        >
                    </p>

                    <p>
                        <label>Author:</label><br>
                        <input
                            type="text"
                            name="book_author"
                            required
                        >
                    </p>

                    <p>
                        <label>Category:</label><br>
                        <input
                            type="text"
                            name="book_category"
                            required
                        >
                    </p>

                    <button type="submit">
                        Save
                    </button>

                    <a href="index.php?section=books">
                        Cancel
                    </a>

                </form>

            <?php elseif ($action === 'update'): ?>

                <h3>Update Book</h3>

                <form method="POST">

                    <p>
                        <label>Title:</label><br>
                        <input
                            type="text"
                            name="book_title"
                            value="<?= htmlspecialchars($book['book_title']) ?>"
                            required
                        >
                    </p>

                    <p>
                        <label>Author:</label><br>
                        <input
                            type="text"
                            name="book_author"
                            value="<?= htmlspecialchars($book['book_author']) ?>"
                            required
                        >
                    </p>

                    <p>
                        <label>Category:</label><br>
                        <input
                            type="text"
                            name="book_category"
                            value="<?= htmlspecialchars($book['book_category']) ?>"
                            required
                        >
                    </p>

                    <button type="submit">
                        Update
                    </button>

                    <a href="index.php?section=books">
                        Cancel
                    </a>

                </form>

            <?php else: ?>

                <table border="1" cellpadding="8">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($books as $book): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($book['book_id']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($book['book_title']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($book['book_author']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($book['book_category']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($book['book_created_at']) ?>
                                </td>

                                <td>

                                    <a href="index.php?section=books&action=update&id=<?= $book['book_id'] ?>">
                                        Edit
                                    </a>

                                    |

                                    <a
                                        href="index.php?section=books&action=delete&id=<?= $book['book_id'] ?>"
                                        onclick="return confirm('Delete this book?');"
                                    >
                                        Delete
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>


    <?php endif; ?>

    <?php if($section==='borrow'): ?>
        <h1>Borrow</h1>
        <p>
            <a href="index.php?section=borrow&action=create">
                Borrow a Book  
            </a>
        </p>

        <?php if($action==='create'):?>
            <h3>Borrow a Book</h3>

            <form method="POST">

                <label>Student</label>
                <br>
                <select name=student_id required>
                    <option value="">
                        -- Select Student--
                    </option>
                    <?php foreach($students as $student): ?>
                        <option value="<?= $student['student_id']?> ">
                            <?= htmlspecialchars(
                                $student['student_first_name']
                                .' '.
                                $student['student_last_name']
                            )?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <br>
                <br>

                <label>Book</label>
                <br>
                <select name=book_id required>
                    <option value="">
                        -- Select Book--
                    </option>
                    <?php foreach($books as $book): ?>
                        <option value="<?= $book['book_id']?> ">
                            <?= htmlspecialchars(
                                $book['book_title']
                                .'-'.
                                $book['book_author']
                            )?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <br>
                <button type="submit">
                    Borrow
                </button

                <a href="index.php?section=borrow">
                    Cancel
                </a
            </form>



        <?php else: ?>

        <table border="1" cellpadding="8">

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Book</th>
                    <th>Borrow Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>

            </thead>


            <tbody>

                <?php foreach ($borrowRecords as $borrow): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($borrow['borrow_id']) ?>
                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $borrow['student_first_name']
                                . ' '
                                . $borrow['student_last_name']
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $borrow['book_title']
                            ) ?>

                            -
                            <?= htmlspecialchars(
                                $borrow['book_author']
                            ) ?>

                        </td>

                        <td>
                            <?= htmlspecialchars($borrow['borrow_date']) ?>
                        </td>

                        <td>

                            <?php if ($borrow['borrow_return_date']): ?>

                                <?= htmlspecialchars(
                                    $borrow['borrow_return_date']
                                ) ?>

                            <?php else: ?>

                                Not returned

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($borrow['borrow_return_date']): ?>

                                Returned

                            <?php else: ?>

                                Borrowed

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if (!$borrow['borrow_return_date']): ?>

                                <a
                                    href="index.php?section=borrow&action=return&id=<?= $borrow['borrow_id'] ?>"
                                    onclick="return confirm('Mark this book as returned?');"
                                >
                                    Return
                                </a>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>
        <?php endif;?>



    <?php endif; ?>
    
</body>

<?php if (isset($_SESSION['alert'])): ?>

    <script>
        alert(<?=json_encode($_SESSION['alert'])?>);
    </script>

    <?php unset($_SESSION['alert']); ?>

<?php endif; ?>
</html>