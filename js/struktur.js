// =========================================================
// STRUKTUR ORGANISASI — sepenuhnya dinamis.
// Variabel `orgData` (leaders/branches/divisions) disiapkan oleh
// struktur.php (dari database) lewat <script> inline sebelum file
// ini di-load. Menambah anggota/cabang/divisi baru di admin panel
// otomatis muncul di sini tanpa perlu ubah HTML/CSS/JS.
// =========================================================

// ================= LOOKUP id -> orang (dari orgData) =================
const orgPeople = {};

function registerPerson(p){
  orgPeople[p.id] = p;
  (p.members || []).forEach(registerPerson);
}
orgData.leaders.forEach(registerPerson);
orgData.branches.forEach(registerPerson);
orgData.divisions.forEach(registerPerson);


// =========================================================
// DESKTOP: bangun pohon struktur
// =========================================================
function makeCardEl(person, extraClass){
  // Kartu kategori (kepala cabang/divisi seperti "Sekretaris"/"Kominfo")
  // dibuat sebagai <div> polos, BUKAN <button> -- supaya tidak ke-bind
  // event hover/tap sama sekali (lihat bindCardEvents: query-nya cuma
  // button.org-card, jadi div di sini otomatis terlewat).
  const tag = person.isCategory ? "div" : "button";
  const el = document.createElement(tag);
  el.className = `org-card${extraClass ? " " + extraClass : ""}`;
  if(!person.isCategory) el.dataset.id = person.id;

  const roleHtml = person.label ? `<span class="org-role">${person.label}</span>` : "";
  const iconHtml = person.icon ? `<span class="org-icon"><i class="material-icons">${person.icon}</i></span>` : "";

  el.innerHTML = `${iconHtml}${roleHtml}<span class="org-name">${person.name}</span>`;
  return el;
}

function makeColumnEl(group){
  const col = document.createElement("div");
  col.className = "org-col";
  col.appendChild(makeCardEl(group, "org-card-head"));
  (group.members || []).forEach(m=>{
    col.appendChild(makeCardEl(m, "org-card-staff"));
  });
  return col;
}

// Render satu baris kolom (cabang ATAU divisi) + hitung & pasang garis
// penghubung horizontal secara otomatis -- termasuk kalau kolomnya
// wrap ke lebih dari satu baris visual (dikelompokkan berdasarkan
// offsetTop yang sama), supaya tetap presisi walau adminnya nambah
// cabang/divisi baru sebanyak apapun.
function renderRow(groups){
  const row = document.createElement("div");
  row.className = "org-row";

  groups.forEach(group=>{
    row.appendChild(makeColumnEl(group));
  });

  requestAnimationFrame(()=> positionConnectorBars(row));

  return row;
}

function positionConnectorBars(row){
  row.querySelectorAll(".org-connector-bar").forEach(el => el.remove());

  const cols = Array.from(row.querySelectorAll(":scope > .org-col"));
  if(cols.length === 0) return;

  const byTop = {};
  cols.forEach(col=>{
    const top = col.offsetTop;
    (byTop[top] = byTop[top] || []).push(col);
  });

  Object.values(byTop).forEach(rowCols=>{
    if(rowCols.length < 2) return;
    const first = rowCols[0];
    const last = rowCols[rowCols.length - 1];
    const bar = document.createElement("span");
    bar.className = "org-connector-bar";
    bar.style.top = (first.offsetTop - 26) + "px";
    bar.style.left = (first.offsetLeft + first.offsetWidth / 2) + "px";
    bar.style.width = (last.offsetLeft + last.offsetWidth / 2 - (first.offsetLeft + first.offsetWidth / 2)) + "px";
    row.appendChild(bar);
  });
}

function renderDesktopTree(){

  const root = document.getElementById("orgDesktop");
  if(!root) return;
  root.innerHTML = "";

  orgData.leaders.forEach(leader=>{
    const trunk = document.createElement("div");
    trunk.className = "org-trunk-single";
    root.appendChild(trunk);
    root.appendChild(makeCardEl(leader));
  });

  if(orgData.branches.length){
    root.appendChild(renderRow(orgData.branches));
  }

  if(orgData.divisions.length){
    const trunkWide = document.createElement("div");
    trunkWide.className = "org-trunk-single org-trunk-wide";
    root.appendChild(trunkWide);
    root.appendChild(renderRow(orgData.divisions));
  }

  bindCardEvents();
}


// =========================================================
// MOBILE: accordion per kelompok + foto
// =========================================================
function buildMobileGroups(){

  const groups = [];

  if(orgData.leaders.length){
    groups.push({ label: orgData.leaders[0].label || orgData.leaders[0].name, memberIds: [orgData.leaders[0].id] });
    const rest = orgData.leaders.slice(1);
    if(rest.length){
      groups.push({
        label: rest.map(l => l.label || l.name).join(" & "),
        memberIds: rest.map(l => l.id)
      });
    }
  }

  orgData.branches.forEach(b=>{
    const ids = [b.id, ...(b.members || []).map(m => m.id)]
      .filter(id => !orgPeople[id].isCategory);
    groups.push({ label: b.name, memberIds: ids });
  });

  orgData.divisions.forEach(d=>{
    const ids = [d.id, ...(d.members || []).map(m => m.id)]
      .filter(id => !orgPeople[id].isCategory);
    groups.push({ label: d.name, memberIds: ids });
  });

  return groups;
}

function renderMobileGroups(){

  const container = document.getElementById("orgMobileGroups");
  if(!container) return;

  const groups = buildMobileGroups();

  container.innerHTML = groups.map((group, gi)=>{

    const photosHtml = group.memberIds.map(id=>{
      const person = orgPeople[id];
      if(!person) return "";
      return `
        <button class="org-m-photo" data-id="${id}" aria-label="${person.name}">
          <span class="org-m-photo-inner">${buildAvatarHtml(person)}</span>
        </button>
      `;
    }).join("");

    return `
      <div class="org-m-group">
        <button class="org-m-group-header" data-group="${gi}">
          <span>${group.label}</span>
          <span class="material-icons org-m-chevron">arrow_drop_down</span>
        </button>
        <div class="org-m-photos-wrap">
          <div class="org-m-photos">${photosHtml}</div>
        </div>
      </div>
    `;

  }).join("");

  container.querySelectorAll(".org-m-group-header").forEach(header=>{
    header.addEventListener("click", ()=>{
      header.classList.toggle("active");
      header.nextElementSibling.classList.toggle("active");
    });
  });

  container.querySelectorAll(".org-m-photo").forEach(btn=>{
    btn.addEventListener("click", ()=>{
      const person = orgPeople[btn.dataset.id];
      if(person) openOrgModal(person);
    });
  });

}


// =========================================================
// ISI DETAIL (dipakai popover desktop & modal mobile)
// =========================================================
function initials(name){
  return name.split(" ").filter(Boolean).slice(0, 2).map(w => w[0]).join("").toUpperCase();
}
function buildAvatarHtml(person){
  return person.photo ? `<img src="${person.photo}" alt="${person.name}">` : initials(person.name);
}
function buildMetaHtml(person){
  const rows = [];
  if(person.nid)          rows.push(`<div class="meta-row"><span>NID</span><span>${person.nid}</span></div>`);
  if(person.masaJabatan)  rows.push(`<div class="meta-row"><span>Masa Jabatan</span><span>${person.masaJabatan}</span></div>`);
  if(person.npm)      rows.push(`<div class="meta-row"><span>NPM</span><span>${person.npm}</span></div>`);
  if(person.semester) rows.push(`<div class="meta-row"><span>Semester</span><span>${person.semester}</span></div>`);
  if(person.alamat)   rows.push(`<div class="meta-row"><span>Alamat</span><span>${person.alamat}</span></div>`);
  if(!person.nid && !person.masaJabatan && !person.npm && !person.semester && !person.alamat && !person.members){
    rows.push(`<div class="meta-empty">Belum ada detail tambahan.</div>`);
  }
  return rows.join("");
}
function buildLinksHtml(person){
  const links = [];
  if(person.whatsapp) links.push(`<a href="${buildWhatsappLinkJs(person.whatsapp)}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>`);
  if(person.instagram) links.push(`<a href="${buildInstagramLinkJs(person.instagram)}" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>`);
  if(person.email)     links.push(`<a href="mailto:${person.email}" aria-label="Email"><i class="material-icons">mail</i></a>`);
  return links.join("");
}
function buildWhatsappLinkJs(raw){
  let digits = raw.replace(/\D/g, "");
  if(digits.startsWith("0")) digits = "62" + digits.slice(1);
  return `https://wa.me/${digits}`;
}
function buildInstagramLinkJs(raw){
  return `https://instagram.com/${raw.replace(/^@/, "")}`;
}

// ================= POPOVER (HOVER, DESKTOP) =================
const orgPopover = document.getElementById("orgPopover");
const orgPopoverAvatar = document.getElementById("orgPopoverAvatar");
const orgPopoverName = document.getElementById("orgPopoverName");
const orgPopoverRole = document.getElementById("orgPopoverRole");
const orgPopoverMeta = document.getElementById("orgPopoverMeta");
const orgPopoverLinks = document.getElementById("orgPopoverLinks");

let popoverHideTimer = null;

function showPopover(card){
  const person = orgPeople[card.dataset.id];
  if(!person || person.isCategory) return;

  clearTimeout(popoverHideTimer);

  orgPopoverAvatar.innerHTML = buildAvatarHtml(person);
  orgPopoverName.textContent = person.name;
  orgPopoverRole.textContent = person.label || "";
  orgPopoverMeta.innerHTML = buildMetaHtml(person);
  orgPopoverLinks.innerHTML = buildLinksHtml(person);

  orgPopover.classList.add("active");

  const rect = card.getBoundingClientRect();
  const popoverWidth = orgPopover.offsetWidth || 360;
  const popoverHeight = orgPopover.offsetHeight || 150;
  const gap = 12;

  let left = rect.left + rect.width / 2 - popoverWidth / 2;
  left = Math.max(12, Math.min(left, window.innerWidth - popoverWidth - 12));

  let top = rect.bottom + gap;
  if(top + popoverHeight > window.innerHeight){
    top = rect.top - popoverHeight - gap;
  }

  orgPopover.style.left = `${left}px`;
  orgPopover.style.top = `${top}px`;
}

function scheduleHidePopover(){
  popoverHideTimer = setTimeout(()=> orgPopover.classList.remove("active"), 150);
}

function bindCardEvents(){
  document.querySelectorAll(".org-desktop button.org-card").forEach(card=>{
    card.addEventListener("mouseenter", ()=> showPopover(card));
    card.addEventListener("focus", ()=> showPopover(card));
    card.addEventListener("mouseleave", scheduleHidePopover);
    card.addEventListener("blur", scheduleHidePopover);
  });
}

if(orgPopover){
  orgPopover.addEventListener("mouseenter", ()=> clearTimeout(popoverHideTimer));
  orgPopover.addEventListener("mouseleave", scheduleHidePopover);
}

// ================= MODAL (TAP, MOBILE) =================
const orgModal = document.getElementById("orgModal");
const orgModalBackdrop = document.getElementById("orgModalBackdrop");
const orgModalClose = document.getElementById("orgModalClose");
const orgModalAvatar = document.getElementById("orgModalAvatar");
const orgModalName = document.getElementById("orgModalName");
const orgModalRole = document.getElementById("orgModalRole");
const orgModalMeta = document.getElementById("orgModalMeta");
const orgModalLinks = document.getElementById("orgModalLinks");

function openOrgModal(person){
  if(!person || person.isCategory) return;
  orgModalAvatar.innerHTML = buildAvatarHtml(person);
  orgModalName.textContent = person.name;
  orgModalRole.textContent = person.label || "";
  orgModalMeta.innerHTML = buildMetaHtml(person);
  orgModalLinks.innerHTML = buildLinksHtml(person);
  orgModal.classList.add("active");
}
function closeOrgModal(){
  orgModal.classList.remove("active");
}

if(orgModal){
  orgModalBackdrop.addEventListener("click", closeOrgModal);
  orgModalClose.addEventListener("click", closeOrgModal);
  document.addEventListener("keydown", (e)=>{ if(e.key === "Escape") closeOrgModal(); });
}

// =========================================================
// INIT
// =========================================================
renderDesktopTree();
renderMobileGroups();

let resizeDebounce;
window.addEventListener("resize", ()=>{
  clearTimeout(resizeDebounce);
  resizeDebounce = setTimeout(()=>{
    document.querySelectorAll(".org-row").forEach(positionConnectorBars);
  }, 150);
});