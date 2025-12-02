<!-- Link Icon -->
<script src="https://unpkg.com/phosphor-icons"></script>

<main class="home-page">
  <!-- 1. HERO SECTION  -->
  <section class="hero-section">
    <div class="hero-content">
      <div class="hero-text-box">
        <h1 class="hero-title">Visualize your dreams.<br>Track your journey.<br>Send love to the future.</h1>
        <p class="hero-subtitle">
          DreamBoard is a healing space where you can nurture your dreams, record every small step forward, and send
          hopes to your future self.
        </p>
        <div class="hero-actions">
          <button class="btn-primary-lg" onclick="goToJourney()">Start Your Journey</button>
          <button class="btn-outline-lg" onclick="scrollToFeatures()">Learn More</button>
        </div>
      </div>

      <div class="hero-image-box">

        <img src="assets/images/nen.jpg" alt="DreamBoard Illustration" class="hero-img">
      </div>
    </div>
  </section>

  <!-- 2. FEATURE CARDS  -->
  <section class="features-section" id="features">
    <div class="section-heading">
      <h2>All the tools to build the future</h2>
      <p>Simple, gentle, and meaningful.</p>
    </div>

    <div class="cards-grid">
      <!-- Card 1: Vision Board -->
      <div class="feature-card" onclick="goToTool('vision')">
        <div class="card-icon icon-purple">
          <i class="ph-fill ph-image"></i>
        </div>
        <h3>Dream Canvas</h3>
        <p>Freely cut, paste, and drag to create an inspiring vision board.</p>
        <span class="card-link">Design now <i class="ph-bold ph-arrow-right"></i></span>
      </div>

      <!-- Card 2: Journey Log -->
      <div class="feature-card" onclick="goToTool('journal')">
        <div class="card-icon icon-green">
          <i class="ph-fill ph-book-bookmark"></i>
        </div>
        <h3>The Journey Log</h3>
        <p>Record your journal, track your progress (%) and emotions throughout each stage of your journey.</p>
        <span class="card-link">Write log <i class="ph-bold ph-arrow-right"></i></span>
      </div>

      <!-- Card 3: Time Capsule -->
      <div class="feature-card" onclick="goToTool('future')">
        <div class="card-icon icon-pink">
          <i class="ph-fill ph-envelope-simple-open"></i>
        </div>
        <h3>Time Capsule</h3>
        <p>Send secret letters to your future self. Open them when you need motivation the most.</p>
        <span class="card-link">Seal a letter <i class="ph-bold ph-arrow-right"></i></span>
      </div>
    </div>
  </section>

  <!-- 3. HOW IT WORKS (Thơ thơ xinh xinh) -->
  <section class="how-it-works-section">
    <div class="how-container">
      <h2 class="script-font">How it works...</h2>

      <div class="steps-row">
        <div class="step-item">
          <span class="step-num">01</span>
          <h4>Dream</h4>
          <p>Define what you truly want.</p>
        </div>
        <div class="step-line"></div>
        <div class="step-item">
          <span class="step-num">02</span>
          <h4>Plan</h4>
          <p>Break it down into small steps.</p>
        </div>
        <div class="step-line"></div>
        <div class="step-item">
          <span class="step-num">03</span>
          <h4>Achieve</h4>
          <p>Track progress & celebrate.</p>
        </div>
      </div>

      <div class="final-cta">
        <p>Ready to start?</p>
        <button class="btn-primary-lg" onclick="goToRegister()">Create your account</button>
      </div>
    </div>
  </section>

</main>