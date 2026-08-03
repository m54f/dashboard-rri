/**
 * sonner.js — Vanilla JS toast inspired by the custom Sonner React component.
 *
 * Design:
 *  - White card background for all variants
 *  - Variant-colored border (50% opacity), icon, and title
 *  - Layout: [icon | title+message] ─────── [action btn | × close]
 *  - Compact size, xs font, rounded-xl, shadow-md
 *  - Slide-up + scale animation (matches framer-motion variant)
 *
 * API (same as Sonner React library):
 *   toast("Hello!")
 *   toast.success("Saved!", { title: "Done", description: "...", action: { label, onClick } })
 *   toast.error("Failed!")
 *   toast.warning("Watch out!")
 *   toast.info("FYI")
 *   toast.loading("Loading...")
 *   toast.promise(fetchPromise, { loading, success, error })
 *   toast.dismiss() / toast.dismiss(id)
 *   showToast(msg, type)  ← backward-compat alias
 */
(function () {

  // ─── CONFIG ────────────────────────────────────────────────────────────────
  const DURATION   = 4000;
  const MAX_TOASTS = 5;

  // ─── ICONS ─────────────────────────────────────────────────────────────────
  const ICONS = {
    default: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
    success: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>`,
    error:   `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>`,
    warning: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>`,
    info:    `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
    loading: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:sonner-spin-anim .75s linear infinite;transform-origin:center"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>`,
    close:   `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>`,
  };

  // ─── VARIANT PALETTE ───────────────────────────────────────────────────────
  // All variants use white card bg; only border, icon & title color differ.
  const PALETTE = {
    default: {
      border:      '#e2e8f0',
      icon:        '#64748b',
      title:       '#0f172a',
      desc:        '#94a3b8',
      actionText:  '#0f172a',
      actionBorder:'#e2e8f0',
      actionHover: 'rgba(100,116,139,0.08)',
      progress:    '#64748b',
    },
    success: {
      border:      'rgba(22,163,74,0.45)',
      icon:        '#16a34a',
      title:       '#16a34a',
      desc:        '#94a3b8',
      actionText:  '#16a34a',
      actionBorder:'rgba(22,163,74,0.5)',
      actionHover: 'rgba(22,163,74,0.08)',
      progress:    '#16a34a',
    },
    error: {
      border:      'rgba(220,38,38,0.45)',
      icon:        '#dc2626',
      title:       '#dc2626',
      desc:        '#94a3b8',
      actionText:  '#dc2626',
      actionBorder:'rgba(220,38,38,0.5)',
      actionHover: 'rgba(220,38,38,0.08)',
      progress:    '#dc2626',
    },
    warning: {
      border:      'rgba(217,119,6,0.45)',
      icon:        '#d97706',
      title:       '#d97706',
      desc:        '#94a3b8',
      actionText:  '#d97706',
      actionBorder:'rgba(217,119,6,0.5)',
      actionHover: 'rgba(217,119,6,0.08)',
      progress:    '#d97706',
    },
    info: {
      border:      'rgba(37,99,235,0.45)',
      icon:        '#2563eb',
      title:       '#2563eb',
      desc:        '#94a3b8',
      actionText:  '#2563eb',
      actionBorder:'rgba(37,99,235,0.5)',
      actionHover: 'rgba(37,99,235,0.08)',
      progress:    '#2563eb',
    },
    loading: {
      border:      '#e2e8f0',
      icon:        '#6366f1',
      title:       '#0f172a',
      desc:        '#94a3b8',
      actionText:  '#6366f1',
      actionBorder:'rgba(99,102,241,0.4)',
      actionHover: 'rgba(99,102,241,0.08)',
      progress:    '#6366f1',
    },
  };

  // ─── INJECT STYLES ─────────────────────────────────────────────────────────
  if (!document.getElementById('sonner-styles')) {
    const s = document.createElement('style');
    s.id = 'sonner-styles';
    s.textContent = `
      #sonner-toaster {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 99999;
        display: flex;
        flex-direction: column-reverse;
        gap: 10px;
        pointer-events: none;
      }

      /* ── Toast card ── */
      .sonner-toast {
        pointer-events: all;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 320px;
        max-width: calc(100vw - 48px);
        padding: 12px 14px;
        border-radius: 14px;
        border: 1px solid;
        background: #ffffff;
        box-shadow:
          0 4px 6px -1px rgba(0,0,0,.07),
          0 10px 15px -3px rgba(0,0,0,.07);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        /* animation */
        transform: translateY(40px) scale(0.95);
        opacity: 0;
        transition:
          transform 0.32s cubic-bezier(0.16, 1, 0.3, 1),
          opacity   0.32s cubic-bezier(0.16, 1, 0.3, 1),
          box-shadow 0.2s ease;
        will-change: transform, opacity;
        position: relative;
        overflow: hidden;
      }

      .sonner-toast.sonner-visible {
        transform: translateY(0) scale(1);
        opacity: 1;
      }

      .sonner-toast.sonner-exiting {
        transform: translateY(40px) scale(0.95);
        opacity: 0;
      }

      .sonner-toast:hover {
        box-shadow:
          0 10px 15px -3px rgba(0,0,0,.1),
          0 20px 25px -5px rgba(0,0,0,.08);
      }

      /* ── Left side: icon + text ── */
      .sonner-left {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        flex: 1;
        min-width: 0;
      }

      .sonner-icon {
        flex-shrink: 0;
        width: 16px;
        height: 16px;
        margin-top: 2px;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .sonner-icon svg {
        display: block;
        width: 100%;
        height: 100%;
      }

      .sonner-body {
        flex: 1;
        min-width: 0;
      }

      .sonner-title {
        font-size: 0.75rem;   /* 12px */
        font-weight: 600;
        line-height: 1.3;
        margin: 0 0 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .sonner-description {
        font-size: 0.75rem;   /* 12px */
        line-height: 1.4;
        color: #94a3b8;
        margin: 0;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
      }

      /* ── Right side: action + close ── */
      .sonner-right {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
      }

      /* Action button — outline style */
      .sonner-action-btn {
        font-family: inherit;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 7px;
        border: 1px solid;
        background: transparent;
        cursor: pointer;
        white-space: nowrap;
        transition: background 0.15s, opacity 0.15s;
        line-height: 1.4;
      }

      .sonner-action-btn:hover {
        opacity: 0.85;
      }

      /* Close button — rounded circle */
      .sonner-close-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        border: none;
        background: transparent;
        cursor: pointer;
        color: #94a3b8;
        transition: background 0.15s, color 0.15s;
        flex-shrink: 0;
        padding: 0;
      }

      .sonner-close-btn:hover {
        background: rgba(100,116,139,0.12);
        color: #475569;
      }

      .sonner-close-btn:focus-visible {
        outline: 2px solid #6366f1;
        outline-offset: 2px;
      }

      /* Progress bar */
      .sonner-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 2px;
        border-radius: 0 0 14px 14px;
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
          left: 12px;
          right: 12px;
          bottom: 16px;
        }
        .sonner-toast {
          width: 100%;
        }
      }
    `;
    document.head.appendChild(s);
  }

  // ─── TOASTER CONTAINER ─────────────────────────────────────────────────────
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

  // ─── STATE ─────────────────────────────────────────────────────────────────
  let toastIdCounter = 0;
  const activeToasts = new Map(); // id → { el, timer }

  // ─── DISMISS ───────────────────────────────────────────────────────────────
  function dismiss(id) {
    const entry = activeToasts.get(id);
    if (!entry) return;
    const { el, timer } = entry;
    clearTimeout(timer);
    activeToasts.delete(id);
    el.classList.remove('sonner-visible');
    el.classList.add('sonner-exiting');
    el.addEventListener('transitionend', () => el.remove(), { once: true });
    // safety fallback
    setTimeout(() => el.remove(), 600);
  }

  // ─── CREATE TOAST ──────────────────────────────────────────────────────────
  function createToast(message, opts = {}) {
    const {
      type        = 'default',
      title       = null,
      description = null,   // alias for message when using description key
      duration    = DURATION,
      action      = null,   // { label, onClick }
      onDismiss   = null,
    } = opts;

    // Resolve the body text: prefer explicit `description`, else use `message`
    const bodyText = description || message;

    // Prune oldest if over limit
    if (activeToasts.size >= MAX_TOASTS) {
      dismiss(activeToasts.keys().next().value);
    }

    const id = ++toastIdCounter;
    const p  = PALETTE[type] || PALETTE.default;
    const iconSvg = ICONS[type] || ICONS.default;

    const toaster = getToaster();
    const el      = document.createElement('div');
    el.className  = 'sonner-toast';
    el.setAttribute('role', 'status');
    el.style.borderColor = p.border;

    // ── Left: icon + body ──
    const left = document.createElement('div');
    left.className = 'sonner-left';

    const iconEl = document.createElement('div');
    iconEl.className = 'sonner-icon';
    iconEl.style.color = p.icon;
    iconEl.innerHTML = iconSvg;

    const body = document.createElement('div');
    body.className = 'sonner-body';

    if (title) {
      const titleEl = document.createElement('p');
      titleEl.className = 'sonner-title';
      titleEl.style.color = p.title;
      titleEl.textContent = title;
      body.appendChild(titleEl);
    }

    const descEl = document.createElement('p');
    descEl.className = 'sonner-description';
    // If there's a title, make message text muted; if no title, give it a bit more weight
    if (!title) {
      descEl.style.color = p.title;
      descEl.style.fontWeight = '500';
    }
    descEl.textContent = bodyText;
    body.appendChild(descEl);

    left.appendChild(iconEl);
    left.appendChild(body);

    // ── Right: action + close ──
    const right = document.createElement('div');
    right.className = 'sonner-right';

    if (action && action.label) {
      const actionBtn = document.createElement('button');
      actionBtn.className = 'sonner-action-btn';
      actionBtn.style.color       = p.actionText;
      actionBtn.style.borderColor = p.actionBorder;
      actionBtn.textContent = action.label;
      actionBtn.addEventListener('mouseenter', () => {
        actionBtn.style.background = p.actionHover;
      });
      actionBtn.addEventListener('mouseleave', () => {
        actionBtn.style.background = 'transparent';
      });
      actionBtn.onclick = () => {
        action.onClick();
        dismiss(id);
      };
      right.appendChild(actionBtn);
    }

    const closeBtn = document.createElement('button');
    closeBtn.className = 'sonner-close-btn';
    closeBtn.setAttribute('aria-label', 'Tutup notifikasi');
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
      progressEl.style.background     = p.progress;
      progressEl.style.animationDuration = `${duration}ms`;
      el.appendChild(progressEl);
    }

    // ── Assemble ──
    el.appendChild(left);
    el.appendChild(right);
    toaster.prepend(el);

    // Animate in (needs two frames for transition to fire)
    requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('sonner-visible')));

    // Auto-dismiss
    let timer = null;
    if (duration > 0 && type !== 'loading') {
      timer = setTimeout(() => dismiss(id), duration);
    }
    activeToasts.set(id, { el, timer });

    // Pause on hover
    el.addEventListener('mouseenter', () => {
      if (timer) { clearTimeout(timer); }
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

  // ─── UPDATE TOAST (used by toast.promise) ──────────────────────────────────
  function updateToast(id, message, type, title) {
    const entry = activeToasts.get(id);
    if (!entry) return;
    const { el } = entry;
    const p = PALETTE[type] || PALETTE.default;

    el.style.borderColor = p.border;

    const iconEl  = el.querySelector('.sonner-icon');
    const titleEl = el.querySelector('.sonner-title');
    const descEl  = el.querySelector('.sonner-description');

    if (iconEl) { iconEl.style.color = p.icon; iconEl.innerHTML = ICONS[type] || ICONS.default; }

    if (title) {
      if (titleEl) {
        titleEl.style.color = p.title;
        titleEl.textContent = title;
      } else {
        const t = document.createElement('p');
        t.className = 'sonner-title';
        t.style.color = p.title;
        t.textContent = title;
        el.querySelector('.sonner-body')?.prepend(t);
      }
    }

    if (descEl) {
      descEl.textContent = message;
      descEl.style.color = '';
      descEl.style.fontWeight = '';
    }

    // Start auto-dismiss once resolved
    if (type !== 'loading') {
      let progressEl = el.querySelector('.sonner-progress');
      if (!progressEl) {
        progressEl = document.createElement('div');
        progressEl.className = 'sonner-progress';
        el.appendChild(progressEl);
      }
      progressEl.style.background       = p.progress;
      progressEl.style.animationDuration = `${DURATION}ms`;
      progressEl.style.animationPlayState = 'running';

      const timer = setTimeout(() => dismiss(id), DURATION);
      const old = activeToasts.get(id);
      if (old) { clearTimeout(old.timer); old.timer = timer; }
    }
  }

  // ─── PUBLIC API ────────────────────────────────────────────────────────────
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

  // ─── EXPOSE ────────────────────────────────────────────────────────────────
  window.toast = toast;

  // Backward-compat: showToast(msg, type) still works on all existing pages
  window.showToast = (message, type) => {
    const map = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
    return createToast(message, { type: map[type] || 'default' });
  };

})();
