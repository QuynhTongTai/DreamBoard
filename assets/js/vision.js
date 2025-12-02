document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('canvas');
    const fileInput = document.getElementById('fileInput');
    const frameInput = document.getElementById('frameImageInput');
    
    let zIndexCounter = 100;
    let currentLayout = 'free';
    let currentSlotElement = null;

    // 1. LOAD DỮ LIỆU
    /* =========================================
       1. LOAD DỮ LIỆU TỪ SERVER
       ================================********* */
    fetch('api/get_vision.php')
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success' && data.items) {
                
                // Tìm xem user đã lưu layout nào chưa?
                const layoutMeta = data.items.find(i => i.type === 'layout_meta');

                if (layoutMeta) {
                    // TRƯỜNG HỢP 1: ĐÃ LƯU LAYOUT (Grid hoặc Free)
                    // Gọi hàm applyLayout để dựng khung trước (false = không xóa data)
                    window.applyLayout(layoutMeta.content, false); 
                    
                    // Nếu là Grid, điền ảnh vào ô
                    if (layoutMeta.content !== 'free') {
                        data.items.forEach(item => {
                            if (item.type === 'layout_slot') {
                                const slots = document.querySelectorAll('.frame-slot');
                                const slot = slots[item.z_index];
                                if (slot && item.image_path) {
                                    slot.innerHTML = `<img src="${item.image_path}">`;
                                    slot.classList.add('has-image');
                                    // Khôi phục vị trí ảnh
                                    const img = slot.querySelector('img');
                                    if (item.content) img.style.objectPosition = item.content;
                                    attachDragToImage(img);
                                }
                            }
                        });
                    } 
                    // Nếu là Free, vẽ item trôi nổi
                    else {
                        data.items.forEach(item => {
                            if (item.type !== 'layout_meta' && item.type !== 'layout_slot') {
                                renderFloatingItem(item);
                            }
                        });
                    }

                } else {
                    // TRƯỜNG HỢP 2: CHƯA CÓ DỮ LIỆU (LẦN ĐẦU VÀO)
                    // Bắt buộc gọi hàm này để khởi tạo giao diện mặc định
                    window.applyLayout('free', false);
                }
            }
        })
        .catch(err => console.error("Lỗi load vision:", err));

    // 2. XỬ LÝ LAYOUT
    window.applyLayout = function(layoutName, confirmClear = true) {
        if (confirmClear && canvas.children.length > 0 && !document.querySelector('.canvas-placeholder')) {
            if (!confirm("Đổi layout sẽ xóa bảng hiện tại. Tiếp tục?")) return;
        }

        canvas.innerHTML = '';
        canvas.className = 'vision-board-canvas';
        document.getElementById('frameSelectionPanel').classList.add('hidden');

        if (layoutName === 'free') {
            currentLayout = 'free';
            canvas.innerHTML = '<div class="canvas-placeholder"><i class="ph-duotone ph-image-square"></i><p>Free Style Mode</p></div>';
            return;
        }

        currentLayout = layoutName;
        const grid = document.createElement('div');
        grid.className = `layout-${layoutName}`;
        canvas.appendChild(grid);

        let slotCount = 9;
        if (layoutName === 'masonry') slotCount = 3;
        if (layoutName === 'film-strip') slotCount = 4;

        for (let i = 0; i < slotCount; i++) {
            const slot = document.createElement('div');
            slot.className = 'frame-slot';
            slot.innerHTML = '<span class="slot-hint">+</span>';
            
            // Click để upload
            slot.addEventListener('click', function(e) {
                if(e.target.tagName === 'IMG') return; // Nếu click vào ảnh thì ko upload lại
                currentSlotElement = this;
                frameInput.click();
            });
            grid.appendChild(slot);
        }
    };

    window.toggleFramePanel = function() {
        document.getElementById('frameSelectionPanel').classList.toggle('hidden');
    };

    // 3. UPLOAD ẢNH VÀO KHUNG
    if (frameInput) {
        frameInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (!file || !currentSlotElement) return;

            const formData = new FormData(); formData.append('image', file);
            fetch('api/upload_vision.php', { method: 'POST', body: formData })
            .then(res => res.json()).then(data => {
                if(data.status === 'success') {
                    currentSlotElement.innerHTML = `<img src="${data.path}">`;
                    currentSlotElement.classList.add('has-image');
                    attachDragToImage(currentSlotElement.querySelector('img'));
                }
            });
            this.value = '';
        });
    }

    // 4. KÉO ĐỂ CĂN CHỈNH ẢNH (REPOSITION)
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
            if(isDragging) {
                isDragging = false;
                img.parentElement.classList.remove('is-dragging');
            }
        });
    }

    // 5. THÊM ITEM TRANG TRÍ
    document.getElementById('btnAddText').addEventListener('click', () => {
        renderFloatingItem({ type: 'text', content: 'Note...', pos_x: 50, pos_y: 50 });
    });
    document.getElementById('btnStickers').addEventListener('click', () => {
        const stickers = ['✨', '❤️', '🎯', '🍀', '🔥'];
        const random = stickers[Math.floor(Math.random() * stickers.length)];
        renderFloatingItem({ type: 'sticker', content: random, pos_x: 100, pos_y: 100 });
    });

    function renderFloatingItem(data) {
        const el = document.createElement('div');
        el.className = `board-item item-${data.type}`;
        el.style.left = data.pos_x + 'px';
        el.style.top = data.pos_y + 'px';
        el.style.zIndex = zIndexCounter++;
        
        if (data.type === 'text') {
            el.contentEditable = true; el.innerText = data.content;
        } else {
            el.innerHTML = data.content; el.style.fontSize = '60px';
        }

        // Drag Logic
        let isDown = false, offset = [0,0];
        el.addEventListener('mousedown', (e) => { isDown = true; offset = [el.offsetLeft-e.clientX, el.offsetTop-e.clientY]; });
        document.addEventListener('mouseup', () => isDown = false);
        document.addEventListener('mousemove', (e) => { if(isDown) { el.style.left = (e.clientX+offset[0])+'px'; el.style.top = (e.clientY+offset[1])+'px'; }});
        el.addEventListener('dblclick', () => el.remove());

        canvas.appendChild(el);
    }

    // 6. LƯU BOARD
    document.getElementById('saveBtn').addEventListener('click', () => {
        const items = [];
        
        // Lưu Layout
        if (currentLayout !== 'free') {
            items.push({ type: 'layout_meta', content: currentLayout, pos_x:0, pos_y:0, width:0, height:0, z_index:0 });
            document.querySelectorAll('.frame-slot').forEach((slot, index) => {
                const img = slot.querySelector('img');
                if (img) {
                    items.push({
                        type: 'layout_slot',
                        image_path: img.src,
                        z_index: index,
                        content: img.style.objectPosition || '50% 50%', // Lưu vị trí căn chỉnh
                        pos_x: 0, pos_y: 0, width: 0, height: 0
                    });
                }
            });
        }

        // Lưu Stickers/Text
        document.querySelectorAll('.board-item').forEach(el => {
            items.push({
                type: el.classList.contains('item-text') ? 'text' : 'sticker',
                content: el.innerText,
                image_path: '',
                pos_x: parseFloat(el.style.left), pos_y: parseFloat(el.style.top),
                width: 0, height: 0, z_index: 100, rotation: 0
            });
        });

        fetch('api/save_vision.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ items: items })
        }).then(res => res.json()).then(d => {
            if(d.status === 'success') alert('Saved! 💾');
            else alert('Error saving');
        });
    });
    
    // Clear
    document.getElementById('clearBtn').addEventListener('click', () => {
        if(confirm('Clear all?')) {
            window.applyLayout('free', false);
            fetch('api/save_vision.php', { method: 'POST', body: JSON.stringify({ items: [] }) });
        }
    });
});