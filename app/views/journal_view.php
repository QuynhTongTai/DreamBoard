<!-- views/journal_view.php -->
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<main class="container">
  <section class="left-col">

    <div class="dashboard-header">
      <div class="header-titles">
        <h3>Journey Dashboard</h3>
        <p>Track your goals & memories</p>
      </div>

      <div class="header-actions">
        <div class="search-wrapper">
          <input type="text" id="searchInput" placeholder="Search goals, logs..." onkeyup="filterContent()">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        </div>

        <a href="javascript:void(0);" onclick="openModal()" class="btn-add-goal">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          <span>New Goal</span>
        </a>
      </div>
    </div>

    <div class="filter-scroll-container" id="topicFilterBar">
      <button class="filter-pill active" onclick="selectTopic('all', this)">All Topics</button>

      <?php if (!empty($topics)): ?>
        <?php foreach ($topics as $t): ?>
          <button class="filter-pill" onclick="selectTopic(<?php echo $t['topic_id']; ?>, this)">
            <span class="dot" style="background:<?php echo htmlspecialchars($t['color']); ?>;"></span>
            <?php echo htmlspecialchars($t['name']); ?>
          </button>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <h4 class="section-title">Active Goals</h4>
    <div class="goals">
      <?php if (!empty($goals)): ?>
        <?php foreach ($goals as $index => $g): ?>

          <?php
          $bgColor = !empty($g['topic_color']) ? $g['topic_color'] : '#f9f9f9';
          $percent = intval($g['progress']);

          // DANH SÁCH ICON "XINH XINH" (Random ngẫu nhiên)
          $cuteIcons = [
            'ph-fill ph-sparkle',       // Lấp lánh
            'ph-fill ph-star',          // Ngôi sao
            'ph-fill ph-heart',         // Trái tim
            'ph-fill ph-flower',        // Bông hoa
            'ph-fill ph-plant',         // Chậu cây
            'ph-fill ph-butterfly',     // Con bướm
            'ph-fill ph-moon-stars',    // Trăng sao
            'ph-fill ph-coffee',        // Cốc cafe chill
            'ph-fill ph-music-notes'    // Nốt nhạc
          ];
          // Chọn bừa 1 cái dựa trên ID của goal để nó cố định (không bị đổi mỗi khi F5)
          $iconIndex = $g['goal_id'] % count($cuteIcons);
          $randomIconClass = $cuteIcons[$iconIndex];
          ?>

          <div class="goal-card filter-item" data-type="goal" data-topic-id="<?php echo $g['topic_id']; ?>"
            data-search-text="<?php echo strtolower(htmlspecialchars($g['title'])); ?>"
            id="goal-card-<?php echo $g['goal_id']; ?>" onclick="openGoalDetails(
    <?php echo $g['goal_id']; ?>, 
    '<?php echo htmlspecialchars($g['title']); ?>', 
    <?php echo $percent; ?>,
    '<?php echo htmlspecialchars($g['topic_color'] ?? '#C6A7FF'); ?>', 
    '<?php echo date('M d, Y', strtotime($g['created_at'])); ?>'
)" style="background-color: <?php echo $bgColor; ?>; <?php if ($index >= 9)
       echo 'display:none;'; ?>">

            <i class="<?php echo $randomIconClass; ?> goal-icon-standalone"></i>

            <div class="goal-info">
              <h4><?php echo htmlspecialchars($g['title']); ?></h4>
              <span class="topic-tag"><?php echo htmlspecialchars($g['topic_name'] ?? 'General'); ?></span>
            </div>

            <div class="circular-progress" style="--p:<?php echo $percent; ?>;">
              <div class="inner-circle"></div>
              <span class="progress-value"><?php echo $percent; ?>%</span>
            </div>

          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="goals-footer-cta">
      <a href="javascript:void(0);" class="btn-view-all">View All Goals</a>
    </div>
    <div class="dream-canvas-section">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h4 class="section-title" style="margin:0;">Your Dream Canvas</h4>
        <a href="vision.php" style="font-size:13px; color:#6b5bff; text-decoration:none; font-weight:600;">Edit Canvas
          &rarr;</a>
      </div>

      <div class="canvas-preview-wrapper" style="text-align: center; padding: 10px 0;">
        <?php if ($visionPreviewSrc): ?>
          <img src="<?php echo $visionPreviewSrc; ?>" alt="My Vision Board"
            style="width: 100%; max-width: 550px; height: auto; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.08);">
        <?php else: ?>
          <div
            style="height:150px; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#999; background:#f9f9fc; border-radius:12px; max-width: 600px; margin: 0 auto;">
            <p style="margin-bottom:8px; font-size:14px;">No vision board yet</p>
            <a href="vision.php" class="btn-view-all" style="background:#fff; font-size:12px; padding: 6px 14px;">Create
              now</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <h4 class="section-title">Journey Timeline</h4>
    <div class="timeline">
      <div class="timeline-list">
        <?php if (!empty($logs)): ?>
          <?php foreach ($logs as $log): ?>
            <div class="tl-item filter-item" data-type="log" data-topic-id="<?php echo $log['topic_id'] ?? 0; ?>"
              data-search-text="<?php echo strtolower(htmlspecialchars($log['content'] . ' ' . $log['goal_title'])); ?>"
              onclick="openEntryDetail(<?php echo htmlspecialchars(json_encode($log)); ?>)">

              <div class="tl-body">
                <small><?php echo date('M d, Y', strtotime($log['created_at'])); ?> —
                  <strong><?php echo htmlspecialchars($log['goal_title'] ?: 'General'); ?></strong></small>
                <p><?php echo nl2br(htmlspecialchars($log['content'])); ?></p>
                <?php if (!empty($log['image'])): ?>
                  <div style="margin-top:8px"><img src="<?php echo htmlspecialchars($log['image']); ?>" alt=""
                      style="max-width:160px;border-radius:8px"></div>
                <?php endif; ?>
              </div>
              <div style="margin-left:auto;text-align:right">
                <div style="font-weight:600;color:#6b5bff"><?php echo intval($log['progress_update']); ?>%</div>
                <div style="color:var(--muted);font-size:12px"><?php echo htmlspecialchars($log['mood']); ?></div>
              </div>

            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </section>

  <aside class="right-col">
    <div class="profile-card">
      <div class="avatar-wrapper">
        <?php
        // Logic hiển thị ảnh: Nếu có trong DB thì hiện, không thì dùng UI Avatars
        $avatarSrc = !empty($profile['avatar']) ? $profile['avatar'] :
          'https://ui-avatars.com/api/?name=' . urlencode($profile['username']) . '&background=C6A7FF&color=fff&size=128&rounded=true';
        ?>

        <img src="<?php echo $avatarSrc; ?>" alt="Avatar" class="profile-avatar-img" id="profileAvatarDisplay">

        <label for="avatarUploadInput" class="btn-edit-avatar" title="Change Avatar">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20h9"></path>
            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
          </svg>
        </label>

        <input type="file" id="avatarUploadInput" accept="image/*" style="display: none;" onchange="uploadAvatar(this)">
      </div>
      <div class="username"><?php echo htmlspecialchars($profile['username']); ?></div>
      <div class="meta"><?php echo htmlspecialchars($profile['email'] ?? ''); ?></div>

      <!-- Quick actions -->
      <div class="side-box" style="margin-top:14px">
        <h4>Your stats</h4>
        <div class="small-stat">
          <div class="stat"><strong><?php echo count($goals); ?></strong> goals</div>
          <div class="stat"><strong><?php echo count($logs); ?></strong> entries</div>
        </div>
      </div>
    </div>
    <div class="side-box"
      style="margin-top: 20px; background: #fff; padding: 20px; border-radius: 16px; box-shadow: var(--shadow);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
        <h4 style="margin: 0; color: #3b2b7a; font-family: 'Playfair Display';">Weekly Focus</h4>
        <span
          style="font-size: 12px; background: #f3e8ff; color: #6b5bff; padding: 2px 8px; border-radius: 10px; font-weight: 600;">
          <?php echo isset($activityStats) ? array_sum($activityStats) : 0; ?> entries
        </span>
      </div>

      <div class="bar-chart-container">
        <?php
        // Logic tạo 7 cột cho 7 ngày gần nhất
        $today = new DateTime();
        // Tìm số lượng bài nhiều nhất để làm mốc (Max Height)
        $max_count = !empty($activityStats) ? max($activityStats) : 1;
        if ($max_count < 3)
          $max_count = 3; // Giới hạn min để cột không quá thấp
        
        // Vòng lặp 7 ngày ngược từ hôm nay về quá khứ
        for ($i = 6; $i >= 0; $i--) {
          $dateObj = (clone $today)->modify("-$i days");
          $dateStr = $dateObj->format('Y-m-d');
          // Lấy tên thứ (Mon, Tue...) hoặc lấy chữ cái đầu
          $dayLabel = $dateObj->format('D');
          $isToday = ($i === 0); // Kiểm tra xem có phải hôm nay không để tô màu khác
        
          $count = $activityStats[$dateStr] ?? 0;

          // Tính chiều cao % (tối đa 100px)
          $heightPercent = ($count / $max_count) * 100;
          // Nếu có bài viết thì ít nhất cao 10% cho đẹp, nếu 0 thì để 4% làm đế
          $displayHeight = ($count > 0) ? max($heightPercent, 15) : 4;
          ?>
          <div class="bar-group" title="<?php echo "$dateStr: $count entries"; ?>">
            <div class="bar <?php echo $isToday ? 'today' : ''; ?> <?php echo $count > 0 ? 'has-data' : ''; ?>"
              style="height: <?php echo $displayHeight; ?>%;">

              <?php if ($count > 0): ?>
                <span class="bar-tooltip"><?php echo $count; ?></span>
              <?php endif; ?>
            </div>

            <span class="day-label" style="<?php echo $isToday ? 'color:#6b5bff;font-weight:700;' : ''; ?>">
              <?php echo substr($dayLabel, 0, 1); ?>
            </span>
          </div>
        <?php } ?>
      </div>

      <p style="text-align:center; font-size:11px; color:#999; margin-top:15px; margin-bottom:0;">
        Your consistency last 7 days
      </p>
    </div>
  </aside>
  <!-- Add Goal Modal -->
  <div id="goalModal" class="modal hidden">
    <div class="modal-backdrop" onclick="closeModal()"></div>

    <div class="modal-box">
      <h3 style="margin-top:0; color:#3b2b7a;">New Goal 🎯</h3>

      <label style="display:block; margin-bottom:8px; font-weight:500;">Goal Title</label>
      <input type="text" id="goalTitle" placeholder="I want to..."
        style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; margin-bottom:15px;">

      <label style="display:block; margin-bottom:8px; font-weight:500;">Topic</label>
      <div class="topic-select-wrapper" style="position:relative;">

        <input type="text" id="goalTopicName" list="topicSuggestions" placeholder="Select or type new topic..."
          style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">

        <datalist id="topicSuggestions">
          <?php if (!empty($topics)): ?>
            <?php foreach ($topics as $t): ?>
              <option value="<?php echo htmlspecialchars($t['name']); ?>"></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </datalist>

      </div>

      <div class="modal-actions" style="margin-top:25px; display:flex; justify-content:flex-end; gap:10px;">
        <button class="btn-cancel" onclick="closeModal()"
          style="padding:8px 16px; background:#f0f0f0; border:none; border-radius:6px; cursor:pointer;">Cancel</button>
        <button class="btn-save" onclick="saveGoal()"
          style="padding:8px 20px; background:#6b5bff; color:white; border:none; border-radius:6px; font-weight:600; cursor:pointer;">Create
          Goal</button>
      </div>
    </div>
  </div>
  <div id="goalDetailsModal" class="modal hidden" style="z-index: 1050;">
    <div class="modal-backdrop" onclick="closeGoalDetails()"></div>

    <div class="modal-box modal-large modal-expandable modern-modal" id="goalModalBox">

      <div class="modal-left-panel">

        <div class="goal-header-hero" id="goalHeaderHero">
          <button class="btn-close-white" onclick="closeGoalDetails()">&times;</button>

          <div class="hero-content">
            <div class="hero-info">
              <h2 id="detailGoalTitle">Read 5 Books</h2>
              <div class="hero-meta">
                <span id="detailGoalDate">Created at: Nov 23, 2023</span>
                <span id="detailGoalCount">0 entities</span>
              </div>
            </div>

            <div class="hero-progress">
              <svg viewBox="0 0 36 36" class="circular-chart">
                <path class="circle-bg"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="circle" id="heroProgressPath" stroke-dasharray="0, 100"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <text x="18" y="20.35" class="percentage" id="heroProgressText">0%</text>
              </svg>
              <span class="label">Progress</span>
            </div>
          </div>
        </div>

        <div class="modal-scroll-body timeline-container-styled" id="goalLogsContainer">
          <div class="loading-spinner">Loading journey...</div>
        </div>

        <div class="goal-footer-actions">
          <button class="btn-delete-styled" onclick="deleteCurrentGoal()">
            <i class="ph ph-trash"></i> Delete Goal
          </button>
          <button class="btn-add-styled" onclick="expandAddJourneyPanel()">
            <i class="ph ph-plus"></i> Add New Entry
          </button>
        </div>
      </div>

      <div class="modal-right-panel">
        <div class="panel-header">
          <h3>New Entry 📝</h3>
        </div>
        <div class="panel-body">
          <form id="addJourneyForm">
            <input type="hidden" name="goal_id" id="hiddenGoalId" value="">

            <div class="form-group">
              <label class="form-label">Title</label>
              <input type="text" name="journey_title" class="form-input" placeholder="E.g. Finished chapter 1...">
            </div>

            <div class="form-group">
              <label class="form-label">Moods</label>
              <div class="mood-selection-grid">
                <label class="mood-item"><input type="checkbox" name="mood[]" value="Happy"><span class="mood-badge">😊
                    Happy</span></label>
                <label class="mood-item"><input type="checkbox" name="mood[]" value="Excited"><span
                    class="mood-badge">🤩 Excited</span></label>
                <label class="mood-item"><input type="checkbox" name="mood[]" value="Proud"><span class="mood-badge">😎
                    Proud</span></label>
                <label class="mood-item"><input type="checkbox" name="mood[]" value="Tired"><span class="mood-badge">😴
                    Tired</span></label>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Content</label>
              <textarea name="content" rows="3" class="form-input" placeholder="Tell me more..."></textarea>
            </div>

            <div class="form-group">
              <div class="progress-label-group">
                <label class="form-label">Progress update</label>
                <span id="sliderValue" style="font-weight:bold; color:#6b5bff">0%</span>
              </div>
              <input type="range" id="progressSlider" name="progress" min="0" max="100" value="0" class="slider-input"
                oninput="document.getElementById('sliderValue').innerText = this.value + '%'">
            </div>

            <div class="form-group">
              <label class="form-label">Image</label>
              <input type="file" name="image" class="form-input file-input">
            </div>

            <div class="form-actions-right">
              <button type="button" class="btn-cancel-panel" onclick="collapseAddJourneyPanel()">Cancel</button>
              <button type="submit" class="btn-save-panel">Save Entry</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
  <div id="entryDetailModal" class="modal hidden" style="z-index: 1100;">
    <div class="modal-backdrop" onclick="closeEntryDetail()"></div>

    <div class="modal-box modal-entry-detail">

      <button class="btn-close-absolute" onclick="closeEntryDetail()">&times;</button>

      <div class="entry-detail-layout">

        <div class="entry-left-media" id="entryImageArea">
          <img id="detailEntryImg" src="" alt="Journal Image" class="entry-img-full">
          <div id="noImagePlaceholder" class="no-image-box">
            <span style="font-size: 40px;">📔</span>
            <p>No photo for this memory</p>
          </div>
        </div>

        <div class="entry-right-content">

          <div id="viewModeContent">
            <div class="entry-header">
              <span class="entry-date" id="detailEntryDate">Nov 28, 2025</span>
              <div class="entry-mood-badge" id="detailEntryMood">😊 Happy</div>
            </div>

            <div class="entry-scroll-text">
              <p id="detailEntryText" class="entry-text-content">Nội dung nhật ký...</p>
            </div>

            <div class="entry-footer-info">
              <div class="progress-mini-info">
                <span>Progress at this moment:</span>
                <strong id="detailEntryProgress">60%</strong>
              </div>
            </div>

            <div class="entry-actions">
              <button class="btn-action-icon delete" onclick="deleteEntryCurrent()" title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
              <button class="btn-action-icon edit" onclick="toggleEditMode(true)" title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
              </button>
            </div>
          </div>

          <div id="editModeContent" class="hidden">
            <h3 style="margin:0 0 15px 0; color:#4c3b9b;">Edit Entry ✏️</h3>
            <form id="editEntryForm">
              <input type="hidden" id="editEntryId" name="log_id">
              <input type="hidden" id="editGoalId" name="goal_id">
              <div class="form-group">
                <label>Content</label>
                <textarea name="content" id="editContentInput" rows="5" class="form-input"></textarea>
              </div>

              <div class="form-group">
                <label>Mood</label>
                <input type="text" name="mood" id="editMoodInput" class="form-input" placeholder="e.g. Happy, Tired...">
              </div>

              <div class="form-group">
                <label>Progress (%)</label>
                <input type="number" name="progress" id="editProgressInput" class="form-input" min="0" max="100">
              </div>

              <div class="edit-actions">
                <button type="button" class="btn-cancel-small" onclick="toggleEditMode(false)">Cancel</button>
                <button type="submit" class="btn-save-small">Save Changes</button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </div>
  <div id="letterNotificationModal" class="modal hidden" style="z-index: 2000;">
    <div class="modal-backdrop"></div>
    <div class="modal-box letter-popup-box">
      <div class="letter-icon-animation">
        <i class="ph-duotone ph-envelope-open"></i>
      </div>
      <h3 class="letter-title">A Message from the Past!</h3>
      <p class="letter-desc">
        You wrote a letter to your future self when you were <span id="notiMood" class="highlight-mood">Happy</span>!
        Would you like to open it now?
      </p>

      <div class="letter-actions">
        <button class="btn-letter-secondary" onclick="closeLetterNotification()">Maybe Later</button>
        <button class="btn-letter-primary" onclick="openFullLetter()">Open Letter</button>
      </div>
    </div>
  </div>

  <div id="letterContentModal" class="modal hidden" style="z-index: 2100;">
    <div class="modal-backdrop" onclick="closeFullLetter()"></div>
    <div class="modal-box letter-content-box">
      <div class="letter-header">
        <h3>A Message from Your Past Self</h3>
        <div class="letter-meta">
          <span>Mood: <strong id="letterMoodDisplay">Happy</strong></span>
          <span>Written: <span id="letterDateDisplay">Nov 29, 2023</span></span>
        </div>
      </div>

      <div class="letter-body-scroll">
        <p id="letterMessageContent">
          Dear Future Self,...
        </p>
      </div>

      <div class="letter-footer">
        <i class="ph-duotone ph-paper-plane-tilt deco-icon"></i>
        <button class="btn-letter-close" onclick="closeFullLetter()">Close Letter</button>
      </div>
    </div>
  </div>
</main>