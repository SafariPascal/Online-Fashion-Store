<?php
include 'db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Gets the OTP entered by the user
    $otp     = $_POST['otp'];
    $user_id = $_SESSION['user_id'];

    // Checks OTP
    $sql = "SELECT * FROM otp_codes WHERE user_id=? AND otp=? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    $otpRow = $result->fetch_assoc();

    // Checks if OTP exists and has not expired
    if ($otpRow && strtotime($otpRow['expiry']) > time()) {
        echo "Login successful!";
    } else {
        echo "Invalid or expired OTP.";
    }
}
?>

<form method="POST">
    <input type="text" name="otp" placeholder="Enter OTP" required><br>
    <button type="submit">Verify</button>
</form>
