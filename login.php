<?php
include 'db_connect.php';
include 'mail.php';
session_start();

$successMessage = "";
$showVerifyLink = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    // This checks if the user exists
    $sql  = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // This generates OTP
        $otp    = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        // This stores OTP
        $insertOtp = "INSERT INTO otp_codes (user_id, otp, expiry) VALUES (?, ?, ?)";
        $stmtOtp   = $conn->prepare($insertOtp);
        $stmtOtp->bind_param("iss", $user['id'], $otp, $expiry);
        $stmtOtp->execute();

        // This sends the email the otp code
        $subject = "Your OTP Code";
        $message = "<h2>Your OTP is: $otp</h2><p>Valid for 5 minutes.</p>";

        if (sendMail($email, $subject, $message)) {
            $_SESSION['user_id'] = $user['id'];
            $successMessage = "OTP sent to your email!";
            $showVerifyLink = true;
        } else {
            $successMessage = "Failed to send OTP.";
        }
    } else {
        $successMessage = "Invalid email or password.";
    }
}
?>


<?php if (!empty($successMessage)) : ?>
    <p><?php echo $successMessage; ?></p>
<?php endif; ?>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Login</button>
</form>


<?php if ($showVerifyLink) : ?>
    <p><a href="verify.php">Verify here</a></p>
<?php endif; ?>
