<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - DreamBoard</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

    <div class="auth-container">
        <div class="auth-box">
            
            <div class="logo-circle">
                <img src="assets/images/logo1.png" alt="Logo">
            </div>

            <h2>Create Account</h2>

            <?php if (!empty($error)): ?>
                 <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <input type="text" name="username" required placeholder="Username">
                </div>
                
                <div class="form-group">
                    <input type="email" name="email" required placeholder="Email Address">
                </div>

                <div class="form-group">
                    <input type="password" name="password" required placeholder="Password">
                </div>

                <div class="form-group">
                    <input type="password" name="confirm_password" required placeholder="Confirm Password">
                </div>

                <button type="submit" class="btn-auth">Sign Up</button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </div>
    </div>

</body>
</html>