document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('canvas');
    const frameInput = document.getElementById('frameImageInput');

    let zIndexCounter = 100;
    let currentLayout = 'free';
    let currentSlotElement = null;

    // 1. LOAD DỮ LIỆU
    // 1. LOAD DỮ LIỆU
    fetch('api/get_vision.php')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.items) {
                const layoutMeta = data.items.find(i => i.type === 'layout_meta');

                if (layoutMeta) {
                    // Bước 1: Dựng khung layout trước
                    window.applyLayout(layoutMeta.content, false);

                    // Bước 2: Duyệt qua TẤT CẢ các item để hiển thị
                    data.items.forEach(item => {
                        
                        // TRƯỜNG HỢP 1: Ảnh nằm trong khung (Layout Slot)
                        // Chỉ xử lý khi không phải chế độ Free và item là layout_slot
                        if (layoutMeta.content !== 'free' && item.type === 'layout_slot') {
                            const slots = document.querySelectorAll('.frame-slot');
                            // Dùng z_index để xác định ô thứ mấy
                            const slot = slots[item.z_index]; 
                            if (slot && item.image_path) {
                                slot.innerHTML = `<img src="${item.image_path}">`;
                                slot.classList.add('has-image');
                                const img = slot.querySelector('img');
                                // Load vị trí căn chỉnh ảnh (object-position)
                                if (item.content) img.style.objectPosition = item.content;
                                attachDragToImage(img);
                            }
                        }
                        
                        // TRƯỜNG HỢP 2: Vật phẩm trôi nổi (Sticker, Text, Note...)
                        // Hiển thị ở CẢ chế độ Free lẫn Grid Layout
                        // Loại trừ layout_meta (đã dùng ở trên) và layout_slot (đã xử lý ở trên)
                        else if (item.type !== 'layout_meta' && item.type !== 'layout_slot') {
                            renderFloatingItem(item);
                        }
                    });

                } else {
                    // Nếu không có dữ liệu cũ thì mặc định Free
                    window.applyLayout('free', false);
                }
            }
        })
        .catch(err => console.error("Lỗi load vision:", err));

    // 2. XỬ LÝ LAYOUT
    window.applyLayout = function (layoutName, confirmClear = true) {
        const hasContent = canvas.children.length > 0 && !canvas.querySelector('.canvas-placeholder');

        if (confirmClear && hasContent) {
            if (!confirm("Đổi layout sẽ làm mới bảng. Bạn có chắc không?")) return;
        }

        canvas.innerHTML = '';
        canvas.className = 'vision-board-canvas';
        document.getElementById('frameSelectionPanel').classList.add('hidden');

        if (layoutName === 'free') {
            currentLayout = 'free';
            canvas.innerHTML = `
                <div class="canvas-placeholder">
                    <i class="ph-duotone ph-pencil-simple-slash"></i>
                    <p>Free Mode - Drag & Drop Stickers</p>
                </div>`;
            return;
        }

        currentLayout = layoutName;
        const grid = document.createElement('div');
        grid.className = `layout-${layoutName}`;
        grid.style.width = "100%";
        grid.style.height = "100%";
        canvas.appendChild(grid);

        let slotCount = 9;
        if (layoutName === 'masonry') slotCount = 5;
        if (layoutName === 'hero-center') slotCount = 9;

        for (let i = 0; i < slotCount; i++) {
            const slot = document.createElement('div');
            slot.className = 'frame-slot';
            slot.innerHTML = '<span class="slot-hint">+</span>';
            
            // 1. CLICK ĐƠN: Chỉ hoạt động khi ô đang trống
            slot.addEventListener('click', function(e) {
                // Nếu click vào ảnh đang có -> Bỏ qua (để dành cho thao tác kéo)
                if(e.target.tagName === 'IMG') return; 
                
                currentSlotElement = this;
                frameInput.click();
            });

            // 2. DOUBLE CLICK (MỚI): Để thay thế ảnh khác
            slot.addEventListener('dblclick', function(e) {
                currentSlotElement = this;
                frameInput.click();
            });

            grid.appendChild(slot);
        }
    };

    window.toggleFramePanel = function () {
        document.getElementById('frameSelectionPanel').classList.toggle('hidden');
        document.getElementById('textSelectionPanel').classList.add('hidden');
        document.getElementById('stickerSelectionPanel').classList.add('hidden');
    };

    window.toggleTextPanel = function () {
        document.getElementById('textSelectionPanel').classList.toggle('hidden');
        document.getElementById('frameSelectionPanel').classList.add('hidden');
        document.getElementById('stickerSelectionPanel').classList.add('hidden');
    };

    // MỚI: Toggle Sticker Menu
    window.toggleStickerPanel = function () {
        document.getElementById('stickerSelectionPanel').classList.toggle('hidden');
        document.getElementById('frameSelectionPanel').classList.add('hidden');
        document.getElementById('textSelectionPanel').classList.add('hidden');
    };

    // MỚI: Chọn Sticker từ thư viện
    window.addSticker = function (contentHtml) {
        renderFloatingItem({ type: 'sticker', content: contentHtml, pos_x: 100, pos_y: 100 });
        document.getElementById('stickerSelectionPanel').classList.add('hidden');
    }

    window.addText = function (type) {
        let content = 'Double click to edit';
        if (type === 'text_heading') content = 'MY GOAL';
        if (type === 'text_quote') content = '"Dream big, work hard"';

        renderFloatingItem({ type: type, content: content, pos_x: 150, pos_y: 150 });
        document.getElementById('textSelectionPanel').classList.add('hidden');
    };

    // 3. UPLOAD ẢNH (Giữ nguyên)
    if (frameInput) {
        frameInput.addEventListener('change', function (e) {
            const file = this.files[0];
            if (!file || !currentSlotElement) return;

            const formData = new FormData(); formData.append('image', file);
            fetch('api/upload_vision.php', { method: 'POST', body: formData })
                .then(res => res.json()).then(data => {
                    if (data.status === 'success') {
                        currentSlotElement.innerHTML = `<img src="${data.path}">`;
                        currentSlotElement.classList.add('has-image');
                        attachDragToImage(currentSlotElement.querySelector('img'));
                    }
                });
            this.value = '';
        });
    }

    // 4. DRAG ẢNH
    function attachDragToImage(img) {
        let isDragging = false, startX, startY;
        let initialPosX = 50, initialPosY = 50;

        img.addEventListener('mousedown', (e) => {
            e.preventDefault();
            isDragging = true;
            startX = e.clientX; startY = e.clientY;
            img.parentElement.classList.add('is-dragging');
            const pos = window.getComputedStyle(img).objectPosition.split(' ');
            initialPosX = parseFloat(pos[0]) || 50;
            initialPosY = parseFloat(pos[1]) || 50;
        });

        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            const deltaX = (startX - e.clientX) * 0.2;
            const deltaY = (startY - e.clientY) * 0.2;
            let newX = Math.max(0, Math.min(100, initialPosX + deltaX));
            let newY = Math.max(0, Math.min(100, initialPosY + deltaY));
            img.style.objectPosition = `${newX}% ${newY}%`;
        });

        window.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                img.parentElement.classList.remove('is-dragging');
            }
        });
    }

    // 5. RENDER ITEM (Cập nhật để hiển thị sticker HTML)
    function renderFloatingItem(data) {
        const el = document.createElement('div');
        el.className = `board-item`;

        if (data.type.startsWith('text')) {
            el.classList.add('item-' + data.type);
            el.classList.add('item-text');
            el.contentEditable = true;
            el.innerText = data.content;
            el.style.fontSize = '';
        }
        else {
            el.classList.add(`item-sticker`); // Class chung cho sticker
            // Ở đây data.content có thể là HTML (ví dụ: <i class...>) hoặc Emoji
            el.innerHTML = data.content;
        }

        el.style.left = data.pos_x + 'px';
        el.style.top = data.pos_y + 'px';
        el.style.zIndex = zIndexCounter++;

        let isDown = false, offset = [0, 0];
        el.addEventListener('mousedown', (e) => { isDown = true; offset = [el.offsetLeft - e.clientX, el.offsetTop - e.clientY]; });
        document.addEventListener('mouseup', () => isDown = false);
        document.addEventListener('mousemove', (e) => { if (isDown) { el.style.left = (e.clientX + offset[0]) + 'px'; el.style.top = (e.clientY + offset[1]) + 'px'; } });
        el.addEventListener('dblclick', () => el.remove());

        canvas.appendChild(el);
    }

    // 6. LƯU BOARD (Đã nâng cấp: Lưu kèm ảnh Preview cho Journal)
    document.getElementById('saveBtn').addEventListener('click', () => {
        
        // --- BƯỚC 1: Gom dữ liệu JSON (Giữ nguyên logic cũ của bạn) ---
        const items = [];

        if (currentLayout !== 'free') {
            items.push({ type: 'layout_meta', content: currentLayout, pos_x: 0, pos_y: 0, width: 0, height: 0, z_index: 0 });
            document.querySelectorAll('.frame-slot').forEach((slot, index) => {
                const img = slot.querySelector('img');
                if (img) {
                    items.push({
                        type: 'layout_slot',
                        image_path: img.src,
                        z_index: index,
                        content: img.style.objectPosition || '50% 50%',
                        pos_x: 0, pos_y: 0, width: 0, height: 0
                    });
                }
            });
        }

        document.querySelectorAll('.board-item').forEach(el => {
            let type = 'sticker';
            let content = el.innerHTML;

            if (el.classList.contains('item-text')) {
                if (el.classList.contains('item-text_heading')) type = 'text_heading';
                else if (el.classList.contains('item-text_body')) type = 'text_body';
                else if (el.classList.contains('item-text_quote')) type = 'text_quote';
                else if (el.classList.contains('item-text_note')) type = 'text_note';
                else if (el.classList.contains('item-text_neon')) type = 'text_neon';
                else type = 'text';
                content = el.innerText;
            }

            items.push({
                type: type,
                content: content,
                image_path: '',
                pos_x: parseFloat(el.style.left), pos_y: parseFloat(el.style.top),
                width: 0, height: 0, z_index: 100, rotation: 0
            });
        });

        // --- BƯỚC 2: CHỤP ẢNH CANVAS VÀ GỬI CÙNG JSON (PHẦN MỚI) ---
        
        // Hiệu ứng nút bấm đang xử lý
        const saveBtn = document.getElementById('saveBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Saving...';
        saveBtn.disabled = true;

        const board = document.getElementById('canvas');

        // Dùng html2canvas chụp lại bảng hiện tại
        html2canvas(board, { scale: 1, useCORS: true }).then(canvas => {
            // Chuyển canvas thành chuỗi ảnh Base64
            const base64Image = canvas.toDataURL('image/png'); 

            // Gửi cả items (JSON) và preview_image (Base64) lên server
            fetch('api/save_vision.php', {
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    items: items,
                    preview_image: base64Image // <--- Gửi kèm ảnh tại đây
                })
            })
            .then(res => res.json())
            .then(d => {
                if (d.status === 'success') alert('Saved successfully! 💾');
                else alert('Error saving: ' + d.message);
            })
            .catch(err => {
                console.error(err);
                alert("Error saving board connection.");
            })
            .finally(() => {
                // Trả lại trạng thái nút bấm ban đầu
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        });
    });
    document.getElementById('clearBtn').addEventListener('click', () => {
        if (confirm('Clear all?')) {
            window.applyLayout('free', false);
            fetch('api/save_vision.php', { method: 'POST', body: JSON.stringify({ items: [] }) });
        }
    });
    // ... (Code Save và Clear ở trên giữ nguyên)

    // 7. EXPORT HÌNH ẢNH (MỚI)
    document.getElementById('exportBtn').addEventListener('click', () => {
        const board = document.getElementById('canvas');

        // Hiệu ứng thông báo đang xử lý
        const originalText = document.getElementById('exportBtn').innerHTML;
        document.getElementById('exportBtn').innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Saving...';

        // Dùng html2canvas chụp lại vùng #canvas
        // scale: 2 để ảnh nét hơn (chất lượng cao)
        html2canvas(board, { scale: 2, useCORS: true }).then(canvas => {

            // Tạo thẻ <a> ảo để tự động tải xuống
            const link = document.createElement('a');
            link.download = 'My-Vision-Board-2026.png';
            link.href = canvas.toDataURL('image/png');
            link.click();

            // Trả lại nút bấm cũ
            document.getElementById('exportBtn').innerHTML = originalText;
        }).catch(err => {
            console.error(err);
            alert("Lỗi khi xuất ảnh. Vui lòng thử lại!");
            document.getElementById('exportBtn').innerHTML = originalText;
        });
    });

    // Kết thúc file (đóng DOMContentLoaded)
});