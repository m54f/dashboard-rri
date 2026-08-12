/**
 * onboarding.js — Tour Engine dengan Tipseen Spec (dashboard-rri)
 *
 * API publik:
 *   startTour(steps, tourKey, force)  — mulai tour, force=true untuk restart
 *   startTourIfNew(steps, tourKey)    — mulai hanya jika belum pernah selesai
 *   endTour()                         — tutup tour dari luar
 *
 * Format step:
 *   { target: 'css-selector', title: 'Judul', body: 'Pesan' }
 */

(function (global) {
  'use strict';

  // ─── CSS (di-inject sekali) ───────────────────────────────────────────────
  var CSS = [
    /* 4 panel backdrop — mengelilingi elemen target, area tengah kosong */
    '.ob-backdrop-wrap{pointer-events:none;z-index:99980;}',
    '.ob-bd{position:fixed;background:rgba(0,0,0,0.52);z-index:99980;pointer-events:none;transition:all .25s cubic-bezier(.4,0,.2,1);}',

    /* Highlight ring di sekitar elemen target */
    '.ob-highlight{position:fixed;border-radius:8px;outline:3px solid rgba(255,255,255,0.6);z-index:99981;pointer-events:none;transition:top .3s cubic-bezier(.4,0,.2,1),left .3s cubic-bezier(.4,0,.2,1),width .3s cubic-bezier(.4,0,.2,1),height .3s cubic-bezier(.4,0,.2,1);}',

    /* Tipseen tooltip — identik dengan komponen di footer.php */
    '.ob-tipseen{position:fixed;width:288px;background:#323338;box-shadow:0px 6px 20px rgba(0,0,0,.25);border-radius:8px;z-index:99982;opacity:0;transform:scale(.88);transition:opacity .22s cubic-bezier(.16,1,.3,1),transform .22s cubic-bezier(.16,1,.3,1);}',
    '.ob-tipseen.ob-visible{opacity:1;transform:scale(1);}',

    /* Arrow pointer */
    '.ob-arrow{position:absolute;width:0;height:0;pointer-events:none;}',
    '.ob-arrow.ob-arrow-down{border-left:8px solid transparent;border-right:8px solid transparent;border-top:8px solid #323338;bottom:-8px;left:50%;transform:translateX(-50%);}',
    '.ob-arrow.ob-arrow-up{border-left:8px solid transparent;border-right:8px solid transparent;border-bottom:8px solid #323338;top:-8px;left:50%;transform:translateX(-50%);}',
    '.ob-arrow.ob-arrow-right{border-top:8px solid transparent;border-bottom:8px solid transparent;border-left:8px solid #323338;right:-8px;top:50%;transform:translateY(-50%);}',
    '.ob-arrow.ob-arrow-left{border-top:8px solid transparent;border-bottom:8px solid transparent;border-right:8px solid #323338;left:-8px;top:50%;transform:translateY(-50%);}',

    /* Tombol close */
    '.ob-close{position:absolute;top:8px;right:8px;display:flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:4px;border:none;background:transparent;color:#676879;cursor:pointer;padding:0;transition:color .15s;z-index:1;}',
    '.ob-close:hover{color:#fff;}',

    /* Konten */
    '.ob-content{display:flex;flex-direction:column;align-items:flex-start;padding:0 20px 20px;width:100%;box-sizing:border-box;}',
    '.ob-text{display:flex;flex-direction:column;padding:16px 0 16px;gap:4px;align-self:stretch;}',
    '.ob-title{font-family:"Figtree",sans-serif;font-weight:700;font-size:16px;line-height:22px;color:#fff;padding-right:20px;}',
    '.ob-body{font-family:"Figtree",sans-serif;font-weight:400;font-size:14px;line-height:20px;color:#fff;}',

    /* Footer: stepper + tombol */
    '.ob-footer{display:flex;flex-direction:row;justify-content:space-between;align-items:center;gap:8px;align-self:stretch;}',
    '.ob-stepper{display:flex;flex-direction:row;align-items:center;gap:6px;flex:1;}',
    '.ob-dot{border-radius:50%;background:#676879;transition:all .2s;flex-shrink:0;}',
    '.ob-dot.ob-dot-active{background:#fff;}',
    '.ob-actions{display:flex;gap:6px;align-items:center;}',

    /* Tombol — sama persis dengan tipseen di footer.php */
    '.ob-btn-back{display:flex;align-items:center;justify-content:center;padding:0 8px;height:32px;border-radius:4px;border:none;background:transparent;color:#fff;font-family:"Figtree",inherit;font-size:14px;line-height:20px;cursor:pointer;transition:background .15s;}',
    '.ob-btn-back:hover{background:rgba(255,255,255,.12);}',
    '.ob-btn-next{display:flex;align-items:center;justify-content:center;padding:0 8px;height:32px;background:#fff;border-radius:4px;border:none;font-family:"Figtree",inherit;font-size:14px;line-height:20px;color:#323338;cursor:pointer;transition:opacity .15s;}',
    '.ob-btn-next:hover{opacity:.88;}',
  ].join('');

  var _cssInjected = false;
  function injectCss() {
    if (_cssInjected) return;
    var style = document.createElement('style');
    style.id = 'ob-styles';
    style.textContent = CSS;
    document.head.appendChild(style);
    _cssInjected = true;
  }

  // ─── State ────────────────────────────────────────────────────────────────
  var _steps       = [];
  var _tourKey     = '';
  var _current     = 0;
  var _elBackdrop  = null;
  var _elHighlight = null;
  var _elTipseen   = null;
  var _resizeTimer = null;
  var _renderTimer = null;  // timer pending _doRender — di-cancel saat tour ditutup
  var _active      = false;

  // ─── Helpers ──────────────────────────────────────────────────────────────
  function findTarget(selector) {
    if (!selector) return null;
    try { return document.querySelector(selector); } catch (e) { return null; }
  }

  /** Kembalikan step valid berikutnya mulai dari index, atau -1 jika tidak ada */
  function nextValidIndex(from) {
    for (var i = from; i < _steps.length; i++) {
      if (findTarget(_steps[i].target)) return i;
    }
    return -1;
  }

  /** Kembalikan step valid sebelumnya mulai dari index, atau -1 jika tidak ada */
  function prevValidIndex(from) {
    for (var i = from; i >= 0; i--) {
      if (findTarget(_steps[i].target)) return i;
    }
    return -1;
  }

  function buildDom() {
    // 4 panel backdrop mengelilingi elemen target — area tengah kosong
    _elBackdrop = document.createElement('div');
    _elBackdrop.className = 'ob-backdrop-wrap';
    _elBackdrop.innerHTML =
      '<div class="ob-bd" id="ob-bd-top"></div>' +
      '<div class="ob-bd" id="ob-bd-bottom"></div>' +
      '<div class="ob-bd" id="ob-bd-left"></div>' +
      '<div class="ob-bd" id="ob-bd-right"></div>';
    document.body.appendChild(_elBackdrop);

    // Highlight ring
    _elHighlight = document.createElement('div');
    _elHighlight.className = 'ob-highlight';
    document.body.appendChild(_elHighlight);

    // Tipseen tooltip
    _elTipseen = document.createElement('div');
    _elTipseen.className = 'ob-tipseen';
    _elTipseen.setAttribute('role', 'dialog');
    _elTipseen.setAttribute('aria-modal', 'true');
    _elTipseen.innerHTML = [
      '<button class="ob-close" aria-label="Tutup panduan">',
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"',
        ' stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">',
        '<path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>',
      '</button>',
      '<div class="ob-content">',
        '<div class="ob-text">',
          '<div class="ob-title" id="ob-title"></div>',
          '<div class="ob-body"  id="ob-body"></div>',
        '</div>',
        '<div class="ob-footer">',
          '<div class="ob-stepper" id="ob-stepper"></div>',
          '<div class="ob-actions">',
            '<button class="ob-btn-back" id="ob-btn-back">Kembali</button>',
            '<button class="ob-btn-next" id="ob-btn-next">Lanjut</button>',
          '</div>',
        '</div>',
      '</div>',
      '<div class="ob-arrow" id="ob-arrow"></div>',
    ].join('');
    document.body.appendChild(_elTipseen);

    // Events
    _elTipseen.querySelector('.ob-close').addEventListener('click', function () { endTour(true); });
    document.getElementById('ob-btn-back').addEventListener('click', prevStep);
    document.getElementById('ob-btn-next').addEventListener('click', nextStep);
    document.addEventListener('keydown', onKey);
    window.addEventListener('resize', onResize);
  }

  function destroyDom() {
    clearTimeout(_renderTimer);
    clearTimeout(_resizeTimer);
    if (_elBackdrop)  { _elBackdrop.remove();  _elBackdrop  = null; }
    if (_elHighlight) { _elHighlight.remove(); _elHighlight = null; }
    if (_elTipseen)   { _elTipseen.remove();   _elTipseen   = null; }
    document.removeEventListener('keydown', onKey);
    window.removeEventListener('resize', onResize);
  }

  // ─── Render Step ─────────────────────────────────────────────────────────
  function isInViewport(el) {
    var r = el.getBoundingClientRect();
    return r.top >= 0 && r.bottom <= window.innerHeight &&
           r.left >= 0 && r.right <= window.innerWidth;
  }

  function renderStep(index) {
    var step   = _steps[index];
    var target = findTarget(step.target);

    clearTimeout(_renderTimer); // batalkan render sebelumnya yang masih pending

    if (target && !isInViewport(target)) {
      target.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
      _renderTimer = setTimeout(function () {
        if (!_active) return; // tour sudah ditutup saat menunggu scroll
        _doRender(index, target);
      }, 420);
    } else {
      _doRender(index, target);
    }
  }

  function _doRender(index, target) {
    if (!_active) return; // guard: tour mungkin sudah ditutup saat setTimeout pending
    var step = _steps[index];

    // ── Isi teks ──
    document.getElementById('ob-title').textContent = step.title || '';
    document.getElementById('ob-body').textContent  = step.body  || '';

    // ── Stepper dots ──
    var stepper = document.getElementById('ob-stepper');
    stepper.innerHTML = '';
    var validIndexes = [];
    _steps.forEach(function (s, i) { if (findTarget(_steps[i].target)) validIndexes.push(i); });
    var posInValid = validIndexes.indexOf(index);

    var DOT_ACTIVE = 8; var DOT_INACTIVE = 6;
    validIndexes.forEach(function (vi, pos) {
      var dot = document.createElement('span');
      dot.className = 'ob-dot' + (pos === posInValid ? ' ob-dot-active' : '');
      var size = pos === posInValid ? DOT_ACTIVE : DOT_INACTIVE;
      dot.style.cssText = 'width:' + size + 'px;height:' + size + 'px;';
      stepper.appendChild(dot);
    });

    // ── Tombol Back/Next ──
    var btnBack = document.getElementById('ob-btn-back');
    var btnNext = document.getElementById('ob-btn-next');
    var isFirst = prevValidIndex(index - 1) === -1;
    var isLast  = nextValidIndex(index + 1) === -1;
    btnBack.style.display = isFirst ? 'none' : '';
    btnNext.textContent   = isLast ? 'Selesai' : 'Lanjut';

    // ── Posisi highlight + tipseen ──
    if (!target) {
      _elHighlight.style.cssText = 'display:none;';
      _backdropFull();
      _positionCenter();
      return;
    }

    var PAD = 6;
    var rect = target.getBoundingClientRect();

    // Update 4-panel backdrop — area kotak target kosong/terang
    _updateBackdrop(rect, PAD);

    _elHighlight.style.cssText = [
      'display:block;',
      'top:'    + (rect.top    - PAD) + 'px;',
      'left:'   + (rect.left   - PAD) + 'px;',
      'width:'  + (rect.width  + PAD * 2) + 'px;',
      'height:' + (rect.height + PAD * 2) + 'px;',
    ].join('');

    _positionTipseen(rect, PAD);
  }

  function _updateBackdrop(rect, PAD) {
    if (!_elBackdrop) return;
    var x1 = Math.max(0, rect.left   - PAD);
    var y1 = Math.max(0, rect.top    - PAD);
    var x2 = Math.min(window.innerWidth,  rect.right  + PAD);
    var y2 = Math.min(window.innerHeight, rect.bottom + PAD);
    var vw = window.innerWidth;
    var vh = window.innerHeight;

    var top    = document.getElementById('ob-bd-top');
    var bottom = document.getElementById('ob-bd-bottom');
    var left   = document.getElementById('ob-bd-left');
    var right  = document.getElementById('ob-bd-right');

    if (!top) return;
    // Atas: dari top=0 sampai y1
    top.style.cssText    = 'position:fixed;top:0;left:0;width:' + vw + 'px;height:' + y1 + 'px;background:rgba(0,0,0,0.52);z-index:99980;pointer-events:none;';
    // Bawah: dari y2 sampai bawah
    bottom.style.cssText = 'position:fixed;top:' + y2 + 'px;left:0;width:' + vw + 'px;height:' + (vh - y2) + 'px;background:rgba(0,0,0,0.52);z-index:99980;pointer-events:none;';
    // Kiri: dari y1 sampai y2, kiri dari x1
    left.style.cssText   = 'position:fixed;top:' + y1 + 'px;left:0;width:' + x1 + 'px;height:' + (y2 - y1) + 'px;background:rgba(0,0,0,0.52);z-index:99980;pointer-events:none;';
    // Kanan: dari y1 sampai y2, kanan dari x2
    right.style.cssText  = 'position:fixed;top:' + y1 + 'px;left:' + x2 + 'px;width:' + (vw - x2) + 'px;height:' + (y2 - y1) + 'px;background:rgba(0,0,0,0.52);z-index:99980;pointer-events:none;';
  }

  function _backdropFull() {
    // Tutup penuh (tidak ada target)
    if (!_elBackdrop) return;
    var vw = window.innerWidth, vh = window.innerHeight;
    var top    = document.getElementById('ob-bd-top');
    var bottom = document.getElementById('ob-bd-bottom');
    var left   = document.getElementById('ob-bd-left');
    var right  = document.getElementById('ob-bd-right');
    if (!top) return;
    top.style.cssText    = 'position:fixed;top:0;left:0;width:' + vw + 'px;height:' + vh + 'px;background:rgba(0,0,0,0.52);z-index:99980;pointer-events:none;';
    bottom.style.cssText = 'display:none;';
    left.style.cssText   = 'display:none;';
    right.style.cssText  = 'display:none;';
  }
  function _positionCenter() {
    var arrow = document.getElementById('ob-arrow');
    arrow.className = 'ob-arrow';

    var tw = 288, th = _elTipseen.offsetHeight || 180;
    var vw = window.innerWidth, vh = window.innerHeight;
    _elTipseen.style.left = Math.max(8, (vw - tw) / 2) + 'px';
    _elTipseen.style.top  = Math.max(8, (vh - th) / 2) + 'px';
  }

  /** Posisikan tipseen anchored ke elemen target */
  function _positionTipseen(rect, PAD) {
    var arrow  = document.getElementById('ob-arrow');
    var GAP    = 14; // jarak antara highlight dan tipseen
    var tw     = 288;
    var th     = _elTipseen.offsetHeight || 160;
    var vw     = window.innerWidth;
    var vh     = window.innerHeight;

    var spaceBelow = vh - (rect.bottom + PAD);
    var spaceAbove = rect.top  - PAD;
    var spaceRight = vw - (rect.right  + PAD);
    var spaceLeft  = rect.left - PAD;

    var tipTop, tipLeft, arrowClass, arrowOffset;

    // Prioritas: kanan → kiri → bawah → atas
    // Elemen sempit tinggi (misal sidebar): tempatkan di kanan atau kiri
    var isNarrowTall = rect.height > rect.width;

    if (isNarrowTall && spaceRight >= tw + GAP) {
      // Letakkan di kanan
      tipLeft    = rect.right + PAD + GAP;
      tipTop     = rect.top + rect.height / 2 - th / 2;
      tipTop     = Math.max(8, Math.min(tipTop, vh - th - 8));
      arrowClass = 'ob-arrow ob-arrow-left';

      // Reset horizontal arrow, set vertical
      arrow.className       = arrowClass;
      arrow.style.left      = '';
      arrow.style.transform = '';
      var arrowTop = Math.min(Math.max((rect.top + rect.height / 2) - tipTop, 20), th - 20);
      arrow.style.top = arrowTop + 'px';

      _elTipseen.style.top  = tipTop  + 'px';
      _elTipseen.style.left = tipLeft + 'px';
      return;

    } else if (isNarrowTall && spaceLeft >= tw + GAP) {
      // Letakkan di kiri
      tipLeft    = rect.left - PAD - GAP - tw;
      tipTop     = rect.top + rect.height / 2 - th / 2;
      tipTop     = Math.max(8, Math.min(tipTop, vh - th - 8));
      arrowClass = 'ob-arrow ob-arrow-right';

      arrow.className       = arrowClass;
      arrow.style.left      = '';
      arrow.style.transform = '';
      var arrowTop = Math.min(Math.max((rect.top + rect.height / 2) - tipTop, 20), th - 20);
      arrow.style.top = arrowTop + 'px';

      _elTipseen.style.top  = tipTop  + 'px';
      _elTipseen.style.left = tipLeft + 'px';
      return;
    }

    // Default: bawah atau atas
    if (spaceBelow >= th + GAP) {
      tipTop     = rect.bottom + PAD + GAP;
      arrowClass = 'ob-arrow ob-arrow-up';
    } else if (spaceAbove >= th + GAP) {
      tipTop     = rect.top - PAD - GAP - th;
      arrowClass = 'ob-arrow ob-arrow-down';
    } else {
      tipTop     = rect.bottom + PAD + GAP;
      arrowClass = 'ob-arrow ob-arrow-up';
    }

    // Horizontal: sejajarkan tengah elemen target, clamp ke viewport
    var centerX = rect.left + rect.width / 2;
    tipLeft = centerX - tw / 2;
    tipLeft = Math.max(8, Math.min(tipLeft, vw - tw - 8));

    arrowOffset = Math.min(Math.max(centerX - tipLeft, 20), tw - 20);

    _elTipseen.style.top  = tipTop  + 'px';
    _elTipseen.style.left = tipLeft + 'px';
    arrow.className       = arrowClass;
    arrow.style.top       = '';
    arrow.style.left      = arrowOffset + 'px';
    arrow.style.transform = 'translateX(-50%)';
  }

  // ─── Navigasi ─────────────────────────────────────────────────────────────
  function prevStep() {
    var idx = prevValidIndex(_current - 1);
    if (idx !== -1) { _current = idx; renderStep(_current); }
  }

  function nextStep() {
    var idx = nextValidIndex(_current + 1);
    if (idx !== -1) {
      _current = idx;
      renderStep(_current);
    } else {
      endTour(true);
    }
  }

  // ─── Keyboard ─────────────────────────────────────────────────────────────
  function onKey(e) {
    if (!_active) return;
    if (e.key === 'Escape')     { endTour(true); }
    if (e.key === 'ArrowRight') { nextStep(); }
    if (e.key === 'ArrowLeft')  { prevStep(); }
  }

  // ─── Resize ──────────────────────────────────────────────────────────────
  function onResize() {
    clearTimeout(_resizeTimer);
    _resizeTimer = setTimeout(function () {
      if (_active) renderStep(_current);
    }, 120);
  }

  // ─── Public API ──────────────────────────────────────────────────────────

  /**
   * startTour(steps, tourKey, force)
   * @param {Array}   steps   - Array of { target, title, body }
   * @param {string}  tourKey - localStorage key, misal 'tour_dashboard_v1'
   * @param {boolean} force   - true = abaikan localStorage (restart)
   */
  function startTour(steps, tourKey, force) {
    if (!steps || steps.length === 0) return;
    if (!force && tourKey && localStorage.getItem(tourKey) === 'done') return;

    // Jika tour lain sedang berjalan, tutup dulu
    if (_active) destroyDom();

    _steps   = steps;
    _tourKey = tourKey || '';
    _current = 0;
    _active  = true;

    injectCss();
    buildDom();

    requestAnimationFrame(function () {
      // Cari step valid pertama
      var firstValid = nextValidIndex(0);
      if (firstValid === -1) { endTour(false); return; }
      _current = firstValid;
      renderStep(_current);
      setTimeout(function () {
        if (_elTipseen) _elTipseen.classList.add('ob-visible');
      }, 60);
    });
  }

  /**
   * startTourIfNew — mulai hanya jika belum pernah selesai (cek localStorage)
   * Auto-delay 800ms agar halaman sempurna ter-render
   */
  function startTourIfNew(steps, tourKey) {
    if (!tourKey || localStorage.getItem(tourKey) === 'done') return;
    setTimeout(function () { startTour(steps, tourKey, false); }, 800);
  }

  /**
   * endTour(save) — tutup tour
   * @param {boolean} save - true = simpan 'done' ke localStorage
   */
  function endTour(save) {
    if (!_active) return;
    _active = false;

    // Animasi fade-out tipseen, backdrop langsung hilang via destroyDom
    if (_elTipseen) _elTipseen.classList.remove('ob-visible');

    setTimeout(function () {
      destroyDom();
      if (save && _tourKey) {
        try { localStorage.setItem(_tourKey, 'done'); } catch (e) {}
      }
    }, 250);
  }

  // ─── Export ke global ────────────────────────────────────────────────────
  global.startTour      = startTour;
  global.startTourIfNew = startTourIfNew;
  global.endTour        = endTour;

}(window));
