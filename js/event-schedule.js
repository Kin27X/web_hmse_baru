// ================= DATA EVENT (maks. 5, urut kronologis) =================
// Variabel `scheduleEvents` disiapkan oleh event-schedule.php (dari database)
// lewat <script> inline sebelum file ini di-load -- lihat definisinya di sana.

// Teks dari database di-escape dulu sebelum masuk innerHTML (anti-XSS).
function esEscape(value){
  return String(value ?? "").replace(/[&<>"']/g, ch => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  })[ch]);
}

// ================= RENDER: TIMELINE HORIZONTAL (DESKTOP) =================
const eventlineNodes = document.getElementById("eventlineNodes");
const eventlist = document.getElementById("eventlist");

if(eventlineNodes && eventlist){

  scheduleEvents.forEach((ev, i)=>{

    // -- node di garis waktu horizontal --
    const node = document.createElement("div");
    const posClass = i === 0 ? "node-top" : (i === scheduleEvents.length - 1 ? "node-bottom" : "node-mid");
    node.className = `eventline-node ${posClass}`;

    const stemHtml = posClass !== "node-mid" ? `<span class="eventline-stem"></span>` : "";
    const labelText = posClass === "node-mid" ? ev.dateShort : ev.dateFull;

    node.innerHTML = `
      <span class="eventline-label">${esEscape(labelText)}</span>
      <span class="eventline-dot"></span>
      ${stemHtml}
    `;
    eventlineNodes.appendChild(node);

    // -- baris detail di bawah timeline --
    const item = document.createElement("div");
    item.className = "eventlist-item";
    item.innerHTML = `
      <div class="eventlist-photo">
        ${ev.photo ? `<img src="${esEscape(ev.photo)}" alt="${esEscape(ev.name)}">` : `<i class="material-icons">event</i>`}
      </div>
      <div class="eventlist-text">
        <h3>${esEscape(ev.name)}</h3>
        <span>${esEscape(ev.place)} &middot; ${esEscape(ev.time)}</span>
      </div>
    `;
    eventlist.appendChild(item);

  });

}

// ================= RENDER: TIMELINE VERTIKAL + KARTU KLIK (MOBILE) =================
const esMobileWrap = document.getElementById("esMobileWrap");

if(esMobileWrap){

  scheduleEvents.forEach((ev, i)=>{

    const item = document.createElement("div");
    item.className = "eventline-m-item";
    item.innerHTML = `
      <div class="eventline-m-rail">
        <span class="m-dot"></span>
        <span class="m-line"></span>
      </div>
      <button class="eventline-m-card" type="button" data-index="${i}">
        <span class="m-name">${esEscape(ev.name)}</span>
        <span class="m-date">${esEscape(ev.dateFull)}</span>
      </button>
    `;
    esMobileWrap.appendChild(item);

  });

}

// ================= MODAL DETAIL EVENT (dipicu dari kartu mobile) =================
const eventModal = document.getElementById("eventModal");
const eventModalBackdrop = document.getElementById("eventModalBackdrop");
const eventModalClose = document.getElementById("eventModalClose");
const eventModalPhoto = document.getElementById("eventModalPhoto");
const eventModalDate = document.getElementById("eventModalDate");
const eventModalName = document.getElementById("eventModalName");
const eventModalMeta = document.getElementById("eventModalMeta");

function openEventModal(ev){

  eventModalPhoto.innerHTML = ev.photo
    ? `<img src="${esEscape(ev.photo)}" alt="${esEscape(ev.name)}">`
    : `<i class="material-icons">event</i>`;

  eventModalDate.textContent = ev.dateFull;
  eventModalName.textContent = ev.name;
  eventModalMeta.textContent = `${ev.place} \u00B7 ${ev.time}`;

  eventModal.classList.add("active");

}

function closeEventModal(){
  eventModal.classList.remove("active");
}

if(esMobileWrap && eventModal){

  esMobileWrap.addEventListener("click", (e)=>{
    const card = e.target.closest(".eventline-m-card");
    if(!card) return;
    const ev = scheduleEvents[Number(card.dataset.index)];
    if(ev) openEventModal(ev);
  });

  eventModalBackdrop.addEventListener("click", closeEventModal);
  eventModalClose.addEventListener("click", closeEventModal);

  document.addEventListener("keydown", (e)=>{
    if(e.key === "Escape") closeEventModal();
  });

}