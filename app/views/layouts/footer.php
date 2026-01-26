<footer>
  <div class="footer-inner">
    <div class="footer-content">
      <div class="footer-slogan">Visualize. Plan. Achieve.</div>
      <div class="small-note">
        © 2023 <strong>DreamBoard</strong>. Designed with ❤️ for mindful goal setting.
      </div>
      <div class="small-note">
        Contact: <a href="mailto:hello@dreamboard.local">hello@dreamboard.local</a>
      </div>
    </div>
  </div>
</footer>
<script>
document.addEventListener('DOMContentLoaded', () => {
    
    // Hàm kiểm tra mail
    function checkMailAutomatic() {
        // console.log(" System: Checking mail..."); // Bỏ comment nếu muốn soi lỗi

        // Lưu ý: Dùng đường dẫn tuyệt đối bắt đầu bằng / để chạy đúng ở mọi trang
        // Bạn hãy sửa 'DreamBoard' thành tên thư mục dự án thật của bạn nếu khác
        const apiUrl = '/DreamBoard/api/cron_send_mail.php'; 

        fetch(apiUrl) 
        .then(response => {
            if (response.ok) return response.text();
        })
        .then(data => {
            if (data && (data.includes("Đã gửi") || data.includes("✅"))) {
                console.log("🎉 Email Sent:", data);
                showGlobalToast("Ting ting! A message from your past self has just arrived!");
            }
        })
        .catch(err => console.error("Auto-mail error:", err));
    }

    // Hàm hiện thông báo đẹp (Toast)
    function showGlobalToast(message) {
        // Xóa toast cũ
        const old = document.querySelector('.global-toast');
        if(old) old.remove();

        const toast = document.createElement("div");
        toast.className = "global-toast";
        toast.innerHTML = `<i class="ph-fill ph-paper-plane-tilt"></i> ${message}`;
        
        // CSS trực tiếp
        Object.assign(toast.style, {
            position: "fixed", bottom: "30px", right: "30px",
            background: "linear-gradient(135deg, #6b5bff, #8a6dc5)",
            color: "white", padding: "16px 24px", borderRadius: "12px",
            boxShadow: "0 10px 30px rgba(107, 91, 255, 0.4)",
            zIndex: "10000", fontFamily: "sans-serif", fontWeight: "600",
            display: "flex", alignItems: "center", gap: "12px",
            animation: "slideInToast 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55) forwards"
        });

        document.body.appendChild(toast);
        
        // Tự biến mất sau 6s
        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transform = "translateY(20px)";
            toast.style.transition = "0.5s";
            setTimeout(() => toast.remove(), 500);
        }, 6000);
    }

    // Thêm keyframe cho đẹp
    const styleSheet = document.createElement("style");
    styleSheet.innerText = `@keyframes slideInToast { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }`;
    document.head.appendChild(styleSheet);

    // --- CẤU HÌNH CHẠY ---
    // 1. Chạy ngay lập tức sau 3 giây vào trang
    setTimeout(checkMailAutomatic, 3000);
    
    // 2. Lặp lại mỗi 15 giây (Để demo cho nhanh, thực tế có thể để 60s)
    setInterval(checkMailAutomatic, 3000); 
});
</script>

</body>
</html>

