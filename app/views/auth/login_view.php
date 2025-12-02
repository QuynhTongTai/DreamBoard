<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DreamBoard</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>

    <div class="auth-container">
        <div class="auth-box">

            <div class="logo-circle">
                <img src="assets/images/logo1.png" alt="Logo">
            </div>

            <h2>Welcome Back!</h2>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'registered'): ?>
                <div class="alert success">Account created! Please login.</div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <input type="email" name="email" required placeholder="Email Address">
                </div>

                <div class="form-group">
                    <input type="password" name="password" required placeholder="Password">
                </div>

                

                <button type="submit" class="btn-auth">Login</button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Sign Up</a>
            </div>



        </div>
    </div>

</body>

</html>