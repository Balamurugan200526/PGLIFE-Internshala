<?php
session_start();
require "includes/database_connect.php";

if (!isset($_SESSION["user_id"])) {
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Fetch existing details
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    echo "User not found!";
    exit;
}
$user = mysqli_fetch_assoc($result);

// If form submitted, update details
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $college_name = mysqli_real_escape_string($conn, $_POST['college_name']);

    $update_sql = "UPDATE users 
                   SET full_name='$full_name', email='$email', phone='$phone', college_name='$college_name'
                   WHERE id = $user_id";

    if (mysqli_query($conn, $update_sql)) {
        header("Location: dashboard.php?success=1");
        exit;
    } else {
        $error = "Failed to update profile!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <?php include "includes/head_links.php"; ?>
</head>
<body>
<?php include "includes/header.php"; ?>

<div class="page-container">
    <h1>Edit Profile</h1>
    <?php if (!empty($error)) { echo "<div class='alert alert-danger'>$error</div>"; } ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
        </div>
        <div class="form-group">
            <label>College Name</label>
            <input type="text" name="college_name" class="form-control" value="<?= htmlspecialchars($user['college_name']) ?>" required>
        </div>
        <button type="submit" class="btn btn-success">Save Changes</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include "includes/footer.php"; ?>
</body>
</html>
