  </div>
  </main>
</div>

<!-- ===== TIPSEEN CONFIRM DIALOG (Global) ===== -->
<style>
[hidden]{display:none!important}
.tipseen-overlay{position:fixed;inset:0;z-index:99998;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.35);opacity:0;visibility:hidden;transition:opacity .2s ease,visibility .2s ease}
.tipseen-overlay.active{opacity:1;visibility:visible}
.tipseen{position:relative;display:flex;flex-direction:column;align-items:center;padding:0;width:288px;background:#323338;box-shadow:0px 6px 20px rgba(0,0,0,.2);border-radius:8px;isolation:isolate;transform:scale(.88);transition:transform .22s cubic-bezier(.16,1,.3,1)}
.tipseen-overlay.active .tipseen{transform:scale(1)}
.tipseen-content{display:flex;flex-direction:column;align-items:flex-start;padding:0 20px 24px;width:100%;box-sizing:border-box}
.tipseen-text{display:flex;flex-direction:column;justify-content:center;align-items:flex-start;padding:16px 0 20px;gap:4px;align-self:stretch}
.tipseen-title{font-family:'Figtree',sans-serif;font-weight:700;font-size:16px;line-height:22px;color:#fff;align-self:stretch}
.tipseen-message{font-family:'Figtree',sans-serif;font-weight:400;font-size:14px;line-height:20px;color:#fff;align-self:stretch}
.tipseen-footer{display:flex;flex-direction:row;justify-content:flex-end;align-items:center;gap:8px;align-self:stretch}
.tipseen-btn-back{display:flex;align-items:center;justify-content:center;padding:0 8px;height:32px;border-radius:4px;border:none;background:transparent;color:#fff;font-family:'Figtree',inherit;font-size:14px;line-height:20px;cursor:pointer;transition:background .15s}
.tipseen-btn-back:hover{background:rgba(255,255,255,.12)}
.tipseen-btn-confirm{display:flex;align-items:center;justify-content:center;padding:0 8px;height:32px;background:#fff;border-radius:4px;border:none;font-family:'Figtree',inherit;font-size:14px;line-height:20px;color:#323338;cursor:pointer;transition:opacity .15s}
.tipseen-btn-confirm:hover{opacity:.88}
.tipseen-close{position:absolute;top:8px;right:8px;display:flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:4px;border:none;background:transparent;color:#676879;cursor:pointer;padding:0;transition:color .15s;z-index:1}
.tipseen-close:hover{color:#fff}
</style>

<div class="tipseen-overlay" id="tipseenOverlay" role="alertdialog" aria-modal="true"
     aria-labelledby="tipseenTitle" aria-describedby="tipseenMessage">
  <div class="tipseen" id="tipseenBox">
    <button class="tipseen-close" id="tipseenClose" aria-label="Tutup">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
      </svg>
    </button>
    <div class="tipseen-content">
      <div class="tipseen-text">
        <div class="tipseen-title" id="tipseenTitle">Konfirmasi</div>
        <div class="tipseen-message" id="tipseenMessage">Apakah Anda yakin?</div>
      </div>
      <div class="tipseen-footer">
        <button class="tipseen-btn-back" id="tipseenBtnBack">Batal</button>
        <button class="tipseen-btn-confirm" id="tipseenBtnConfirm">OK</button>
      </div>
    </div>
  </div>
</div>

<script>
/**
 * customConfirm — Tipseen Figma Vibe component, mengganti browser confirm()
 * @param {string}   title        - Judul popup
 * @param {string}   message      - Pesan popup
 * @param {Function} onConfirm    - Callback jika dikonfirmasi
 * @param {string}   [confirmLabel='OK'] - Label tombol konfirmasi
 */
function customConfirm(title, message, onConfirm, confirmLabel = 'OK') {
  const overlay   = document.getElementById('tipseenOverlay');
  const elTitle   = document.getElementById('tipseenTitle');
  const elMsg     = document.getElementById('tipseenMessage');
  const btnOk     = document.getElementById('tipseenBtnConfirm');
  const btnCancel = document.getElementById('tipseenBtnBack');
  const btnClose  = document.getElementById('tipseenClose');

  elTitle.textContent = title;
  elMsg.textContent   = message;
  btnOk.textContent   = confirmLabel;

  overlay.classList.add('active');
  setTimeout(() => btnOk.focus(), 50);

  function close() {
    overlay.classList.remove('active');
    btnOk.onclick     = null;
    btnCancel.onclick = null;
    btnClose.onclick  = null;
    overlay.onclick   = null;
    document.removeEventListener('keydown', onKey);
  }
  function onKey(e) { if (e.key === 'Escape') close(); }

  btnOk.onclick     = () => { close(); onConfirm(); };
  btnCancel.onclick = close;
  btnClose.onclick  = close;
  overlay.onclick   = (e) => { if (e.target === overlay) close(); };
  document.addEventListener('keydown', onKey);
}
</script>

</body>
</html>
