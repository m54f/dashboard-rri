/**
 * sonner.js — Toast Notification (Monday.com / Vibe Design System)
 *
 * Spec layout (Figma):
 *  Toast      : 436×48px, bg #0073EA, padding 8px 16px, gap 8px (column), border-radius 4px
 *  Container  : flex-row, gap 24px, align-center, 404×32px
 *  Content    : flex-row, gap 16px, align-center, 273×20px
 *    Title    : flex-row, gap 8px, align-center  — [Icon 20×20] [text 14px/20px white]
 *    Link     : 14px/20px, underline, white
 *  Actions    : flex-row, justify-end, gap 16px, 107×32px
 *    Button   : 59×32px, border 1px solid #fff, border-radius 4px, 14px white
 *    Close    : 32×32px, border-radius 4px, icon 20×20 white
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

  // ─── ICONS (white SVG, 20×20) ───────────────────────────────────────────────
  const ICONS = {
    default: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
    success: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>`,
    error:   `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>`,
    warning: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>`,
    info:    `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
    loading: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:sonner-spin .75s linear infinite;transform-origin:center"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>`,
    /* Close icon: 16×16 X mark */
    close:   `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>`,
  };

  // ─── VARIANT PALETTE ────────────────────────────────────────────────────────
  const PALETTE = {
    default: { bg: '#0073EA', progress: 'rgba(255,255,255,0.4)' },
    success: { bg: '#00C875', progress: 'rgba(255,255,255,0.4)' },
    error:   { bg: '#E2445C', progress: 'rgba(255,255,255,0.4)' },
    warning: { bg: '#FFCB00', progress: 'rgba(0,0,0,0.15)', textColor: '#333333' },
    info:    { bg: '#0073EA', progress: 'rgba(255,255,255,0.4)' },
    loading: { bg: '#676879', progress: 'rgba(255,255,255,0.4)' },
  };

  // ─── INJECT STYLES ──────────────────────────────────────────────────────────
  if (!document.getElementById('sonner-styles')) {
    const s = document.createElement('style');
    s.id = 'sonner-styles';
    s.textContent = `
      @import url('https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600&display=swap');

      /* ── Toaster container (fixed, bottom-center) ── */
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

      /* ── Toast card ──
         Spec: display flex, flex-direction column, align-items flex-start,
               padding 8px 16px, gap 8px, width 436px, height 48px,
               bg #0073EA, box-shadow 0 6px 20px rgba(0,0,0,.2), border-radius 4px
      */
      .sonner-toast {
        pointer-events: all;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding: 8px 16px;
        gap: 8px;
        width: 100%;          /* 436px via parent */
        min-height: 48px;
        border-radius: 4px;
        border: none;
        box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.2);
        font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        position: relative;
        overflow: hidden;
        /* entry animation */
        transform: translateY(16px) scale(0.98);
        opacity: 0;
        transition:
          transform 0.28s cubic-bezier(0.16, 1, 0.3, 1),
          opacity   0.28s cubic-bezier(0.16, 1, 0.3, 1);
        will-change: transform, opacity;
      }

      .sonner-toast.sonner-visible {
        transform: translateY(0) scale(1);
        opacity: 1;
      }

      .sonner-toast.sonner-exiting {
        transform: translateY(16px) scale(0.98);
        opacity: 0;
      }

      /* ── Container ──
         Spec: flex-row, justify-content center, align-items center,
               gap 24px, width 404px, height 32px
      */
      .sonner-container {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 0;
        gap: 24px;
        width: 100%;
        min-height: 32px;
        flex: none;
        order: 0;
        flex-grow: 0;
      }

      /* ── Content (left side: icon + title + optional link) ──
         Spec: flex-row, justify-content center, align-items center,
               gap 16px, width 273px, height 20px
      */
      .sonner-content {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 0;
        gap: 16px;
        flex: 1;
        min-width: 0;
        min-height: 20px;
        flex-shrink: 0;
        order: 0;
        flex-grow: 1;
      }

      /* ── Title block (icon + text) ──
         Spec: flex-row, align-items center, gap 8px, width 172px, height 20px
      */
      .sonner-title-block {
        display: flex;
        flex-direction: row;
        align-items: center;
        padding: 0;
        gap: 8px;
        min-width: 0;
        flex-shrink: 1;
        min-height: 20px;
        order: 0;
        flex-grow: 0;
      }

      /* ── Icon (20×20) ── */
      .sonner-icon {
        flex-shrink: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        order: 0;
        flex-grow: 0;
      }

      .sonner-icon svg {
        display: block;
        width: 20px;
        height: 20px;
      }

      /* ── Title text ──
         Spec: width 144px, height 20px, Figtree 14px/20px, weight 400, color #fff
      */
      .sonner-title {
        font-family: 'Figtree', inherit;
        font-style: normal;
        font-weight: 400;
        font-size: 14px;
        line-height: 20px;
        color: #ffffff;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: none;
        order: 1;
        flex-grow: 0;
      }

      /* ── Link ──
         Spec: width 85px, height 20px, Figtree 14px/20px, weight 400,
               text-decoration underline, color #fff
      */
      .sonner-link {
        font-family: 'Figtree', inherit;
        font-style: normal;
        font-weight: 400;
        font-size: 14px;
        line-height: 20px;
        text-decoration-line: underline;
        color: #ffffff;
        white-space: nowrap;
        cursor: pointer;
        background: none;
        border: none;
        padding: 0;
        flex: none;
        order: 1;
        flex-grow: 0;
      }

      .sonner-link:hover {
        opacity: 0.85;
      }

      /* ── Actions (right side) ──
         Spec: flex-row, justify-content flex-end, align-items center,
               gap 16px, width 107px, height 32px
      */
      .sonner-actions {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        align-items: center;
        padding: 0;
        gap: 16px;
        flex-shrink: 0;
        min-height: 32px;
        flex: none;
        order: 1;
        flex-grow: 0;
      }

      /* ── Action Button ──
         Spec: 59×32px, padding 0 8px, border 1px solid #fff,
               border-radius 4px, Figtree 14px/400, color #fff
      */
      .sonner-action-btn {
        box-sizing: border-box;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 0 8px;
        gap: 8px;
        width: auto;
        min-width: 59px;
        height: 32px;
        min-height: 32px;
        max-height: 32px;
        border: 1px solid #ffffff;
        border-radius: 4px;
        background: transparent;
        font-family: 'Figtree', inherit;
        font-style: normal;
        font-weight: 400;
        font-size: 14px;
        line-height: 20px;
        color: #ffffff;
        cursor: pointer;
        white-space: nowrap;
        flex: none;
        order: 0;
        flex-grow: 0;
        transition: background 0.15s;
      }

      .sonner-action-btn:hover {
        background: rgba(255, 255, 255, 0.15);
      }

      /* ── Close Button ──
         Spec: 32×32px, border-radius 4px, icon 20×20 white
      */
      .sonner-close-btn {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 0;
        gap: 8px;
        width: 32px;
        height: 32px;
        border-radius: 4px;
        border: none;
        background: transparent;
        cursor: pointer;
        color: #ffffff;
        flex: none;
        order: 1;
        flex-grow: 0;
        transition: background 0.15s;
      }

      .sonner-close-btn svg {
        width: 20px;
        height: 20px;
        display: block;
      }

      .sonner-close-btn:hover {
        background: rgba(255, 255, 255, 0.2);
      }

      .sonner-close-btn:focus-visible {
        outline: 2px solid rgba(255, 255, 255, 0.7);
        outline-offset: 2px;
      }

      /* ── Progress bar ── */
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

      @keyframes sonner-spin {
        to { transform: rotate(360deg); }
      }

      /* ── Responsive ── */
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
        .sonner-link {
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
      title       = null,      // optional bold title above message
      link        = null,      // { label, href, onClick } — underlined link in content area
      duration    = DURATION,
      action      = null,      // { label, onClick } — outline button in actions area
      onDismiss   = null,
    } = opts;

    // Prune oldest if over limit
    if (activeToasts.size >= MAX_TOASTS) {
      dismiss(activeToasts.keys().next().value);
    }

    const id      = ++toastIdCounter;
    const p       = PALETTE[type] || PALETTE.default;
    const iconSvg = ICONS[type]   || ICONS.default;
    const textClr = p.textColor   || '#ffffff';

    const toaster = getToaster();

    /* ── Toast card ── */
    const el = document.createElement('div');
    el.className = 'sonner-toast';
    el.setAttribute('role', 'status');
    el.style.background = p.bg;

    /* ── Container (flex-row, gap 24px) ── */
    const container = document.createElement('div');
    container.className = 'sonner-container';

    /* ── Content (flex-row, gap 16px) ── */
    const content = document.createElement('div');
    content.className = 'sonner-content';

    /* ── Title block: [Icon] [text] ── */
    const titleBlock = document.createElement('div');
    titleBlock.className = 'sonner-title-block';

    // Icon
    const iconEl = document.createElement('div');
    iconEl.className = 'sonner-icon';
    iconEl.style.color = textClr;
    iconEl.innerHTML = iconSvg;
    titleBlock.appendChild(iconEl);

    // Title text (bold prefix when title is provided, else just message)
    const titleEl = document.createElement('span');
    titleEl.className = 'sonner-title';
    titleEl.style.color = textClr;
    if (title) {
      titleEl.style.fontWeight = '600';
      titleEl.textContent = title;
    } else {
      titleEl.textContent = message;
    }
    titleBlock.appendChild(titleEl);

    content.appendChild(titleBlock);

    // Optional description after the title-block (when both title & message given)
    if (title && message) {
      const descEl = document.createElement('span');
      descEl.className = 'sonner-title';
      descEl.style.color = textClr;
      descEl.style.fontWeight = '400';
      descEl.textContent = message;
      content.appendChild(descEl);
    }

    // Optional link element (underline, white)
    if (link && link.label) {
      const linkEl = document.createElement('a');
      linkEl.className = 'sonner-link';
      linkEl.style.color = textClr;
      linkEl.textContent = link.label;
      if (link.href) {
        linkEl.href = link.href;
        linkEl.target = '_blank';
        linkEl.rel = 'noopener noreferrer';
      } else {
        linkEl.href = '#';
        linkEl.addEventListener('click', (e) => {
          e.preventDefault();
          link.onClick?.();
          dismiss(id);
        });
      }
      content.appendChild(linkEl);
    }

    container.appendChild(content);

    /* ── Actions (flex-row, justify-end, gap 16px) ── */
    const actions = document.createElement('div');
    actions.className = 'sonner-actions';

    // Optional action button (white outline)
    if (action && action.label) {
      const actionBtn = document.createElement('button');
      actionBtn.className = 'sonner-action-btn';
      actionBtn.style.color = textClr;
      actionBtn.style.borderColor = textClr;
      actionBtn.textContent = action.label;
      actionBtn.onclick = () => {
        action.onClick?.();
        dismiss(id);
      };
      actions.appendChild(actionBtn);
    }

    // Close button (32×32, icon 20×20)
    const closeBtn = document.createElement('button');
    closeBtn.className = 'sonner-close-btn';
    closeBtn.setAttribute('aria-label', 'Tutup notifikasi');
    closeBtn.style.color = textClr;
    closeBtn.innerHTML = ICONS.close;
    closeBtn.onclick = () => {
      dismiss(id);
      onDismiss?.();
    };
    actions.appendChild(closeBtn);

    container.appendChild(actions);
    el.appendChild(container);

    /* ── Progress bar ── */
    let progressEl = null;
    if (duration > 0 && type !== 'loading') {
      progressEl = document.createElement('div');
      progressEl.className = 'sonner-progress';
      progressEl.style.background = p.progress;
      progressEl.style.animationDuration = `${duration}ms`;
      el.appendChild(progressEl);
    }

    toaster.prepend(el);

    // Animate in (double-rAF for reliable first-frame)
    requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('sonner-visible')));

    // Auto-dismiss timer
    let timer = null;
    if (duration > 0 && type !== 'loading') {
      timer = setTimeout(() => dismiss(id), duration);
    }
    activeToasts.set(id, { el, timer });

    // Pause progress on hover
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
  function updateToast(id, message, type) {
    const entry = activeToasts.get(id);
    if (!entry) return;
    const { el } = entry;
    const p       = PALETTE[type] || PALETTE.default;
    const textClr = p.textColor   || '#ffffff';

    el.style.background = p.bg;

    const iconEl   = el.querySelector('.sonner-icon');
    const titleEl  = el.querySelector('.sonner-title');
    const closeBtn = el.querySelector('.sonner-close-btn');
    const actionBtn = el.querySelector('.sonner-action-btn');

    if (iconEl)    { iconEl.style.color = textClr; iconEl.innerHTML = ICONS[type] || ICONS.default; }
    if (titleEl)   { titleEl.style.color = textClr; titleEl.textContent = message; }
    if (closeBtn)  closeBtn.style.color = textClr;
    if (actionBtn) { actionBtn.style.color = textClr; actionBtn.style.borderColor = textClr; }

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
        updateToast(id, msg, 'success');
      })
      .catch((err) => {
        const msg = typeof errorMsg === 'function' ? errorMsg(err) : (errorMsg || 'Terjadi kesalahan.');
        updateToast(id, msg, 'error');
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
