/**
 * sonner.js — Monday.com-style toast notifications
 *
 * Design:
 *  - Solid colored background per variant (#0073EA blue as default)
 *  - Flat layout: [icon | message + link] ──────── [action btn | × close]
 *  - Font: Figtree, 14px/20px
 *  - border-radius: 4px, box-shadow: 0 6px 20px rgba(0,0,0,0.2)
 *  - Height: 48px (auto on mobile)
 *
 * API:
 *   toast("Hello!")
 *   toast.success("Saved!")
 *   toast.error("Failed!")
 *   toast.warning("Watch out!")
 *   toast.info("FYI")
 *   toast.loading("Loading...")
 *   toast.promise(fetchPromise, { loading, success, error })
 *   toast.dismiss() / toast.dismiss(id)
 *   showToast(msg, type)  ← backward-compat alias
 */
(function () {

  // ─── CONFIG ─────────────────────────────────────────────────────────────────
  const DURATION   = 4000;
  const MAX_TOASTS = 5;

  // ─── ICONS (white, 20x20) ───────────────────────────────────────────────────
  const ICONS = {
    default: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
    success: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>`,
    error:   `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>`,
    warning: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>`,
    info:    `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
    loading: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:sonner-spin-anim .75s linear infinite;transform-origin:center"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>`,
    close:   `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>`,
  };

  // ─── VARIANT PALETTE ────────────────────────────────────────────────────────
  // Solid background, all text/icons white
  const PALETTE = {
    default: { bg: '#0073EA', progress: 'rgba(255,255,255,0.4)' },
    success: { bg: '#00C875', progress: 'rgba(255,255,255,0.4)' },
    error:   { bg: '#E2445C', progress: 'rgba(255,255,255,0.4)' },
    warning: { bg: '#FFCB00', progress: 'rgba(0,0,0,0.15)',      textColor: '#333333' },
    info:    { bg: '#0073EA', progress: 'rgba(255,255,255,0.4)' },
    loading: { bg: '#676879', progress: 'rgba(255,255,255,0.4)' },
  };

  // ─── INJECT STYLES ──────────────────────────────────────────────────────────
  if (!document.getElementById('sonner-styles')) {
    const s = document.createElement('style');
    s.id = 'sonner-styles';
    s.textContent = `
      @import url('https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600&display=swap');

      #sonner-toaster {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 99999;
        display: flex;
        flex-direction: column-reverse;
        gap: 8px;
        pointer-events: none;
        width: 436px;
        max-width: calc(100vw - 32px);
      }

      /* ── Toast card ── */
      .sonner-toast {
        pointer-events: all;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        width: 100%;
        min-height: 48px;
        padding: 8px 16px;
        border-radius: 4px;
        border: none;
        box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.2);
        font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        color: #ffffff;
        box-sizing: border-box;
        /* animation */
        transform: translateY(16px) scale(0.98);
        opacity: 0;
        transition:
          transform 0.28s cubic-bezier(0.16, 1, 0.3, 1),
          opacity   0.28s cubic-bezier(0.16, 1, 0.3, 1);
        will-change: transform, opacity;
        position: relative;
        overflow: hidden;
      }

      .sonner-toast.sonner-visible {
        transform: translateY(0) scale(1);
        opacity: 1;
      }

      .sonner-toast.sonner-exiting {
        transform: translateY(16px) scale(0.98);
        opacity: 0;
      }

      /* ── Left: icon + text ── */
      .sonner-left {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 16px;
        flex: 1;
        min-width: 0;
      }

      /* Content: icon + body */
      .sonner-content {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 1;
      }

      .sonner-icon {
        flex-shrink: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
      }

      .sonner-icon svg {
        display: block;
        width: 20px;
        height: 20px;
      }

      .sonner-body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
      }

      .sonner-title {
        font-size: 14px;
        font-weight: 400;
        line-height: 20px;
        margin: 0;
        color: #ffffff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .sonner-description {
        font-size: 14px;
        font-weight: 400;
        line-height: 20px;
        color: #ffffff;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      /* ── Right: action + close ── */
      .sonner-right {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: flex-end;
        gap: 16px;
        flex-shrink: 0;
      }

      /* Action button — white outline style */
      .sonner-action-btn {
        font-family: 'Figtree', inherit;
        font-size: 14px;
        font-weight: 400;
        line-height: 20px;
        padding: 0 8px;
        height: 32px;
        min-height: 32px;
        max-height: 32px;
        border-radius: 4px;
        border: 1px solid #ffffff;
        background: transparent;
        color: #ffffff;
        cursor: pointer;
        white-space: nowrap;
        display: flex;
        align-items: center;
        transition: background 0.15s, opacity 0.15s;
      }

      .sonner-action-btn:hover {
        background: rgba(255,255,255,0.15);
      }

      /* Close button */
      .sonner-close-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 4px;
        border: none;
        background: transparent;
        cursor: pointer;
        color: #ffffff;
        transition: background 0.15s;
        flex-shrink: 0;
        padding: 0;
      }

      .sonner-close-btn:hover {
        background: rgba(255,255,255,0.2);
      }

      .sonner-close-btn:focus-visible {
        outline: 2px solid rgba(255,255,255,0.7);
        outline-offset: 2px;
      }

      /* Progress bar */
      .sonner-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 2px;
        border-radius: 0;
        animation: sonner-shrink linear forwards;
      }

      @keyframes sonner-shrink {
        from { width: 100%; }
        to   { width: 0%; }
      }

      @keyframes sonner-spin-anim {
        to { transform: rotate(360deg); }
      }

      @media (max-width: 480px) {
        #sonner-toaster {
          left: 16px;
          right: 16px;
          bottom: 16px;
          transform: none;
          width: auto;
        }
        .sonner-toast {
          width: 100%;
          min-height: 48px;
          height: auto;
        }
        .sonner-title,
        .sonner-description {
          white-space: normal;
        }
      }
    `;
    document.head.appendChild(s);
  }

  // ─── TOASTER CONTAINER ──────────────────────────────────────────────────────
  function getToaster() {
    let el = document.getElementById('sonner-toaster');
    if (!el) {
      el = document.createElement('div');
      el.id = 'sonner-toaster';
      el.setAttribute('aria-live', 'polite');
      el.setAttribute('role', 'region');
      el.setAttribute('aria-label', 'Notifications');
      document.body.appendChild(el);
    }
    return el;
  }

  // ─── STATE ──────────────────────────────────────────────────────────────────
  let toastIdCounter = 0;
  const activeToasts = new Map(); // id → { el, timer }

  // ─── DISMISS ────────────────────────────────────────────────────────────────
  function dismiss(id) {
    const entry = activeToasts.get(id);
    if (!entry) return;
    const { el, timer } = entry;
    clearTimeout(timer);
    activeToasts.delete(id);
    el.classList.remove('sonner-visible');
    el.classList.add('sonner-exiting');
    el.addEventListener('transitionend', () => el.remove(), { once: true });
    setTimeout(() => el.remove(), 500);
  }

  // ─── CREATE TOAST ───────────────────────────────────────────────────────────
  function createToast(message, opts = {}) {
    const {
      type        = 'default',
      title       = null,
      description = null,
      duration    = DURATION,
      action      = null,   // { label, onClick }
      onDismiss   = null,
    } = opts;

    const bodyText = description || message;

    // Prune oldest if over limit
    if (activeToasts.size >= MAX_TOASTS) {
      dismiss(activeToasts.keys().next().value);
    }

    const id     = ++toastIdCounter;
    const p      = PALETTE[type] || PALETTE.default;
    const iconSvg = ICONS[type]  || ICONS.default;
    const textClr = p.textColor || '#ffffff';

    const toaster = getToaster();
    const el      = document.createElement('div');
    el.className  = 'sonner-toast';
    el.setAttribute('role', 'status');
    el.style.background = p.bg;

    // ── Left: content (icon + body) ──
    const left = document.createElement('div');
    left.className = 'sonner-left';

    const content = document.createElement('div');
    content.className = 'sonner-content';

    const iconEl = document.createElement('div');
    iconEl.className = 'sonner-icon';
    iconEl.style.color = textClr;
    iconEl.innerHTML = iconSvg;

    const body = document.createElement('div');
    body.className = 'sonner-body';

    // If title exists, show title + description beneath
    if (title) {
      const titleEl = document.createElement('p');
      titleEl.className = 'sonner-title';
      titleEl.style.color = textClr;
      titleEl.style.fontWeight = '600';
      titleEl.textContent = title;
      body.appendChild(titleEl);
    }

    const descEl = document.createElement('p');
    descEl.className = 'sonner-description';
    descEl.style.color = textClr;
    if (!title) {
      descEl.style.fontWeight = '400';
    }
    descEl.textContent = bodyText;
    body.appendChild(descEl);

    content.appendChild(iconEl);
    content.appendChild(body);
    left.appendChild(content);

    // ── Right: action + close ──
    const right = document.createElement('div');
    right.className = 'sonner-right';

    if (action && action.label) {
      const actionBtn = document.createElement('button');
      actionBtn.className = 'sonner-action-btn';
      actionBtn.style.color = textClr;
      actionBtn.style.borderColor = textClr;
      actionBtn.textContent = action.label;
      actionBtn.onclick = () => {
        action.onClick();
        dismiss(id);
      };
      right.appendChild(actionBtn);
    }

    const closeBtn = document.createElement('button');
    closeBtn.className = 'sonner-close-btn';
    closeBtn.setAttribute('aria-label', 'Tutup notifikasi');
    closeBtn.style.color = textClr;
    closeBtn.innerHTML = ICONS.close;
    closeBtn.onclick = () => {
      dismiss(id);
      onDismiss?.();
    };
    right.appendChild(closeBtn);

    // ── Progress bar ──
    let progressEl = null;
    if (duration > 0 && type !== 'loading') {
      progressEl = document.createElement('div');
      progressEl.className = 'sonner-progress';
      progressEl.style.background = p.progress;
      progressEl.style.animationDuration = `${duration}ms`;
      el.appendChild(progressEl);
    }

    // ── Assemble ──
    el.appendChild(left);
    el.appendChild(right);
    toaster.prepend(el);

    // Animate in
    requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('sonner-visible')));

    // Auto-dismiss
    let timer = null;
    if (duration > 0 && type !== 'loading') {
      timer = setTimeout(() => dismiss(id), duration);
    }
    activeToasts.set(id, { el, timer });

    // Pause on hover
    el.addEventListener('mouseenter', () => {
      if (timer) clearTimeout(timer);
      if (progressEl) progressEl.style.animationPlayState = 'paused';
    });
    el.addEventListener('mouseleave', () => {
      if (duration > 0 && type !== 'loading') {
        if (progressEl) progressEl.style.animationPlayState = 'running';
        const remaining = duration * 0.35;
        timer = setTimeout(() => dismiss(id), remaining);
        const entry = activeToasts.get(id);
        if (entry) entry.timer = timer;
      }
    });

    return id;
  }

  // ─── UPDATE TOAST (used by toast.promise) ───────────────────────────────────
  function updateToast(id, message, type, title) {
    const entry = activeToasts.get(id);
    if (!entry) return;
    const { el } = entry;
    const p = PALETTE[type] || PALETTE.default;
    const textClr = p.textColor || '#ffffff';

    el.style.background = p.bg;

    const iconEl  = el.querySelector('.sonner-icon');
    const titleEl = el.querySelector('.sonner-title');
    const descEl  = el.querySelector('.sonner-description');
    const closeBtn = el.querySelector('.sonner-close-btn');
    const actionBtn = el.querySelector('.sonner-action-btn');

    if (iconEl) { iconEl.style.color = textClr; iconEl.innerHTML = ICONS[type] || ICONS.default; }
    if (closeBtn) closeBtn.style.color = textClr;
    if (actionBtn) { actionBtn.style.color = textClr; actionBtn.style.borderColor = textClr; }

    if (title && titleEl) { titleEl.style.color = textClr; titleEl.textContent = title; }
    if (descEl) { descEl.textContent = message; descEl.style.color = textClr; }

    if (type !== 'loading') {
      let progressEl = el.querySelector('.sonner-progress');
      if (!progressEl) {
        progressEl = document.createElement('div');
        progressEl.className = 'sonner-progress';
        el.appendChild(progressEl);
      }
      progressEl.style.background = p.progress;
      progressEl.style.animationDuration = `${DURATION}ms`;
      progressEl.style.animationPlayState = 'running';

      const timer = setTimeout(() => dismiss(id), DURATION);
      const old = activeToasts.get(id);
      if (old) { clearTimeout(old.timer); old.timer = timer; }
    }
  }

  // ─── PUBLIC API ─────────────────────────────────────────────────────────────
  function toast(message, opts = {}) {
    return createToast(message, { type: 'default', ...opts });
  }

  toast.success = (message, opts = {}) => createToast(message, { type: 'success', ...opts });
  toast.error   = (message, opts = {}) => createToast(message, { type: 'error',   ...opts });
  toast.warning = (message, opts = {}) => createToast(message, { type: 'warning', ...opts });
  toast.info    = (message, opts = {}) => createToast(message, { type: 'info',    ...opts });
  toast.loading = (message, opts = {}) => createToast(message, { type: 'loading', duration: 0, ...opts });

  toast.dismiss = (id) => {
    if (id !== undefined) {
      dismiss(id);
    } else {
      activeToasts.forEach((_, id) => dismiss(id));
    }
  };

  /**
   * toast.promise(promise, { loading, success, error })
   */
  toast.promise = (promise, { loading: loadMsg, success: successMsg, error: errorMsg } = {}) => {
    const id = toast.loading(loadMsg || 'Memuat...');
    Promise.resolve(promise)
      .then((result) => {
        const msg = typeof successMsg === 'function' ? successMsg(result) : (successMsg || 'Berhasil!');
        updateToast(id, msg, 'success', null);
      })
      .catch((err) => {
        const msg = typeof errorMsg === 'function' ? errorMsg(err) : (errorMsg || 'Terjadi kesalahan.');
        updateToast(id, msg, 'error', null);
      });
    return id;
  };

  // ─── EXPOSE ─────────────────────────────────────────────────────────────────
  window.toast = toast;

  // Backward-compat: showToast(msg, type) masih berfungsi di semua halaman
  window.showToast = (message, type) => {
    const map = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
    return createToast(message, { type: map[type] || 'default' });
  };

})();
