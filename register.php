<?php
include 'db_connect.php';

$successMessage = "";
$showLoginLink = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user inputs from the form
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Insert query
    $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $email, $password);

    if ($stmt->execute()) {
        $successMessage = "Registration successful!";
        $showLoginLink = true; 
    } else {
        $successMessage = "Error: " . $conn->error;
    }
}
?>


<?php if (!empty($successMessage)) : ?>
    <p><?php echo $successMessage; ?></p>
<?php endif; ?>

<form method="POST">
    <input type="text" name="name" placeholder="Full Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Register</button>
</form>


<?php if ($showLoginLink) : ?>
    <p><a href="login.php">Login here</a></p>
<?php endif; ?>
