<script src="https://unpkg.com/phosphor-icons"></script>

<main class="container">

  <section class="card">
    <h2 class="capsule-title">
      <i class="ph-fill ph-envelope-simple-open"></i> Craft Your Time Capsule
    </h2>

    <form id="capsuleForm">
      <input type="hidden" name="moodTag" id="moodTagInput">
      <div class="form-group">
        <input type="email" name="email" placeholder="To: Future Self (Email)">
      </div>

      <div class="form-group">
        <input type="text" name="subject" placeholder="Dear Future Self...">
      </div>

      <div class="form-group">
        <textarea name="message"
          placeholder="Write a gentle message to your future self...&#10;How are you feeling today? What do you hope for?"></textarea>
      </div>

      <div class="form-group date-row">
        <div style="flex: 2;">
          <label style="font-size:12px; color:#888; font-weight:bold; margin-left:5px;">OPEN DATE</label>
          <input type="date" name="openDate" required>
        </div>
        <div style="flex: 1;">
          <label style="font-size:12px; color:#888; font-weight:bold; margin-left:5px;">TIME</label>
          <input type="time" name="openTime" value="09:00">
        </div>
      </div>

      <button type="button" class="seal-btn" onclick="sealCapsule()">
        Seal & Send <i class="ph-bold ph-paper-plane-right"></i>
      </button>

    </form>
  </section>


  <aside class="card">
    <h2 class="moods-title">Echoes of Moods Past</h2>

    <div class="mood-grid">
      <div class="mood-item" data-mood="happy" onclick="selectMood(this)">
        <div class="mood-circle"><i class="ph-fill ph-smiley"></i></div>
        <span class="mood-name">Happy</span>
      </div>

      <div class="mood-item" data-mood="calm" onclick="selectMood(this)">
        <div class="mood-circle"><i class="ph-fill ph-coffee"></i></div>
        <span class="mood-name">Calm</span>
      </div>

      <div class="mood-item" data-mood="motivated" onclick="selectMood(this)">
        <div class="mood-circle"><i class="ph-fill ph-lightning"></i></div>
        <span class="mood-name">Motivated</span>
      </div>

      <div class="mood-item" data-mood="sad" onclick="selectMood(this)">
        <div class="mood-circle"><i class="ph-fill ph-cloud-rain"></i></div>
        <span class="mood-name">Sad</span>
      </div>

      <div class="mood-item" data-mood="excited" onclick="selectMood(this)">
        <div class="mood-circle"><i class="ph-fill ph-star"></i></div>
        <span class="mood-name">Excited</span>
      </div>

      <div class="mood-item" data-mood="anxious" onclick="selectMood(this)">
        <div class="mood-circle"><i class="ph-fill ph-waves"></i></div>
        <span class="mood-name">Anxious</span>
      </div>
      <div class="mood-item" data-mood="loved" onclick="selectMood(this)">
        <div class="mood-circle" style="background: #ffe0e9; color: #ff6b81;">
          <i class="ph-fill ph-heart"></i>
        </div>
        <span class="mood-name">Loved</span>
      </div>

      <div class="mood-item" data-mood="creative" onclick="selectMood(this)">
        <div class="mood-circle" style="background: #fff0db; color: #ffae42;">
          <i class="ph-fill ph-paint-brush"></i>
        </div>
        <span class="mood-name">Creative</span>
      </div>

      <div class="mood-item" data-mood="proud" onclick="selectMood(this)">
        <div class="mood-circle" style="background: #fff9c4; color: #fbc02d;">
          <i class="ph-fill ph-trophy"></i>
        </div>
        <span class="mood-name">Proud</span>
      </div>
    </div>

    <div class="mood-footer-text">
      <p>Select a mood to view letters you wrote in that state. <br>Let future you connect with past you.</p>
      <a href="#">Write a New Mood Message</a>
    </div>

  </aside>

</main>
