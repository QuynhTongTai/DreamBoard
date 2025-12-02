<script src="https://unpkg.com/phosphor-icons"></script>

<main class="vision-page container">
  
  <aside class="vision-sidebar">
    <div class="sidebar-header">
      <h4><i class="ph-fill ph-palette"></i> Toolbox</h4>
    </div>

    <div class="tools-grid">
      
      <div class="tool-item" id="btnFrames" onclick="toggleFramePanel()">
        <div class="icon-box bg-purple"><i class="ph-fill ph-squares-four"></i></div>
        <div class="tool-text">
            <span>Layouts</span>
            <small>Grid frames</small>
        </div>
      </div>

      <div id="frameSelectionPanel" class="hidden tool-submenu">
          <div class="layout-option" onclick="applyLayout('grid-3x3')">
              <div class="mini-icon grid-3x3"></div> <span>Grid 9</span>
          </div>
          <div class="layout-option" onclick="applyLayout('masonry')">
              <div class="mini-icon masonry"></div> <span>Masonry</span>
          </div>
          <div class="layout-option" onclick="applyLayout('hero-center')">
              <div class="mini-icon hero"></div> <span>Focus</span>
          </div>
      </div>

      <div class="tool-item" id="btnAddText" onclick="toggleTextPanel()">
        <div class="icon-box bg-pink"><i class="ph-fill ph-text-t"></i></div>
        <div class="tool-text">
            <span>Text</span>
            <small>Add note</small>
        </div>
      </div>

      <div id="textSelectionPanel" class="hidden tool-submenu">
          <div class="layout-option" onclick="addText('text_heading')">
              <i class="ph-bold ph-text-h-one" style="color:#6c5ce7"></i> <span>Heading</span>
          </div>
          <div class="layout-option" onclick="addText('text_body')">
              <i class="ph-bold ph-text-align-left" style="color:#636e72"></i> <span>Paragraph</span>
          </div>
          <div class="layout-option" onclick="addText('text_quote')">
              <i class="ph-bold ph-quotes" style="color:#fdcb6e"></i> <span>Quote Card</span>
          </div>
          <div class="layout-option" onclick="addText('text_note')">
              <i class="ph-fill ph-sticky-note" style="color:#ffeaa7; text-shadow:0 1px 2px #aaa"></i> <span>Sticky Note</span>
          </div>
          <div class="layout-option" onclick="addText('text_neon')">
              <i class="ph-fill ph-lightning" style="color:#ff00de"></i> <span>Neon Style</span>
          </div>
      </div>

      <div class="tool-item" id="btnStickers" onclick="toggleStickerPanel()">
        <div class="icon-box bg-yellow"><i class="ph-fill ph-star"></i></div>
        <div class="tool-text">
            <span>Sticker</span>
            <small>Decorate</small>
        </div>
      </div>

      <div id="stickerSelectionPanel" class="hidden tool-submenu sticker-library">
          
          <p class="library-cat">Targets</p>
          <div class="sticker-grid">
              <div class="sticker-opt" onclick="addSticker('<i class=\'ph-fill ph-airplane-tilt\'></i>')">
                  <i class="ph-fill ph-airplane-tilt"></i>
              </div>
              <div class="sticker-opt" onclick="addSticker('<i class=\'ph-fill ph-currency-dollar\'></i>')">
                  <i class="ph-fill ph-currency-dollar"></i>
              </div>
              <div class="sticker-opt" onclick="addSticker('<i class=\'ph-fill ph-graduation-cap\'></i>')">
                  <i class="ph-fill ph-graduation-cap"></i>
              </div>
              <div class="sticker-opt" onclick="addSticker('<i class=\'ph-fill ph-house-line\'></i>')">
                  <i class="ph-fill ph-house-line"></i>
              </div>
          </div>

          <p class="library-cat">Decor</p>
          <div class="sticker-grid">
              <div class="sticker-opt" onclick="addSticker('<i class=\'ph-fill ph-sparkle\'></i>')">
                  <i class="ph-fill ph-sparkle"></i>
              </div>
              <div class="sticker-opt" onclick="addSticker('<i class=\'ph-fill ph-heart\'></i>')">
                  <i class="ph-fill ph-heart"></i>
              </div>
              <div class="sticker-opt" onclick="addSticker('<i class=\'ph-fill ph-push-pin\'></i>')">
                  <i class="ph-fill ph-push-pin"></i>
              </div>
              <div class="sticker-opt" onclick="addSticker('<i class=\'ph-fill ph-fire\'></i>')">
                  <i class="ph-fill ph-fire"></i>
              </div>
          </div>
          
      </div>

    </div>
    
    <input type="file" id="frameImageInput" accept="image/*" style="display:none">

    
  </aside>

  <section class="canvas-wrapper">
     <div class="canvas-toolbar">
         <div class="toolbar-title">
             <h3>My 2026 Vision</h3>
         </div>
         <div class="toolbar-actions">
             <button class="btn-tool danger" id="clearBtn"><i class="ph-bold ph-trash"></i> Clear</button>
             <button class="btn-tool" id="exportBtn"><i class="ph-bold ph-download-simple"></i> Export</button>
             <button class="btn-tool primary" id="saveBtn"><i class="ph-bold ph-floppy-disk"></i> Save Board</button>
         </div>
     </div>

     <div class="canvas-container">
         <div id="canvas" class="vision-board-canvas">
            <div class="canvas-placeholder">
                <i class="ph-duotone ph-image-square"></i>
                <p>Select a Layout to start</p>
            </div>
         </div>
     </div>
  </section>

</main>