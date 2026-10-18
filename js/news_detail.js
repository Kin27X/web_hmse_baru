// =========================================================
// NEWS DETAIL PAGE
// Artikel & "More Posts" sudah dirender server (news_detail.php).
// File ini hanya mengurus interaksi: tombol Share dan ganti foto utama
// lewat deretan thumbnail (untuk berita lama tanpa foto yang disisipkan).
// =========================================================

const ndShareBtn = document.getElementById("ndShareBtn");
if(ndShareBtn){
  ndShareBtn.addEventListener("click", ()=>{
    shareLink(ndShareBtn.dataset.title || document.title, window.location.href);
  });
}

const ndHeroPhoto = document.getElementById("ndHeroPhoto");
const ndThumbs = document.querySelectorAll(".nd-photo-thumb");

if(ndHeroPhoto && ndThumbs.length){
  ndThumbs.forEach(btn=>{
    btn.addEventListener("click", ()=>{
      ndHeroPhoto.src = btn.dataset.src;
      ndThumbs.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
    });
  });
}