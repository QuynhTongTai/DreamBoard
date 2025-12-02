/* =========================================
   PHẦN 1: BIẾN TOÀN CỤC & HÀM FILTER
   ================================********* */

let currentLogData = null;
let currentTopicFilter = 'all';
let currentSearchText = '';

// --- HÀM LỌC (FILTER & SEARCH) ---
function selectTopic(topicId, btnElement) {
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    if(btnElement) btnElement.classList.add('active');
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
function updateGoalCardUI(goalId, newProgress) {
    const goalCard = document.getElementById(`goal-card-${goalId}`);
    if (goalCard) {
        // Cập nhật text %
        const progressText = goalCard.querySelector('.progress');
        if (progressText) progressText.innerText = newProgress + '%';
        
        // Cập nhật tham số onclick để lần sau mở ra đúng số
        let onclickAttr = goalCard.getAttribute('onclick');
        if (onclickAttr) {
            // Thay thế số % cũ bằng số mới (dùng Regex tìm số cuối cùng trong ngoặc)
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
function openGoalDetails(goalId, goalTitle, goalProgress) {
    const modal = document.getElementById('goalDetailsModal');
    if (modal) modal.classList.remove('hidden');

    collapseAddJourneyPanel();
    
    const hiddenId = document.getElementById('hiddenGoalId');
    if (hiddenId) hiddenId.value = goalId;

    const titleEl = document.getElementById('detailGoalTitle');
    if (titleEl) titleEl.innerText = goalTitle;

    // Cập nhật UI Progress ngay lập tức (từ dữ liệu onclick truyền vào)
    updateProgressUI(goalProgress);

    const container = document.getElementById('goalLogsContainer');
    if (container) container.innerHTML = '<div style="text-align:center;padding:20px;color:#888">Loading entries...</div>';

    // Gọi API lấy log mới nhất
    fetch(`api/get_goal_logs.php?goal_id=${goalId}`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                renderGoalLogs(res.data, container);
                
                // Tính lại Max Progress chuẩn xác từ DB
                let maxP = 0;
                if(res.data && res.data.length > 0) {
                    res.data.forEach(l => { if(parseInt(l.progress_update) > maxP) maxP = parseInt(l.progress_update); });
                }
                // Cập nhật lại UI nếu số tính được khác số ban đầu
                if(maxP !== goalProgress) updateProgressUI(maxP);
                
            } else {
                if(container) container.innerHTML = '<p style="color:red">Error loading data</p>';
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
    updateProgressUI(0);
}

// Helper: Cập nhật các thành phần Progress trong Modal
function updateProgressUI(percent) {
    const pt = document.getElementById('detailGoalProgressText');
    const pf = document.getElementById('detailGoalProgressFill');
    const sl = document.getElementById('progressSlider');
    const sv = document.getElementById('sliderValue');
    
    if(pt) pt.innerText = percent + '%';
    if(pf) pf.style.width = percent + '%';
    if(sl) sl.value = percent;
    if(sv) sv.innerText = percent + '%';
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
function deleteCurrentGoal() {
    if (confirm("Delete this goal?")) alert("Cần backend xử lý.");
}


/* =========================================
   PHẦN 3: MODAL CHI TIẾT ENTRY (XEM/SỬA/XÓA)
   ================================********* */

function openEntryDetail(log) {
    currentLogData = log;
    const modal = document.getElementById('entryDetailModal');
    if(modal) modal.classList.remove('hidden');
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
        imgTag.src = log.image; imgTag.style.display='block'; ph.style.display='none';
    } else {
        imgTag.style.display='none'; ph.style.display='block';
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
    if(showEdit) { view.classList.add('hidden'); edit.classList.remove('hidden'); }
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
            if(data.new_progress !== undefined) {
                updateProgressUI(data.new_progress);
                updateGoalCardUI(gid, data.new_progress);
            }
            
            fetch(`api/get_goal_logs.php?goal_id=${gid}`)
                .then(r => r.json()).then(d => { if(d.status === 'success') renderGoalLogs(d.data, container); });
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
    document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'addJourneyForm') {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const saveBtn = form.querySelector('.btn-save-panel');
            const originalText = saveBtn.innerText;

            saveBtn.innerText = 'Saving...'; saveBtn.disabled = true;

            fetch('api/add_journey.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert("Thêm thành công!");
                    collapseAddJourneyPanel();
                    form.reset();
                    
                    // Cập nhật UI
                    const gid = document.getElementById('hiddenGoalId').value;
                    const container = document.getElementById('goalLogsContainer');
                    
                    if (data.new_progress !== undefined) {
                        updateProgressUI(data.new_progress);
                        updateGoalCardUI(gid, data.new_progress);
                    }

                    fetch(`api/get_goal_logs.php?goal_id=${gid}`)
                        .then(r => r.json()).then(d => { if(d.status === 'success') renderGoalLogs(d.data, container); });

                } else {
                    alert("Lỗi: " + data.message);
                }
            })
            .finally(() => { saveBtn.innerText = originalText; saveBtn.disabled = false; });
        }
    });

    // 3. Submit EDIT ENTRY
    const editForm = document.getElementById('editEntryForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
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
                        .then(r => r.json()).then(d => { if(d.status === 'success') renderGoalLogs(d.data, container); });
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
                    for(let i=0; i<slotCount; i++) {
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
        reader.onload = function(e) {
            const display = document.getElementById('profileAvatarDisplay');
            if(display) display.src = e.target.result;
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