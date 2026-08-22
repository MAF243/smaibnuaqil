<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>🎉 Pendaftaran Berhasil!</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    .fade-in {
      animation: fadeIn 1.5s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body class="bg-success text-white d-flex justify-content-center align-items-center vh-100">

<div class="text-center fade-in">
 <h1>🎉 Pendaftaran Berhasil!</h1>
 <p class="lead">Terima kasih telah mendaftar.<br>Kami akan segera memverifikasi data Anda.</p>
 <a href="dashboard_register.php" class="btn btn-light mt-4">Kembali ke Beranda</a>
 <p class="mt-3 small">Anda akan diarahkan otomatis dalam <span id="countdown">5</span> detik...</p>
 <a href="https://wa.me/" class="btn btn-success mt-2" target="_blank">Hubungi Kami via WhatsApp</a>
</div>

<script>
  setTimeout(function() {
    window.location.href = "dashboard_register.php";
  }, 5000);

  let countdown = 30;
  const countdownElement = document.getElementById('countdown');
  const countdownInterval = setInterval(function() {
    countdown--;
    countdownElement.textContent = countdown;
    if (countdown <= 0) {
      clearInterval(countdownInterval);
    }
  }, 1000);
</script>

<!-- Confetti JS (pesta kecil) -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>

<script>
// Fade-in sudah di CSS

// Confetti animation
const duration = 3 * 1000; // 3 detik
const end = Date.now() + duration;

(function frame() {
  confetti({
    particleCount: 3,
    angle: 60,
    spread: 55,
    origin: { x: 0 }
  });
  confetti({
    particleCount: 3,
    angle: 120,
    spread: 55,
    origin: { x: 1 }
  });

  if (Date.now() < end) {
    requestAnimationFrame(frame);
  }
})();

// Countdown redirect
let seconds = 5;
const countdownEl = document.getElementById('countdown');
const timer = setInterval(() => {
  seconds--;
  countdownEl.textContent = seconds;
  if (seconds <= 0) {
    clearInterval(timer);
    window.location.href = 'dashboard_register.php';
  }
}, 1000);
</script>

</body>
</html>
