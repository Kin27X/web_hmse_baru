// =========================================================
// GALLERY — flat, tanpa album. Variabel `galleryPhotos` disiapkan
// oleh gallery.php (dari database) lewat <script> inline sebelum
// file ini di-load. Tiap foto: { photo, category }.
// =========================================================

const categoryLabel = {
  olahraga: "Olahraga",
  belajar: "Belajar",
  event: "Event"
};

// ================= RENDER GRID =================
const glGrid = document.getElementById("glGrid");

function renderGrid(){

  glGrid.innerHTML = "";

  galleryPhotos.forEach((item, i)=>{

    const el = document.createElement("div");
    // Catatan: TIDAK pakai class "reveal reveal-up" -- elemen ini dibuat
    // SETELAH index.js sempat jalan duluan (revealOnScroll), jadi kalau
    // dikasih class itu bakal nyangkut permanen di opacity:0 (invisible).
    el.className = "gl-photo";
    el.dataset.category = item.category;
    el.dataset.index = i;

    el.innerHTML = `
      <img src="${item.photo}" alt="${categoryLabel[item.category] || item.category}" loading="lazy">
      <span class="gl-photo-category">${categoryLabel[item.category] || item.category}</span>
    `;

    glGrid.appendChild(el);

  });

  bindPhotoClicks();
}

// ================= FILTER KATEGORI =================
const glFilter = document.getElementById("glFilter");
let currentFilter = "semua";

if(glFilter){
  glFilter.querySelectorAll(".gl-filter-btn").forEach(btn=>{
    btn.addEventListener("click", ()=>{

      glFilter.querySelectorAll(".gl-filter-btn").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");

      currentFilter = btn.dataset.filter;

      document.querySelectorAll(".gl-photo").forEach(el=>{
        el.style.display = (currentFilter === "semua" || el.dataset.category === currentFilter) ? "" : "none";
      });

    });
  });
}

// Daftar index foto yang SEDANG TERLIHAT sesuai filter aktif -- dipakai
// biar tombol prev/next di lightbox cuma muter di antara foto yang lolos
// filter, bukan seluruh galeri.
function getVisiblePhotoIndexes(){
  if(currentFilter === "semua") return galleryPhotos.map((_, i) => i);
  return galleryPhotos
    .map((item, i) => item.category === currentFilter ? i : null)
    .filter(i => i !== null);
}

// ================= LIGHTBOX =================
const glLightbox = document.getElementById("glLightbox");
const glLightboxBackdrop = document.getElementById("glLightboxBackdrop");
const glLightboxClose = document.getElementById("glLightboxClose");
const glLightboxImg = document.getElementById("glLightboxImg");
const glLightboxAlbum = document.getElementById("glLightboxAlbum");
const glLightboxCount = document.getElementById("glLightboxCount");
const glLightboxPrev = document.getElementById("glLightboxPrev");
const glLightboxNext = document.getElementById("glLightboxNext");

let currentIndex = 0;

function openLightbox(index){
  currentIndex = index;
  updateLightbox();
  glLightbox.classList.add("active");
}

function updateLightbox(){
  const item = galleryPhotos[currentIndex];
  glLightboxImg.src = item.photo;
  glLightboxImg.alt = categoryLabel[item.category] || item.category;
  glLightboxAlbum.textContent = categoryLabel[item.category] || item.category;

  const visible = getVisiblePhotoIndexes();
  const posInVisible = visible.indexOf(currentIndex) + 1;
  glLightboxCount.textContent = `${posInVisible} / ${visible.length}`;

  const multi = visible.length > 1;
  glLightboxPrev.style.display = multi ? "flex" : "none";
  glLightboxNext.style.display = multi ? "flex" : "none";
}

function closeLightbox(){
  glLightbox.classList.remove("active");
}

function showPrev(){
  const visible = getVisiblePhotoIndexes();
  if(visible.length === 0) return;
  const pos = visible.indexOf(currentIndex);
  currentIndex = visible[(pos - 1 + visible.length) % visible.length];
  updateLightbox();
}
function showNext(){
  const visible = getVisiblePhotoIndexes();
  if(visible.length === 0) return;
  const pos = visible.indexOf(currentIndex);
  currentIndex = visible[(pos + 1) % visible.length];
  updateLightbox();
}

function bindPhotoClicks(){
  document.querySelectorAll(".gl-photo").forEach(el=>{
    el.addEventListener("click", ()=>{
      openLightbox(Number(el.dataset.index));
    });
  });
}

if(glLightbox){
  glLightboxBackdrop.addEventListener("click", closeLightbox);
  glLightboxClose.addEventListener("click", closeLightbox);
  glLightboxPrev.addEventListener("click", showPrev);
  glLightboxNext.addEventListener("click", showNext);

  document.addEventListener("keydown", (e)=>{
    if(!glLightbox.classList.contains("active")) return;
    if(e.key === "Escape") closeLightbox();
    if(e.key === "ArrowLeft") showPrev();
    if(e.key === "ArrowRight") showNext();
  });
}

// ================= INIT =================
renderGrid();