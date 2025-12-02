<script src="https://unpkg.com/phosphor-icons"></script>

<main class="vision-page container">
  
  <aside class="vision-sidebar">
    <div class="sidebar-header">
      <h4><i class="ph-fill ph-palette"></i> Toolbox</h4>
    </div>

    <div class="tools-grid">
      
      <label class="tool-item" title="Upload Image">
        <div class="icon-box bg-blue"><i class="ph-fill ph-image"></i></div>
        <div class="tool-text">
            <span>Photo</span>
            <small>Upload free</small>
        </div>
        <input id="fileInput" type="file" accept="image/*" style="display:none">
      </label>

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
          <div class="layout-option" onclick="applyLayout('film-strip')">
              <div class="mini-icon film"></div> <span>Film</span>
          </div>
          <div class="layout-reset" onclick="applyLayout('free')">
              <i class="ph-bold ph-arrow-u-up-left"></i> Reset to Free
          </div>
      </div>

      <div class="tool-item" id="btnAddText">
        <div class="icon-box bg-pink"><i class="ph-fill ph-text-t"></i></div>
        <div class="tool-text">
            <span>Text</span>
            <small>Add note</small>
        </div>
      </div>

      <div class="tool-item" id="btnStickers">
        <div class="icon-box bg-yellow"><i class="ph-fill ph-star"></i></div>
        <div class="tool-text">
            <span>Sticker</span>
            <small>Decorate</small>
        </div>
      </div>

    </div>
    
    <input type="file" id="frameImageInput" accept="image/*" style="display:none">

    <div class="sidebar-footer">
        <p>✨ Tip: Click & Drag image inside frame to adjust.</p>
    </div>
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