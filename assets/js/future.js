document.addEventListener('DOMContentLoaded', () => {
    
    // --- PHẦN 1: XỬ LÝ MOOD TOGGLE (CHỌN/BỎ CHỌN) ---
    const moodPills = document.querySelectorAll('.mood-item');
    // ... (đoạn khai báo biến moodPills ở trên) ...
    let currentSelectedMood = null; // Biến lưu trữ mood đang chọn hiện tại

    moodPills.forEach(pill => {
        pill.addEventListener('click', function() {
            const mood = this.getAttribute('data-mood');

            // --- LOGIC QUAN TRỌNG Ở ĐÂY ---
            
            // TRƯỜNG HỢP 1: Bấm lại vào mood đang chọn (BỎ CHỌN)
            if (currentSelectedMood === mood) {
                
                // 1. Xóa class 'active' để icon trở về màu cũ
                this.classList.remove('active');
                
                // 2. Reset biến trạng thái về null
                currentSelectedMood = null;
                
                // 3. Xóa nội dung bài viết bên dưới (trả về text hướng dẫn ban đầu)
                clearMoodDisplay(); 
                
            } 
            // TRƯỜNG HỢP 2: Bấm vào mood mới (CHỌN MỚI)
            else {
                // 1. Xóa class 'active' ở tất cả các icon khác (reset cái cũ)
                moodPills.forEach(p => p.classList.remove('active'));
                
                // 2. Thêm class 'active' cho icon mới bấm (để nó sáng lên)
                this.classList.add('active');
                
                // 3. Cập nhật biến trạng thái
                currentSelectedMood = mood;
                
                // 4. Gọi API lấy dữ liệu
                fetchMoodEchoes(mood);
            }
        });
    });

    // Hàm trả lại giao diện mặc định khi bỏ chọn
    function clearMoodDisplay() {
        const displayArea = document.querySelector('.mood-footer-text');
        
        // Code HTML mặc định lúc chưa chọn gì
        displayArea.innerHTML = `
            <p>Select a mood to view letters you wrote in that state. <br>Let future you connect with past you.</p>
            <a href="#">Write a New Mood Message</a>
        `;
    }

    // Hàm gọi API lấy ký ức cũ
    function fetchMoodEchoes(mood) {
        const displayArea = document.querySelector('.mood-footer-text');
        displayArea.innerHTML = '<p style="color:#888">Searching for memories...</p>';

        fetch(`api/get_mood_echoes.php?mood=${mood}`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success' && res.data.length > 0) {
                    let html = `<h4 style="margin:0 0 10px; color:#555;">Memories when you felt ${mood}:</h4>`;
                    res.data.forEach(item => {
                        const date = new Date(item.created_at).toLocaleDateString();
                        html += `
                            <div style="background:#fff; padding:10px; border-radius:8px; margin-bottom:8px; border:1px solid #eee; font-size:13px;">
                                <div style="color:#888; font-size:11px;">${date}</div>
                                <div>${item.content}</div>
                            </div>
                        `;
                    });
                    displayArea.innerHTML = html;
                } else {
                    displayArea.innerHTML = `<p style="color:#aaa">No echoes found for this mood yet.</p>`;
                }
            })
            .catch(err => console.error(err));
    }



    // --- PHẦN 2: GỬI THƯ (SEAL & SEND) ---
    const btnSeal = document.querySelector('.seal-btn');
    if (btnSeal) {
        btnSeal.addEventListener('click', function() {
            const form = document.getElementById('capsuleForm');
            const formData = new FormData(form);

            // Validate cơ bản
            if (!formData.get('openDate')) {
                alert("Please choose an Open Date!");
                return;
            }

            // Hiệu ứng loading
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Sealing...';
            this.disabled = true;

            fetch('api/save_letter.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert("✉️ Your Time Capsule has been sealed!\nIt will be delivered on " + formData.get('openDate'));
                    form.reset();
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Connection error.");
            })
            .finally(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    }
});