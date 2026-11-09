// ================= VOTING: pilih paslon + konfirmasi sebelum kirim =================
(function(){

  const form = document.getElementById("ballotForm");
  if(!form) return;

  const submitBtn = document.getElementById("ballotSubmit");
  const modal = document.getElementById("confirmModal");
  const labelEl = document.getElementById("confirmLabel");
  const namesEl = document.getElementById("confirmNames");
  const backBtn = document.getElementById("confirmBack");
  const sendBtn = document.getElementById("confirmSend");
  let confirmed = false;

  function selected(){
    return form.querySelector('input[name="choice"]:checked');
  }

  form.addEventListener("change", ()=>{
    submitBtn.disabled = !selected();
  });

  function openModal(){
    const choice = selected();
    if(!choice) return;
    labelEl.textContent = choice.dataset.label;
    namesEl.textContent = choice.dataset.names;
    modal.hidden = false;
    sendBtn.focus();
  }

  function closeModal(){
    modal.hidden = true;
    submitBtn.focus();
  }

  form.addEventListener("submit", (e)=>{
    if(confirmed) return;
    e.preventDefault();
    openModal();
  });

  backBtn.addEventListener("click", closeModal);
  modal.addEventListener("click", (e)=>{ if(e.target === modal) closeModal(); });
  document.addEventListener("keydown", (e)=>{ if(e.key === "Escape" && !modal.hidden) closeModal(); });

  sendBtn.addEventListener("click", ()=>{
    confirmed = true;
    // Kunci tombol supaya tidak terkirim dobel (server juga sudah menolak dobel)
    sendBtn.disabled = true;
    backBtn.disabled = true;
    sendBtn.textContent = "Mengirim...";
    form.submit();
  });

})();
