/*
 * Simpan — hiasan & animasi UI (dipakai bersama di login.html, app.html, admin.html)
 * Naikkan APP_VERSION di sini kalau mau versi ditampilkan otomatis lewat SimpanUI.setVersion().
 */
window.SimpanUI = (function(){
  if ('serviceWorker' in navigator && location.protocol !== 'file:') {
    window.addEventListener('load', function(){
      navigator.serviceWorker.register('./sw.js').catch(function(){});
    });
  }

  // ---------- Tema (terang/gelap) ----------
  function initThemeToggle(btnId, iconId){
    var btn = document.getElementById(btnId);
    var icon = document.getElementById(iconId);
    if (!btn || !icon) return;

    function applyIcon(){
      var t = document.documentElement.getAttribute('data-theme');
      var isDark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
      icon.innerHTML = isDark
        ? '<path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/>'
        : '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>';
    }
    btn.addEventListener('click', function(){
      var cur = document.documentElement.getAttribute('data-theme');
      var sysDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      var next = (cur === 'dark' || (!cur && sysDark)) ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      try { localStorage.setItem('simpan_theme', next); } catch(e){}
      applyIcon();
    });
    try {
      var saved = localStorage.getItem('simpan_theme');
      if (saved) document.documentElement.setAttribute('data-theme', saved);
    } catch(e){}
    applyIcon();
  }

  // ---------- Pemasangan aplikasi web ----------
  function initInstallButton(){
    var buttons = document.querySelectorAll('[data-install-app]');
    if (!buttons.length) return;
    var installPrompt = null;
    var isInstalled = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;

    if (isInstalled) buttons.forEach(function(button){ button.hidden = true; });
    window.addEventListener('beforeinstallprompt', function(event){
      event.preventDefault();
      installPrompt = event;
    });
    window.addEventListener('appinstalled', function(){
      installPrompt = null;
      buttons.forEach(function(button){ button.hidden = true; });
    });

    buttons.forEach(function(button){
      button.addEventListener('click', async function(){
        if (isInstalled) {
          window.alert('IT Noted sudah terpasang di perangkat ini.');
          return;
        }
        if (!window.isSecureContext) {
          window.alert('IT Noted belum bisa dipasang karena situs dibuka melalui HTTP. Buka alamat HTTPS setelah SSL aktif, lalu muat ulang halaman.');
          return;
        }
        if (installPrompt) {
          installPrompt.prompt();
          var choice = await installPrompt.userChoice;
          installPrompt = null;
          if (choice && choice.outcome === 'accepted') buttons.forEach(function(item){ item.hidden = true; });
          return;
        }
        var isAppleMobile = /iPhone|iPad|iPod/i.test(navigator.userAgent) || (/MacIntel/i.test(navigator.platform) && navigator.maxTouchPoints > 1);
        var isAndroid = /Android/i.test(navigator.userAgent);
        var message = isAppleMobile
          ? 'Untuk memasang IT Noted: tekan Bagikan di Safari, lalu pilih “Tambahkan ke Layar Utama”.'
          : isAndroid
            ? 'Di Android, buka menu ⋮ browser lalu pilih “Install app” atau “Tambahkan ke layar utama”. Pastikan situs dibuka melalui HTTPS.'
            : 'Di desktop, buka menu browser lalu pilih “Install IT Noted” atau “Install app”. Pastikan situs dibuka melalui HTTPS.';
        window.alert(message);
      });
    });
  }

  // ---------- Nomor versi ----------
  function setVersion(elId, version){
    var el = document.getElementById(elId);
    if (el) el.textContent = 'IT Noted v' + version;
  }

  // ---------- Toast notifikasi ----------
  var toastStack = null;
  function ensureToastStack(){
    if (toastStack) return toastStack;
    toastStack = document.getElementById('toastStack');
    if (!toastStack) {
      toastStack = document.createElement('div');
      toastStack.id = 'toastStack';
      document.body.appendChild(toastStack);
    }
    return toastStack;
  }
  function toast(msg, type){
    var stack = ensureToastStack();
    var el = document.createElement('div');
    el.className = 'sui-toast' + (type === 'error' ? ' error' : '');
    el.innerHTML = '<span class="sui-toast-dot"></span><span></span>';
    el.querySelector('span:last-child').textContent = msg;
    stack.appendChild(el);
    setTimeout(function(){
      el.classList.add('leaving');
      setTimeout(function(){ el.remove(); }, 220);
    }, 3200);
  }

  // ---------- Efek ripple di semua tombol ----------
  function initRipple(){
    document.addEventListener('click', function(e){
      var btn = e.target.closest('button, .btn, a.nav-item');
      if (!btn) return;
      var rect = btn.getBoundingClientRect();
      var ripple = document.createElement('span');
      var size = Math.max(rect.width, rect.height) * 1.4;
      ripple.className = 'sui-ripple';
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
      ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
      var prevPosition = getComputedStyle(btn).position;
      if (prevPosition === 'static') btn.style.position = 'relative';
      btn.style.overflow = btn.style.overflow || 'hidden';
      btn.appendChild(ripple);
      setTimeout(function(){ ripple.remove(); }, 500);
    });
  }

  // ---------- Tilt 3D halus di kartu (delegasi, tahan re-render) ----------
  function initTilt(containerSelector, itemSelector){
    document.querySelectorAll(containerSelector).forEach(function(container){
      container.addEventListener('mousemove', function(e){
        var item = e.target.closest(itemSelector);
        if (!item || !container.contains(item)) return;
        var r = item.getBoundingClientRect();
        var px = (e.clientX - r.left) / r.width;   // 0..1
        var py = (e.clientY - r.top) / r.height;   // 0..1
        var rx = (0.5 - py) * 6;  // derajat
        var ry = (px - 0.5) * 6;
        item.style.transform = 'perspective(600px) rotateX(' + rx.toFixed(2) + 'deg) rotateY(' + ry.toFixed(2) + 'deg) translateY(-2px)';
      });
      container.addEventListener('mouseout', function(e){
        var item = e.target.closest(itemSelector);
        if (item) item.style.transform = '';
      }, true);
    });
  }

  // ---------- Confetti kecil (perayaan simpan) ----------
  function confettiBurst(x, y){
    var colors = ['#1F6F63', '#4FA895', '#C98A2C', '#E0A94F', '#B5482F'];
    for (var i = 0; i < 14; i++) {
      var el = document.createElement('span');
      el.className = 'sui-confetti';
      el.style.background = colors[i % colors.length];
      el.style.left = x + 'px';
      el.style.top = y + 'px';
      var angle = Math.random() * Math.PI * 2;
      var dist = 40 + Math.random() * 50;
      el.style.setProperty('--dx', (Math.cos(angle) * dist) + 'px');
      el.style.setProperty('--dy', (Math.sin(angle) * dist - 20) + 'px');
      el.style.setProperty('--rot', (Math.random() * 360) + 'deg');
      document.body.appendChild(el);
      setTimeout(function(node){ return function(){ node.remove(); }; }(el), 700);
    }
  }

  // ---------- Partikel latar (canvas, dipakai di halaman login) ----------
  function initParticles(canvasId){
    var canvas = document.getElementById(canvasId);
    if (!canvas || !canvas.getContext) return;
    var ctx = canvas.getContext('2d');
    var particles = [];
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function resize(){ canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
    function accentColor(){
      return getComputedStyle(document.documentElement).getPropertyValue('--accent').trim() || '#1F6F63';
    }
    function init(){
      var count = Math.min(46, Math.floor((window.innerWidth * window.innerHeight) / 26000));
      particles = [];
      for (var i = 0; i < count; i++) {
        particles.push({
          x: Math.random() * canvas.width, y: Math.random() * canvas.height,
          r: 1 + Math.random() * 2.2,
          vx: (Math.random() - 0.5) * 0.18, vy: (Math.random() - 0.5) * 0.18,
          a: 0.15 + Math.random() * 0.25
        });
      }
    }
    function tick(){
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      var color = accentColor();
      particles.forEach(function(p){
        p.x += p.vx; p.y += p.vy;
        if (p.x < 0) p.x = canvas.width; if (p.x > canvas.width) p.x = 0;
        if (p.y < 0) p.y = canvas.height; if (p.y > canvas.height) p.y = 0;
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = color; ctx.globalAlpha = p.a; ctx.fill();
      });
      ctx.globalAlpha = 1;
      if (!reduceMotion) requestAnimationFrame(tick);
    }
    resize(); init();
    window.addEventListener('resize', function(){ resize(); init(); });
    if (!reduceMotion) requestAnimationFrame(tick); else tick();
  }

  // ---------- Angka melompat (count-up) ----------
  function animateCount(el, toValue){
    if (!el) return;
    var fromValue = parseInt(el.textContent, 10); if (isNaN(fromValue)) fromValue = 0;
    if (fromValue === toValue) return;
    var start = null, duration = 300;
    function step(ts){
      if (!start) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      el.textContent = Math.round(fromValue + (toValue - fromValue) * progress);
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  // ---------- Logout otomatis karena tidak aktif ----------
  function initInactivityLogout(opts){
    opts = opts || {};
    var timeoutMs = (opts.minutes || 30) * 60 * 1000;
    var pingUrl = opts.pingUrl || 'ping.php';
    var logoutUrl = opts.logoutUrl || 'logout.php';
    var onWarning = opts.onWarning || function(){};
    var warnBeforeMs = (opts.warnBeforeMinutes || 2) * 60 * 1000;
    var sessionStarted = Date.now();
    var lastActivity = Date.now();
    var lastPing = 0;
    var warned = false;
    var loggedOut = false;

    function markActive(){
      if (loggedOut) return;
      lastActivity = Date.now();
      if (Date.now() - sessionStarted < timeoutMs - warnBeforeMs) warned = false;
      if (Date.now() - lastPing > 60000) {
        lastPing = Date.now();
        fetch(pingUrl, { method: 'POST', credentials: 'same-origin' }).catch(function(){});
      }
    }
    ['mousemove','keydown','mousedown','click','scroll','touchstart'].forEach(function(evt){
      document.addEventListener(evt, markActive, { passive: true });
    });

    setInterval(function(){
      if (loggedOut) return;
      var now = Date.now();
      var idle = now - lastActivity;
      var elapsed = Math.max(idle, now - sessionStarted);
      if (elapsed >= timeoutMs) {
        loggedOut = true;
        fetch(logoutUrl, { method: 'POST', credentials: 'same-origin' }).catch(function(){}).then(function(){
          window.location.href = 'login.html?timeout=1';
        });
      } else if (!warned && elapsed >= timeoutMs - warnBeforeMs) {
        warned = true;
        onWarning(Math.round((timeoutMs - elapsed) / 60000));
      }
    }, 15000);
  }

  return {
    initThemeToggle: initThemeToggle,
    initInstallButton: initInstallButton,
    setVersion: setVersion,
    toast: toast,
    initRipple: initRipple,
    initTilt: initTilt,
    confettiBurst: confettiBurst,
    initParticles: initParticles,
    animateCount: animateCount,
    initInactivityLogout: initInactivityLogout
  };
})();
