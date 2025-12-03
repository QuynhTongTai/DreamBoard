// --- PHẦN 1: KHAI BÁO BIẾN & HÀM GLOBAL ---

let currentSelectedMood = null;

// Hàm này được gọi từ onclick="selectMood(this)" bên HTML
function selectMood(element) {
    const mood = element.getAttribute('data-mood');
    const moodInput = document.querySelector('input[name="moodTag"]');

    // TRƯỜNG HỢP 1: Bấm lại vào mood đang chọn (BỎ CHỌN)
    if (currentSelectedMood === mood) {
        element.classList.remove('active');
        currentSelectedMood = null;
        if (moodInput) moodInput.value = ''; // Xóa giá trị trong input ẩn
    } 
    // TRƯỜNG HỢP 2: Bấm vào mood mới (CHỌN MỚI)
    else {
        // 1. Xóa class 'active' ở tất cả các icon khác
        document.querySelectorAll('.mood-item').forEach(p => p.classList.remove('active'));
        
        // 2. Thêm class 'active' cho icon mới bấm
        element.classList.add('active');
        
        // 3. Cập nhật biến trạng thái & input ẩn
        currentSelectedMood = mood;
        if (moodInput) moodInput.value = mood;
    }
}

// --- PHẦN 2: CÁC SỰ KIỆN KHÁC KHI LOAD TRANG ---

document.addEventListener('DOMContentLoaded', () => {
    
    // XỬ LÝ GỬI THƯ (SEAL & SEND)
    const btnSeal = document.querySelector('.seal-btn');
    if (btnSeal) {
        btnSeal.addEventListener('click', function() {
            const form = document.getElementById('capsuleForm');
            const formData = new FormData(form);

            // 1. Logic kiểm tra lỗi: Phải có Ngày HOẶC Mood
            const hasDate = formData.get('openDate');
            // Lấy mood từ input hidden hoặc biến toàn cục
            const hasMood = formData.get('moodTag') || currentSelectedMood; 

            // Nếu mood chưa vào formData thì append vào thủ công cho chắc chắn
            if(hasMood && !formData.get('moodTag')) {
                formData.append('moodTag', hasMood);
            }

            if (!hasDate && !hasMood) {
                alert("Please select an Open Date OR choose a Mood to seal this letter!");
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
                    alert("✉️ Your Time Capsule has been sealed!\nIt will wait for the right moment.");
                    form.reset();
                    
                    // Reset giao diện Mood
                    document.querySelectorAll('.mood-item').forEach(p => p.classList.remove('active'));
                    currentSelectedMood = null;
                    const moodInput = document.querySelector('input[name="moodTag"]');
                    if(moodInput) moodInput.value = '';

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