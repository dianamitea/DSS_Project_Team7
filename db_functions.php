<?php
// db_functions.php

/**
 * Inserts a new user into the database securely.
 * * @param mysqli $conn The database connection
 * @param string $username The user's chosen username
 * @param string $email The user's email address
 * @param string $password The plain-text password (will be hashed)
 * @return bool Returns true on success, false on failure
 */
function db_insert($conn, $username, $email, $password) {
    // 1. Prepare the SQL query (The '?' marks protect against SQL injection)
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("Database error: " . mysqli_error($conn));
    }

    // 2. Hash the password for security (NEVER save plain text passwords!)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 3. Bind the data to the '?' marks ('sss' means 3 strings)
    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed_password);

    // 4. Execute the query and return true if it worked
    if (mysqli_stmt_execute($stmt)) {
        return true;
    } else {
        return false;
    }
}
?>