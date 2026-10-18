// =========================================================
// NEWS PAGE (daftar) — logic tampilan
// Data & helper dari news-data.js (newsArticles, escapeHtml, shareArticle, ...).
//
// Aturan jumlah item per halaman:
// - Mode LIST : 8 berita per halaman (desktop maupun mobile)
// - Mode GRID : 20 per halaman di desktop (4 kolom x 5 baris),
//               10 per halaman di mobile (2 kolom x 5 baris)
//
// Halaman, mode, dan kata pencarian disimpan di URL
// (?page=2&mode=grid&q=kata) supaya tombol Back dari halaman detail
// kembali ke posisi yang sama.
// =========================================================

const nwViewToggle = document.getElementById("nwViewToggle");
const nwList = document.getElementById("nwList");
const nwGrid = document.getElementById("nwGrid");
const nwEmpty = document.getElementById("nwEmpty");
const nwSearchInput = document.getElementById("nwSearchInput");
const nwPrevBtn = document.getElementById("nwPrevBtn");
const nwNextBtn = document.getElementById("nwNextBtn");
const nwPageLabel = document.getElementById("nwPageLabel");

const MOBILE_BREAKPOINT = 700;

function isMobile(){
  return window.innerWidth <= MOBILE_BREAKPOINT;
}

function itemsPerPage(mobile = isMobile()){
  if(currentMode === "list") return 8;
  return mobile ? 10 : 20; // grid: 2x5 mobile, 4x5 desktop
}

// ---------- state awal (dibaca dari URL) ----------
const urlParams = new URLSearchParams(window.location.search);
let currentMode = urlParams.get("mode") === "grid" ? "grid" : "list"; // "list" | "grid"
let currentPage = Math.max(1, parseInt(urlParams.get("page"), 10) || 1);
let searchTerm = (urlParams.get("q") || "").trim();
let wasMobile = isMobile();

function syncUrl(){
  const p = new URLSearchParams();
  if(currentMode !== "list") p.set("mode", currentMode);
  if(currentPage > 1) p.set("page", currentPage);
  if(searchTerm) p.set("q", searchTerm);
  const qs = p.toString();
  history.replaceState(null, "", window.location.pathname + (qs ? "?" + qs : ""));
}

function getFilteredArticles(){
  const all = getSortedArticles();
  if(!searchTerm) return all;
  const q = searchTerm.toLowerCase();
  return all.filter(a =>
    a.title.toLowerCase().includes(q) ||
    (a.search || a.excerpt || "").toLowerCase().includes(q)
  );
}

function renderListItem(article){
  const el = document.createElement("div");
  el.className = "nw-list-item";
  el.innerHTML = `
    <div class="nw-list-thumb">
      <img src="${escapeHtml(article.thumbnail)}" alt="${escapeHtml(article.title)}" loading="lazy">
    </div>
    <div class="nw-list-body">
      <span class="nw-list-title">${escapeHtml(article.title)}</span>
      <span class="nw-list-date">${escapeHtml(article.dateDisplay)}</span>
      <p class="nw-list-excerpt">${escapeHtml(article.excerpt)}</p>
      <button class="nw-share-btn" type="button">Share...</button>
    </div>
  `;
  el.addEventListener("click", (e)=>{
    if(e.target.closest(".nw-share-btn")) return; // tombol share tidak ikut buka detail
    goToDetail(article.id);
  });
  el.querySelector(".nw-share-btn").addEventListener("click", (e)=>{
    e.stopPropagation();
    shareArticle(article);
  });
  return el;
}

function renderGridItem(article){
  const el = document.createElement("div");
  el.className = "nw-grid-item";
  el.innerHTML = `
    <div class="nw-grid-thumb">
      <img src="${escapeHtml(article.thumbnail)}" alt="${escapeHtml(article.title)}" loading="lazy">
    </div>
    <div class="nw-grid-info">
      <div class="nw-grid-title">${escapeHtml(article.title)}</div>
      <div class="nw-grid-date">${escapeHtml(article.dateDay)}</div>
    </div>
  `;
  el.addEventListener("click", ()=> goToDetail(article.id));
  return el;
}

function goToDetail(id){
  window.location.href = articleUrl(id);
}

function renderPage(){

  const filtered = getFilteredArticles();
  const perPage = itemsPerPage();
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));

  currentPage = Math.min(Math.max(1, currentPage), totalPages);
  const start = (currentPage - 1) * perPage;
  const pageItems = filtered.slice(start, start + perPage);

  nwList.innerHTML = "";
  nwGrid.innerHTML = "";

  if(filtered.length === 0){
    nwEmpty.hidden = false;
  }else{
    nwEmpty.hidden = true;
    pageItems.forEach(article=>{
      if(currentMode === "list"){
        nwList.appendChild(renderListItem(article));
      }else{
        nwGrid.appendChild(renderGridItem(article));
      }
    });
  }

  nwList.hidden = currentMode !== "list";
  nwGrid.hidden = currentMode !== "grid";

  nwPageLabel.textContent = `Halaman ${currentPage} / ${totalPages}`;
  nwPrevBtn.disabled = currentPage <= 1;
  nwNextBtn.disabled = currentPage >= totalPages;

  syncUrl();
}

// ================= TOGGLE MODE =================
function setToggleActive(){
  nwViewToggle.querySelectorAll("button").forEach(b=>{
    b.classList.toggle("active", b.dataset.mode === currentMode);
  });
}

nwViewToggle.querySelectorAll("button").forEach(btn=>{
  btn.addEventListener("click", ()=>{
    currentMode = btn.dataset.mode;
    currentPage = 1;
    setToggleActive();
    renderPage();
  });
});

// ================= SEARCH =================
let searchDebounce;
nwSearchInput.addEventListener("input", ()=>{
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(()=>{
    searchTerm = nwSearchInput.value.trim();
    currentPage = 1;
    renderPage();
  }, 200);
});

// ================= PAGINATION =================
nwPrevBtn.addEventListener("click", ()=>{
  if(currentPage > 1){ currentPage--; renderPage(); }
});
nwNextBtn.addEventListener("click", ()=>{
  currentPage++; renderPage(); // renderPage membatasi ke halaman terakhir
});

// Resize: di HP, event resize ikut terpicu saat address bar muncul/hilang
// ketika scroll. Jadi render ulang HANYA kalau status mobile<->desktop benar-benar
// berubah, dan posisi item pertama dipertahankan (tidak dilempar ke halaman 1).
let resizeDebounce;
window.addEventListener("resize", ()=>{
  clearTimeout(resizeDebounce);
  resizeDebounce = setTimeout(()=>{
    const nowMobile = isMobile();
    if(nowMobile === wasMobile) return;

    const firstIndex = (currentPage - 1) * itemsPerPage(wasMobile);
    wasMobile = nowMobile;
    currentPage = Math.floor(firstIndex / itemsPerPage(nowMobile)) + 1;
    renderPage();
  }, 200);
});

// ================= INIT =================
nwSearchInput.value = searchTerm;
setToggleActive();
renderPage();