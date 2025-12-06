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

        // 3. Cập nhật tham số onclick
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

// --- HÀM SAVE GOAL (ĐÃ LÀM SẠCH & TỐI ƯU) ---
function saveGoal(event) {
    if (event) event.preventDefault();

    const titleInput = document.getElementById('goalTitle');
    const topicInput = document.getElementById('goalTopicName');

    if (!titleInput) return;

    const title = titleInput.value.trim();
    const topicName = topicInput ? topicInput.value.trim() : '';

    if (!title) {
        alert("Please enter a goal title!");
        return;
    }

    const btn = document.querySelector('.btn-save');
    if (btn) {
        btn.innerText = "Saving...";
        btn.disabled = true;
    }

    // Sử dụng FormData để gửi dữ liệu chuẩn xác nhất
    const formData = new FormData();
    formData.append('title', title);
    formData.append('topic_name', topicName);

    // Lưu ý: Đường dẫn API giữ nguyên như lúc fix lỗi
    fetch("/DreamBoard/api/add_goal.php", {
        method: "POST",
        body: formData // Không cần set header Content-Type thủ công khi dùng FormData
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                closeModal();
                location.reload(); // Reload để hiện Goal mới
            } else {
                alert(data.message || "Error creating goal");
                if (btn) {
                    btn.innerText = "Create Goal";
                    btn.disabled = false;
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert("Connection error");
            if (btn) {
                btn.innerText = "Create Goal";
                btn.disabled = false;
            }
        });
}

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

    // Gán màu nền theo topic
    const headerHero = document.getElementById('goalHeaderHero');
    if (headerHero) {
        headerHero.style.background = topicColor || '#f3e8ff';
    }

    // --- CẬP NHẬT PROGRESS ---
    const circlePath = document.getElementById('heroProgressPath');
    const circleText = document.getElementById('heroProgressText');

    if (circlePath) {
        circlePath.style.strokeDasharray = "0, 100";
        setTimeout(() => {
            circlePath.style.strokeDasharray = `${goalProgress}, 100`;
        }, 50);
    }

    if (circleText) {
        circleText.textContent = `${goalProgress}%`;
    }

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

    const circlePath = document.getElementById('heroProgressPath');
    if (circlePath) circlePath.style.strokeDasharray = `0, 100`;
}

function renderGoalLogsNew(logs, container, themeColor) {
    if (!container) return;

    if (!logs || logs.length === 0) {
        container.innerHTML = `<div style="text-align:center;padding:50px;color:#aaa;">
            <i class="ph ph-notebook" style="font-size:40px;margin-bottom:10px;display:block"></i>
            <p>Start your journey by adding the first entry!</p>
            
        </div>`;
        return;
    }

    let html = '';
    let currentDate = '';

    logs.forEach(log => {
        const dateObj = new Date(log.created_at);
        const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

        if (dateStr !== currentDate) {
            if (currentDate !== '') html += `</div>`;
            currentDate = dateStr;
            html += `<div class="timeline-date-group">
                        <div class="timeline-date-label">${dateStr}</div>`;
        }

        const imgHtml = log.image
            ? `<div class="card-img"><img src="${log.image}" alt="img"></div>`
            : `<div class="card-img"><div class="card-img-placeholder">📝</div></div>`;

        const displayTitle = log.journey_title ? log.journey_title : 'Journey Update';
        const logData = JSON.stringify(log).replace(/"/g, '&quot;');

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

    html += `</div>`;
    container.innerHTML = html;
}

function updateProgressUI(percent) {
    const sl = document.getElementById('progressSlider');
    const sv = document.getElementById('sliderValue');
    if (sl) sl.value = percent;
    if (sv) sv.innerText = percent + '%';

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

function deleteCurrentGoal() {
    const hiddenInput = document.getElementById('hiddenGoalId');

    if (!hiddenInput) return;

    const goalId = hiddenInput.value;
    if (!goalId) {
        alert("Lỗi: Không xác định được mục tiêu cần xóa!");
        return;
    }

    if (confirm("⚠️ Bạn có chắc chắn muốn xóa mục tiêu này?\nTất cả nhật ký (Journey) thuộc về nó cũng sẽ bị xóa vĩnh viễn!")) {

        const btnDelete = document.querySelector('.btn-delete-styled');
        const originalText = btnDelete ? btnDelete.innerHTML : 'Delete';
        if (btnDelete) {
            btnDelete.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Deleting...';
            btnDelete.disabled = true;
        }

        fetch('api/delete_goal.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `goal_id=${goalId}`
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert("Đã xóa mục tiêu thành công!");
                    closeGoalDetails();
                    location.reload();
                } else {
                    alert("Lỗi: " + (data.message || "Không thể xóa"));
                    if (btnDelete) {
                        btnDelete.innerHTML = originalText;
                        btnDelete.disabled = false;
                    }
                }
            })
            .catch(err => {
                console.error(err);
                alert("Lỗi kết nối server");
                if (btnDelete) {
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

    // 1. Data Text
    const dateStr = new Date(log.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    document.getElementById('detailEntryDate').innerText = dateStr;
    
    // Title logic
    const displayTitle = log.journey_title ? log.journey_title : 'Journey Entry';
    document.getElementById('detailEntryTitle').innerText = displayTitle;

    document.getElementById('detailEntryMood').innerText = log.mood || 'Feeling good';
    document.getElementById('detailEntryText').innerText = log.content;
    document.getElementById('detailEntryProgress').innerText = '+' + log.progress_update + '%';

    // 2. XỬ LÝ ẨN HIỆN CỘT ẢNH (LOGIC MỚI)
    const mediaColumn = document.querySelector('.entry-split-media'); 
    const imgTag = document.getElementById('detailEntryImg');

    if (log.image) {
        mediaColumn.style.display = 'flex'; 
        imgTag.src = log.image;
        imgTag.style.display = 'block';
        
        // --- THÊM DÒNG NÀY: Bấm vào ảnh để phóng to ---
        imgTag.onclick = function() {
            openFullImage(this.src);
        };
        // ----------------------------------------------
        
        const noImgDiv = document.getElementById('detailNoImage');
        if(noImgDiv) noImgDiv.style.display = 'none';
    } else {
        mediaColumn.style.display = 'none'; 
    }

    // 3. Fill Form Edit
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
                        fetch(`api/get_goal_logs.php?goal_id=${gid}`)
                            .then(r => r.json()).then(d => {
                                if (d.status === 'success') renderGoalLogsNew(d.data, container, activeTopicColor);
                            });

                        // 4. Kiểm tra thư
                        if (data.letter_data) {
                            showLetterNotification(data.letter_data);
                        }

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
        fetch('api/get_vision.php')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.items) {
                    miniCanvas.innerHTML = '';
                    const layoutMeta = data.items.find(i => i.type === 'layout_meta');

                    // A. Dựng khung Layout
                    if (layoutMeta && layoutMeta.content !== 'free') {
                        const grid = document.createElement('div');
                        grid.className = `layout-${layoutMeta.content}`;
                        miniCanvas.appendChild(grid);

                        let slotCount = 9;
                        if (layoutMeta.content === 'masonry') slotCount = 5;
                        if (layoutMeta.content === 'hero-center') slotCount = 9;

                        for (let i = 0; i < slotCount; i++) {
                            const slot = document.createElement('div');
                            slot.className = 'frame-slot';
                            slot.id = `mini-slot-${i}`;
                            grid.appendChild(slot);
                        }
                    }

                    // B. Điền các Item
                    data.items.forEach(item => {
                        if (layoutMeta && layoutMeta.content !== 'free' && item.type === 'layout_slot') {
                            const targetSlot = document.getElementById(`mini-slot-${item.z_index}`);
                            if (targetSlot && item.image_path) {
                                targetSlot.innerHTML = `<img src="${item.image_path}" style="object-position: ${item.content || 'center'}">`;
                                targetSlot.classList.add('has-image');
                            }
                        }
                        else if (item.type !== 'layout_meta' && item.type !== 'layout_slot') {
                            const el = document.createElement('div');
                            el.className = `board-item`;
                            if (item.type.startsWith('text')) {
                                el.classList.add('item-' + item.type);
                                el.classList.add('item-text');
                                el.innerText = item.content;
                                el.style.fontSize = '';
                            }
                            else {
                                el.classList.add('item-sticker');
                                el.innerHTML = item.content;
                            }
                            el.style.left = item.pos_x + 'px';
                            el.style.top = item.pos_y + 'px';
                            el.style.zIndex = item.z_index;
                            miniCanvas.appendChild(el);
                        }
                    });
                } else {
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
        if (file.size > 5 * 1024 * 1024) { // 5MB
            alert("File ảnh quá lớn! Vui lòng chọn ảnh dưới 5MB.");
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            const display = document.getElementById('profileAvatarDisplay');
            if (display) display.src = e.target.result;
        }
        reader.readAsDataURL(file);

        const formData = new FormData();
        formData.append('avatar', file);

        fetch('api/update_avatar.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json()) // Đã sửa lại thành .json() gọn gàng
            .then(data => {
                if (data.status === 'success') {
                    console.log("Avatar updated successfully!");
                } else {
                    alert("Lỗi: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Có lỗi xảy ra khi upload avatar");
            });
    }
}
/* =========================================
   PHẦN 5: XỬ LÝ POPUP FUTURE LETTER
   ================================********* */

let pendingLetterContent = null;

// 1. Hiện Popup thông báo
function showLetterNotification(letterData) {
    pendingLetterContent = letterData;
    const notiMood = document.getElementById('notiMood');
    if (notiMood) notiMood.innerText = letterData.mood;

    const modal = document.getElementById('letterNotificationModal');
    if (modal) modal.classList.remove('hidden');
}

// 2. Đóng Popup thông báo
function closeLetterNotification() {
    const modal = document.getElementById('letterNotificationModal');
    if (modal) modal.classList.add('hidden');
}

// 3. Mở thư chi tiết
function openFullLetter() {
    closeLetterNotification();

    if (!pendingLetterContent) return;

    document.getElementById('letterMoodDisplay').innerText = pendingLetterContent.mood;
    document.getElementById('letterDateDisplay').innerText = pendingLetterContent.created_at;

    const safeContent = pendingLetterContent.message
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\n/g, "<br>");

    document.getElementById('letterMessageContent').innerHTML = safeContent;

    const modal = document.getElementById('letterContentModal');
    if (modal) modal.classList.remove('hidden');
}

// 4. Đóng thư chi tiết
function closeFullLetter() {
    const modal = document.getElementById('letterContentModal');
    if (modal) modal.classList.add('hidden');
}
// --- LIGHTBOX FUNCTIONS ---
function openFullImage(src) {
    const lightbox = document.getElementById('imageLightbox');
    const img = document.getElementById('lightboxImg');
    if (lightbox && img) {
        img.src = src;
        lightbox.classList.remove('hidden');
    }
}

function closeFullImage() {
    const lightbox = document.getElementById('imageLightbox');
    if (lightbox) {
        lightbox.classList.add('hidden');
    }
}
/* --- SOUL REFLECTION LOGIC --- */
function callSoulReflection() {
    // 1. Lấy nội dung nhật ký hiện tại (biến currentLogData đã có sẵn trong file JS của bạn)
    if (!currentLogData || !currentLogData.content) {
        alert("Không tìm thấy nội dung nhật ký!");
        return;
    }

    const btn = document.querySelector('.btn-soul-reflect');
    const card = document.getElementById('aiInsightCard');
    
    // 2. Hiệu ứng Loading
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Connecting to Universe...';
    btn.disabled = true;
    card.classList.add('hidden'); // Ẩn kết quả cũ

    // 3. Gọi API PHP
    fetch('api/ai_reflect.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: currentLogData.content })
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            // 4. Điền dữ liệu vào thẻ
            document.getElementById('aiAnalysis').innerText = res.data.analysis;
            document.getElementById('aiAdvice').innerText = res.data.advice;
            document.getElementById('aiQuote').innerText = '"' + res.data.quote + '"';
            
            // Hiện thẻ
            card.classList.remove('hidden');
            
            // Scroll xuống cho đẹp
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            alert("Vũ trụ đang bận: " + res.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Lỗi kết nối.");
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}