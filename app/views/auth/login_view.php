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
                <div class="switch-auth">
                    <a href="#" onclick="openForgotModal(event)" style="font-size: 14px; color: #6b5bff;">Forgot Password?</a>
                </div>


                <button type="submit" class="btn-auth">Login</button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Sign Up</a>
            </div>



        </div>
    </div>
    <div id="forgotModal" class="modal-overlay hidden">
        <div class="modal-content">
            <button class="close-modal" onclick="closeForgotModal()">&times;</button>

            <div id="step1" class="step-content">
                <h3>Forgot Password? 🔒</h3>
                <p>Enter your email to receive an OTP code.</p>
                <div class="form-group">
                    <input type="email" id="forgotEmail" placeholder="Your Email Address">
                </div>
                <button onclick="handleSendOtp()" class="btn-auth" id="btnSendOtp">Send OTP</button>
            </div>

            <div id="step2" class="step-content hidden">
                <h3>Verify OTP 📩</h3>
                <p>Check your email and enter the code.</p>
                <div class="form-group">
                    <input type="text" id="otpInput" placeholder="6-digit Code" maxlength="6"
                        style="text-align:center; letter-spacing: 5px; font-weight:bold;">
                </div>
                <button onclick="handleVerifyOtp()" class="btn-auth" id="btnVerify">Verify</button>
            </div>

            <div id="step3" class="step-content hidden">
                <h3>Reset Password 🔑</h3>
                <p>Enter your new password below.</p>
                <div class="form-group">
                    <input type="password" id="newPassword" placeholder="New Password">
                </div>
                <button onclick="handleResetPassword()" class="btn-auth" id="btnReset">Change Password</button>
            </div>

        </div>
    </div>

    <script>
        const modal = document.getElementById('forgotModal');
        let currentEmail = '';

        // Mở Modal (Gắn hàm này vào link Forgot Password? ở form chính)
        // Ví dụ: <a href="#" onclick="openForgotModal(event)">Forgot Password?</a>
        function openForgotModal(e) {
            if (e) e.preventDefault();
            modal.classList.remove('hidden');
            showStep(1);
        }
        function closeForgotModal() {
            modal.classList.add('hidden');
        }

        // Chuyển bước
        function showStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
            document.getElementById('step' + step).classList.remove('hidden');
        }

        // --- LOGIC GỬI AJAX ---

        // 1. Gửi OTP
        function handleSendOtp() {
            const email = document.getElementById('forgotEmail').value;
            const btn = document.getElementById('btnSendOtp');
            if (!email) return alert("Please enter email!");

            btn.innerText = "Sending...";
            btn.disabled = true;

            const formData = new FormData();
            formData.append('email', email);

            // GỌI API: login.php?action=send_otp
            fetch('login.php?action=send_otp', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        currentEmail = email; // Lưu email để dùng cho bước sau
                        alert(data.message);
                        showStep(2);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(e => { console.error(e); alert("Lỗi kết nối server"); })
                .finally(() => { btn.innerText = "Send OTP"; btn.disabled = false; });
        }

        // 2. Xác thực OTP
        function handleVerifyOtp() {
            const otp = document.getElementById('otpInput').value;
            const btn = document.getElementById('btnVerify');

            btn.innerText = "Checking...";
            btn.disabled = true;

            const formData = new FormData();
            formData.append('email', currentEmail);
            formData.append('otp', otp);

            fetch('login.php?action=verify_otp', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        showStep(3);
                    } else {
                        alert(data.message);
                    }
                })
                .finally(() => { btn.innerText = "Verify"; btn.disabled = false; });
        }

        // 3. Đổi mật khẩu
        function handleResetPassword() {
            const pass = document.getElementById('newPassword').value;

            const formData = new FormData();
            formData.append('email', currentEmail);
            formData.append('password', pass);

            fetch('login.php?action=reset_password', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert("Your password has been changed successfully. Please log in again.");
                        closeForgotModal();
                    } else {
                        alert(data.message);
                    }
                });
        }
    </script>

</body>

</html>