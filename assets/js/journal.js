/* =========================================
   PHẦN 1: BIẾN TOÀN CỤC & HÀM FILTER
   ================================********* */

let currentLogData = null;
let currentTopicFilter = 'all';
let currentSearchText = '';
let activeTopicColor = '#C6A7FF';
// --- HÀM LỌC (FILTER & SEARCH) ---
function selectTopic(topicId, btnElement) {
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    if (btnElement) btnElement.classList.add('active');
    currentTopicFilter = topicId;
    filterContent();
}

function filterContent() {
    const searchInput = document.getElementById('searchInput');
    currentSearchText = searchInput ? searchInput.value.toLowerCase().trim() : '';

    const allItems = document.querySelectorAll('.filter-item');
    allItems.forEach(item => {
        const itemTopicId = item.getAttribute('data-topic-id');
        const itemText = item.getAttribute('data-search-text');

        // So sánh lỏng (==) vì topicId có thể là string '1' hoặc number 1
        const matchTopic = (currentTopicFilter === 'all') || (itemTopicId == currentTopicFilter);
        const matchSearch = (currentSearchText === '') || (itemText && itemText.includes(currentSearchText));

        if (matchTopic && matchSearch) {
            item.style.display = 'flex';
            item.style.animation = 'fadeIn 0.3s ease';
        } else {
            item.style.display = 'none';
        }
    });
}

// --- HÀM CẬP NHẬT UI GOAL CARD ---
// File: assets/js/journal.js

function updateGoalCardUI(goalId, newProgress) {
    const goalCard = document.getElementById(`goal-card-${goalId}`);

    if (goalCard) {
        // 1. Cập nhật số hiển thị
        const progressValue = goalCard.querySelector('.progress-value');
        if (progressValue) progressValue.innerText = newProgress + '%';

        // 2. Cập nhật biến CSS để vòng tròn xoay lại
        const circularProgress = goalCard.querySelector('.circular-progress');
        if (circularProgress) {
            circularProgress.style.setProperty('--p', newProgress);
        }

        // 3. Cập nhật tham số onclick (giữ nguyên logic cũ)
        let onclickAttr = goalCard.getAttribute('onclick');
        if (onclickAttr) {
            onclickAttr = onclickAttr.replace(/,\s*\d+\s*\)$/, `, ${newProgress})`);
            goalCard.setAttribute('onclick', onclickAttr);
        }
    }
}


/* =========================================
   PHẦN 2: CÁC HÀM XỬ LÝ MODAL (ADD/VIEW)
   ================================********* */

function openModal() {
    const modal = document.getElementById('goalModal');
    if (modal) modal.classList.remove('hidden');
}

function closeModal() {
    const modal = document.getElementById('goalModal');
    if (modal) modal.classList.add('hidden');
}

function saveGoal() {
    const title = document.getElementById('goalTitle').value.trim();

    // SỬA: Lấy value từ input text chứ không phải select
    const topicName = document.getElementById('goalTopicName').value.trim();

    if (!title) return alert("Please enter a goal title!");

    fetch("api/add_goal.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        // SỬA: Gửi param là 'topic_name'
        body: `title=${encodeURIComponent(title)}&topic_name=${encodeURIComponent(topicName)}`
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                closeModal();
                location.reload(); // Reload để thấy topic mới và goal mới
            } else {
                alert(data.message || "Error!");
            }
        })
        .catch(err => console.error(err));
}

// --- MODAL CHI TIẾT GOAL ---
// --- MODAL CHI TIẾT GOAL ---
function openGoalDetails(goalId, goalTitle, goalProgress, topicColor, createdAt) {
    const modal = document.getElementById('goalDetailsModal');
    if (modal) modal.classList.remove('hidden');
    activeTopicColor = topicColor || '#C6A7FF';
    collapseAddJourneyPanel();

    // Lưu ID vào hidden input
    const hiddenId = document.getElementById('hiddenGoalId');
    if (hiddenId) hiddenId.value = goalId;

    // 1. CẬP NHẬT HEADER HERO
    document.getElementById('detailGoalTitle').innerText = goalTitle;
    document.getElementById('detailGoalDate').innerText = "Created at: " + (createdAt || 'Unknown date');

    // Gán màu nền theo topic (cho giao diện Minimalist thì ta dùng màu nhạt)
    const headerHero = document.getElementById('goalHeaderHero');
    if (headerHero) {
        headerHero.style.background = topicColor || '#f3e8ff';
    }

    // --- [SỬA LỖI] CẬP NHẬT PROGRESS (CẢ VÒNG TRÒN VÀ CHỮ SỐ) ---
    const circlePath = document.getElementById('heroProgressPath');
    const circleText = document.getElementById('heroProgressText');

    // Reset về 0 trước để tạo hiệu ứng chạy (nếu muốn)
    if (circlePath) {
        circlePath.style.strokeDasharray = "0, 100";
        setTimeout(() => {
            circlePath.style.strokeDasharray = `${goalProgress}, 100`;
        }, 50);
    }

    // Cập nhật nội dung chữ số (QUAN TRỌNG: Dùng textContent cho SVG)
    if (circleText) {
        circleText.textContent = `${goalProgress}%`;
    }
    // -------------------------------------------------------------

    // 2. LOAD DATA TIMELINE
    const container = document.getElementById('goalLogsContainer');
    if (container) container.innerHTML = '<div class="loading-spinner">Loading timeline...</div>';

    fetch(`api/get_goal_logs.php?goal_id=${goalId}`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                const countLabel = document.getElementById('detailGoalCount');
                if (countLabel) countLabel.innerText = `${res.data.length} entities`;

                renderGoalLogsNew(res.data, container, topicColor);
            } else {
                if (container) container.innerHTML = '<p style="color:red; text-align:center">Error loading data</p>';
            }
        })
        .catch(err => console.error(err));
}
function closeGoalDetails() {
    const modal = document.getElementById('goalDetailsModal');
    if (modal) modal.classList.add('hidden');
    collapseAddJourneyPanel();
    const form = document.getElementById('addJourneyForm');
    if (form) form.reset();

    // Reset lại vòng tròn về 0 để tạo hiệu ứng animation cho lần mở sau
    const circlePath = document.getElementById('heroProgressPath');
    if (circlePath) circlePath.style.strokeDasharray = `0, 100`;
}
function renderGoalLogsNew(logs, container, themeColor) {
    if (!container) return;

    // Nếu chưa có nhật ký nào
    if (!logs || logs.length === 0) {
        container.innerHTML = `<div style="text-align:center;padding:50px;color:#aaa;">
            <i class="ph ph-notebook" style="font-size:40px;margin-bottom:10px;display:block"></i>
            <p>Start your journey by adding the first entry!</p>
            <button class="btn-add-journey-expand" onclick="expandAddJourneyPanel()" style="margin-top:15px;">
                + Add First Entry
            </button>
        </div>`;
        return;
    }

    let html = '';
    let currentDate = ''; // Biến để theo dõi ngày đang xét

    logs.forEach(log => {
        // Format ngày: Nov 25, 2023
        const dateObj = new Date(log.created_at);
        const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

        // LOGIC GROUP: Nếu ngày của bài này KHÁC bài trước -> Tạo tiêu đề ngày mới
        if (dateStr !== currentDate) {
            if (currentDate !== '') html += `</div>`; // Đóng div group của ngày cũ (trừ lần đầu tiên)
            currentDate = dateStr;

            // Mở div group mới và in tiêu đề ngày
            html += `<div class="timeline-date-group">
                        <div class="timeline-date-label">${dateStr}</div>`;
        }

        // Xử lý ảnh (Thumbnail nhỏ bên trái)
        const imgHtml = log.image
            ? `<div class="card-img"><img src="${log.image}" alt="img"></div>`
            : `<div class="card-img"><div class="card-img-placeholder">📝</div></div>`;

        // Tiêu đề: Ưu tiên dùng journey_title, nếu không có thì dùng mặc định
        const displayTitle = log.journey_title ? log.journey_title : 'Journey Update';

        // Chuẩn bị dữ liệu để truyền vào hàm xem chi tiết
        const logData = JSON.stringify(log).replace(/"/g, '&quot;');

        // HTML cho từng Card Item (Giống hình mẫu)
        html += `
            <div class="timeline-item-wrapper" style="position:relative; padding-left:20px;">
                <div class="timeline-dot" style="border-color:${themeColor || '#C6A7FF'}"></div>
                
                <div class="timeline-card" onclick="openEntryDetail(${logData})">
                    ${imgHtml}
                    <div class="card-content">
                        
                        <div class="card-header-row">
                            <div class="card-mood-badge">${log.mood || 'Feeling...'}</div>
                            <span class="card-progress-pill" style="background:${themeColor || '#C6A7FF'}">
                                +${parseInt(log.progress_update)}%
                            </span>
                        </div>
                        
                        <h4 class="card-title">${displayTitle}</h4>
                        <p class="card-desc">${log.content}</p>
                        
                    </div>
                </div>
            </div>
        `;
    });

    html += `</div>`; // Đóng div group cuối cùng
    container.innerHTML = html;
}
// Helper: Cập nhật các thành phần Progress trong Modal
// Helper: Cập nhật các thành phần Progress trong Modal (Phiên bản mới)
function updateProgressUI(percent) {
    // 1. Cập nhật Slider trong form thêm mới
    const sl = document.getElementById('progressSlider');
    const sv = document.getElementById('sliderValue');
    if (sl) sl.value = percent;
    if (sv) sv.innerText = percent + '%';

    // 2. [QUAN TRỌNG] Cập nhật số to ở Header Modal (hero section)
    const circlePath = document.getElementById('heroProgressPath');
    const circleText = document.getElementById('heroProgressText');

    if (circlePath) circlePath.style.strokeDasharray = `${percent}, 100`;
    if (circleText) circleText.textContent = `${percent}%`;
}

function renderGoalLogs(logs, container) {
    if (!container) return;
    if (!logs || logs.length === 0) {
        container.innerHTML = `<div style="text-align:center;padding:30px;"><p style="color:#aaa;">No entries yet.</p><button class="btn-add-journey-expand" onclick="expandAddJourneyPanel()" style="margin-top:10px;padding:5px 10px;">+ Add First Entry</button></div>`;
        return;
    }
    let html = '<div class="timeline-list">';
    logs.forEach(log => {
        const date = new Date(log.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const imgHtml = log.image ? `<div style="margin-top:8px"><img src="${log.image}" style="max-width:100px;border-radius:6px;"></div>` : '';
        const logData = JSON.stringify(log).replace(/"/g, '&quot;');

        html += `
        <div class="tl-item" onclick="openEntryDetail(${logData})" style="border-bottom:1px solid #eee; padding:15px 0; display:flex; gap:12px; cursor:pointer;">
            <div style="flex:1">
                <small style="color:#999;">${date}</small>
                <p style="margin:0; color:#333; font-size:14px;">${log.content}</p>
                ${imgHtml}
            </div>
            <div style="text-align:right">
                <div style="font-weight:600;color:#6b5bff">${parseInt(log.progress_update)}%</div>
                <div style="font-size:11px;color:#999">${log.mood}</div>
            </div>
        </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
}

function expandAddJourneyPanel() {
    const box = document.getElementById('goalModalBox');
    if (box) box.classList.add('expanded');
}
function collapseAddJourneyPanel() {
    const box = document.getElementById('goalModalBox');
    if (box) box.classList.remove('expanded');
}
// Tìm hàm này và thay thế nội dung:
function deleteCurrentGoal() {
    // 1. Lấy ID từ input ẩn (được gán khi mở Modal)
    const hiddenInput = document.getElementById('hiddenGoalId');
    
    if (!hiddenInput) {
        console.error("Lỗi: Không tìm thấy input chứa ID (hiddenGoalId)");
        return;
    }

    const goalId = hiddenInput.value;
    console.log("Đang thử xóa Goal ID:", goalId); // Debug xem có lấy được ID không

    if (!goalId) {
        alert("Lỗi: Không xác định được mục tiêu cần xóa!");
        return;
    }

    // 2. Hỏi xác nhận
    if (confirm("⚠️ Bạn có chắc chắn muốn xóa mục tiêu này?\nTất cả nhật ký (Journey) thuộc về nó cũng sẽ bị xóa vĩnh viễn!")) {
        
        // Hiệu ứng nút đang xóa
        const btnDelete = document.querySelector('.btn-delete-styled');
        const originalText = btnDelete ? btnDelete.innerHTML : 'Delete';
        if(btnDelete) {
            btnDelete.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Deleting...';
            btnDelete.disabled = true;
        }

        // 3. Gọi API
        fetch('api/delete_goal.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `goal_id=${goalId}`
        })
        .then(res => res.json())
        .then(data => {
            console.log("Server trả về:", data); // Debug xem server trả về gì
            
            if (data.status === 'success') {
                alert("Đã xóa mục tiêu thành công!");
                closeGoalDetails();
                location.reload(); // Tải lại trang
            } else {
                alert("Lỗi: " + (data.message || "Không thể xóa"));
                // Trả lại nút nếu lỗi
                if(btnDelete) {
                    btnDelete.innerHTML = originalText;
                    btnDelete.disabled = false;
                }
            }
        })
        .catch(err => {
            console.error("Lỗi kết nối:", err);
            alert("Lỗi kết nối server (Xem console để biết chi tiết)");
            if(btnDelete) {
                btnDelete.innerHTML = originalText;
                btnDelete.disabled = false;
            }
        });
    }
}

/* =========================================
   PHẦN 3: MODAL CHI TIẾT ENTRY (XEM/SỬA/XÓA)
   ================================********* */

function openEntryDetail(log) {
    currentLogData = log;
    const modal = document.getElementById('entryDetailModal');
    if (modal) modal.classList.remove('hidden');
    toggleEditMode(false);

    // View Mode
    const dateStr = new Date(log.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    document.getElementById('detailEntryDate').innerText = dateStr;
    document.getElementById('detailEntryMood').innerText = log.mood || 'Feeling good';
    document.getElementById('detailEntryText').innerText = log.content;
    document.getElementById('detailEntryProgress').innerText = log.progress_update + '%';

    const imgTag = document.getElementById('detailEntryImg');
    const ph = document.getElementById('noImagePlaceholder');
    if (log.image) {
        imgTag.src = log.image; imgTag.style.display = 'block'; ph.style.display = 'none';
    } else {
        imgTag.style.display = 'none'; ph.style.display = 'block';
    }

    // Edit Form - Fill Data
    document.getElementById('editEntryId').value = log.log_id;
    const editGoalInput = document.getElementById('editGoalId');
    if (editGoalInput) {
        editGoalInput.value = log.goal_id || document.getElementById('hiddenGoalId').value;
    }

    document.getElementById('editContentInput').value = log.content;
    document.getElementById('editMoodInput').value = log.mood;
    document.getElementById('editProgressInput').value = log.progress_update;
}

function closeEntryDetail() {
    document.getElementById('entryDetailModal').classList.add('hidden');
}

function toggleEditMode(showEdit) {
    const view = document.getElementById('viewModeContent');
    const edit = document.getElementById('editModeContent');
    if (showEdit) { view.classList.add('hidden'); edit.classList.remove('hidden'); }
    else { view.classList.remove('hidden'); edit.classList.add('hidden'); }
}

function deleteEntryCurrent() {
    if (!currentLogData) return;
    if (!confirm("Delete this memory?")) return;

    fetch('api/delete_journey.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `log_id=${currentLogData.log_id}&goal_id=${currentLogData.goal_id}`
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Deleted successfully');
                closeEntryDetail();

                // Reload logs
                const gid = document.getElementById('hiddenGoalId').value;
                const container = document.getElementById('goalLogsContainer');

                // Cập nhật UI bên ngoài & trong modal
                if (data.new_progress !== undefined) {
                    updateProgressUI(data.new_progress);
                    updateGoalCardUI(gid, data.new_progress);
                }

                fetch(`api/get_goal_logs.php?goal_id=${gid}`)
                    .then(r => r.json()).then(d => { if (d.status === 'success') renderGoalLogsNew(d.data, container, activeTopicColor); });
            } else {
                alert(data.message);
            }
        });
}


/* =========================================
   PHẦN 4: DOM EVENTS
   ================================********* */

document.addEventListener('DOMContentLoaded', () => {

    // 1. View All Goals
    const btnViewAll = document.querySelector('.btn-view-all');
    if (btnViewAll) {
        btnViewAll.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelectorAll('.goals .goal-card[style*="display:none"]').forEach(g => g.style.display = 'flex');
            const lastGoal = document.querySelector('.goals .goal-card:last-child');
            if (lastGoal) lastGoal.scrollIntoView({ behavior: 'smooth' });
            btnViewAll.style.display = 'none';
        });
    }

    // 2. Submit ADD JOURNEY
    document.addEventListener('submit', function (e) {
        if (e.target && e.target.id === 'addJourneyForm') {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const saveBtn = form.querySelector('.btn-save-panel');
            const originalText = saveBtn.innerText;

            saveBtn.innerText = 'Saving...';
            saveBtn.disabled = true;

            fetch('api/add_journey.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // 1. Tắt alert mặc định để trải nghiệm mượt hơn (hoặc giữ lại nếu muốn)
                        // alert("Thêm thành công!"); 

                        collapseAddJourneyPanel();
                        form.reset();

                        // 2. Cập nhật UI Progress
                        const gid = document.getElementById('hiddenGoalId').value;
                        const container = document.getElementById('goalLogsContainer');

                        if (data.new_progress !== undefined) {
                            updateProgressUI(data.new_progress);
                            updateGoalCardUI(gid, data.new_progress);
                        }

                        // 3. Load lại danh sách nhật ký
                        // 3. Load lại danh sách nhật ký
                        fetch(`api/get_goal_logs.php?goal_id=${gid}`)
                            .then(r => r.json()).then(d => {
                                // GỌI HÀM MỚI (renderGoalLogsNew) VÀ TRUYỀN MÀU (activeTopicColor) VÀO
                                if (d.status === 'success') renderGoalLogsNew(d.data, container, activeTopicColor);
                            });

                        // --- [QUAN TRỌNG] LOGIC MỚI: KIỂM TRA & HIỆN THƯ ---
                        // Nếu controller trả về dữ liệu thư, nghĩa là Mood này đã kích hoạt thư cũ
                        if (data.letter_data) {
                            showLetterNotification(data.letter_data);
                        }
                        // ----------------------------------------------------

                    } else {
                        alert("Lỗi: " + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Lỗi kết nối server");
                })
                .finally(() => {
                    saveBtn.innerText = originalText;
                    saveBtn.disabled = false;
                });
        }
    });
    // 3. Submit EDIT ENTRY
    const editForm = document.getElementById('editEntryForm');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('api/update_journey.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Cập nhật thành công!');

                        // Update View Mode
                        document.getElementById('detailEntryText').innerText = formData.get('content');
                        document.getElementById('detailEntryMood').innerText = formData.get('mood');
                        document.getElementById('detailEntryProgress').innerText = formData.get('progress') + '%';

                        currentLogData.content = formData.get('content');
                        currentLogData.mood = formData.get('mood');
                        currentLogData.progress_update = formData.get('progress');

                        toggleEditMode(false);

                        // Update UI & Reload List
                        const gid = document.getElementById('hiddenGoalId').value;
                        const container = document.getElementById('goalLogsContainer');

                        if (data.new_progress !== undefined) {
                            updateProgressUI(data.new_progress);
                            updateGoalCardUI(gid, data.new_progress);
                        }

                        fetch(`api/get_goal_logs.php?goal_id=${gid}`)
                            .then(r => r.json()).then(d => { if (d.status === 'success') renderGoalLogs(d.data, container); });
                    } else {
                        alert(data.message);
                    }
                });
        });
    }
    // 4. LOAD MINI VISION BOARD (PREVIEW)
    const miniCanvas = document.getElementById('miniCanvas');
    if (miniCanvas) {
        // Gọi API lấy dữ liệu Vision Board
        fetch('api/get_vision.php')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.items) {
                    miniCanvas.innerHTML = ''; // Xóa loading text

                    const layoutMeta = data.items.find(i => i.type === 'layout_meta');

                    // A. Dựng khung Layout (Grid)
                    if (layoutMeta && layoutMeta.content !== 'free') {
                        const grid = document.createElement('div');
                        grid.className = `layout-${layoutMeta.content}`;
                        miniCanvas.appendChild(grid);

                        // Xác định số ô dựa trên layout
                        let slotCount = 9;
                        if (layoutMeta.content === 'masonry') slotCount = 5;
                        if (layoutMeta.content === 'hero-center') slotCount = 9;

                        // Tạo các ô trống (slots)
                        for (let i = 0; i < slotCount; i++) {
                            const slot = document.createElement('div');
                            slot.className = 'frame-slot';
                            slot.id = `mini-slot-${i}`; // Đánh dấu ID để lát nữa điền ảnh vào
                            grid.appendChild(slot);
                        }
                    }

                    // B. Điền các Item (Ảnh, Sticker, Text)
                    data.items.forEach(item => {

                        // Trường hợp 1: Ảnh nằm trong khung (Layout Slot)
                        if (layoutMeta && layoutMeta.content !== 'free' && item.type === 'layout_slot') {
                            const targetSlot = document.getElementById(`mini-slot-${item.z_index}`);
                            // Chỉ điền nếu tìm thấy slot và có đường dẫn ảnh
                            if (targetSlot && item.image_path) {
                                targetSlot.innerHTML = `<img src="${item.image_path}" style="object-position: ${item.content || 'center'}">`;
                                targetSlot.classList.add('has-image');
                            }
                        }

                        // Trường hợp 2: Vật phẩm trôi nổi (Sticker hoặc Text)
                        else if (item.type !== 'layout_meta' && item.type !== 'layout_slot') {
                            const el = document.createElement('div');
                            el.className = `board-item`; // Class chung

                            // Xử lý Text
                            if (item.type.startsWith('text')) {
                                el.classList.add('item-' + item.type); // vd: item-text_heading
                                el.classList.add('item-text');
                                el.innerText = item.content;
                                // Reset font size mặc định để CSS tự xử lý
                                el.style.fontSize = '';
                            }
                            // Xử lý Sticker
                            else {
                                el.classList.add('item-sticker');
                                el.innerHTML = item.content; // Dùng innerHTML để hiện icon/ảnh
                            }

                            // Set vị trí tọa độ (quan trọng)
                            el.style.left = item.pos_x + 'px';
                            el.style.top = item.pos_y + 'px';
                            el.style.zIndex = item.z_index;

                            miniCanvas.appendChild(el);
                        }
                    });

                } else {
                    // Nếu chưa có dữ liệu thì hiện thông báo
                    miniCanvas.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#999"><p>No vision board yet</p><a href="vision.php" style="color:#6b5bff;text-decoration:none">Create one now</a></div>';
                }
            })
            .catch(err => {
                console.error("Lỗi load mini vision:", err);
                miniCanvas.innerHTML = '<p style="text-align:center;padding-top:100px;color:#aaa">Cannot load vision board</p>';
            });
    }
});
// --- HÀM UPLOAD AVATAR ---
function uploadAvatar(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];

        // Kiểm tra sơ bộ phía client
        if (file.size > 5 * 1024 * 1024) { // 5MB
            alert("File ảnh quá lớn! Vui lòng chọn ảnh dưới 5MB.");
            return;
        }

        // Hiển thị preview ngay
        const reader = new FileReader();
        reader.onload = function (e) {
            const display = document.getElementById('profileAvatarDisplay');
            if (display) display.src = e.target.result;
        }
        reader.readAsDataURL(file);

        // Gửi lên server
        const formData = new FormData();
        formData.append('avatar', file);

        fetch('api/update_avatar.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.text()) // <--- Đọc dạng text trước
            .then(text => {
                console.log("Server response:", text); // [DEBUG] Xem server trả về gì ở Console

                try {
                    return JSON.parse(text); // Thử chuyển sang JSON
                } catch (e) {
                    throw new Error("Server trả về dữ liệu không hợp lệ (Xem console để biết chi tiết)");
                }
            })
            .then(data => {
                if (data.status === 'success') {
                    console.log("Avatar updated successfully!");
                } else {
                    alert("Lỗi: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Có lỗi xảy ra: " + err.message);
            });
    }
}
/* =========================================
   PHẦN 5: XỬ LÝ POPUP FUTURE LETTER
   ================================********* */

let pendingLetterContent = null; // Biến tạm lưu nội dung thư

// 1. Hiện Popup thông báo (Cái hộp nhỏ xinh)
function showLetterNotification(letterData) {
    pendingLetterContent = letterData; // Lưu lại dữ liệu để dùng khi bấm nút "Open"

    // Điền Mood vào text thông báo
    const notiMood = document.getElementById('notiMood');
    if (notiMood) notiMood.innerText = letterData.mood;

    // Hiện Modal
    const modal = document.getElementById('letterNotificationModal');
    if (modal) modal.classList.remove('hidden');
}

// 2. Đóng Popup thông báo
function closeLetterNotification() {
    const modal = document.getElementById('letterNotificationModal');
    if (modal) modal.classList.add('hidden');
}

// 3. Mở thư chi tiết (Cái hộp to)
function openFullLetter() {
    closeLetterNotification(); // Đóng cái hộp nhỏ trước

    if (!pendingLetterContent) return;

    // Điền dữ liệu vào Modal chi tiết
    document.getElementById('letterMoodDisplay').innerText = pendingLetterContent.mood;
    document.getElementById('letterDateDisplay').innerText = pendingLetterContent.created_at;

    // Xử lý nội dung thư: Chuyển ký tự xuống dòng (\n) thành thẻ <br> để hiển thị đẹp
    // và dùng innerHTML để render
    const safeContent = pendingLetterContent.message
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\n/g, "<br>");

    document.getElementById('letterMessageContent').innerHTML = safeContent;

    // Hiện Modal to
    const modal = document.getElementById('letterContentModal');
    if (modal) modal.classList.remove('hidden');
}

// 4. Đóng thư chi tiết
function closeFullLetter() {
    const modal = document.getElementById('letterContentModal');
    if (modal) modal.classList.add('hidden');
}