// assets/js/home.js

function goToJourney() {
    window.location.href = 'journal.php';
}

function scrollToFeatures() {
    document.getElementById('features').scrollIntoView({ behavior: 'smooth' });
}

function goToTool(toolName) {
    // Chuyển đến trang tương ứng
    if (toolName === 'vision') {
        window.location.href = 'vision.php';
    } else if (toolName === 'journal') {
        window.location.href = 'journal.php';
    } else if (toolName === 'future') {
        window.location.href = 'future.php';
    }
}

function goToRegister() {
    window.location.href = 'register.php';
}