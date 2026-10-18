// ================= DROPDOWN PERIODE (TEKNIK PORTAL) + SWITCH DATA TIMELINE =================
//
// Dropdown-nya (#periodeList) dipindah jadi anak langsung dari <body>
// lewat JS, lalu posisinya dihitung manual (getBoundingClientRect dari
// tombol) dan dipasang "position:fixed" + z-index sangat tinggi. Ini
// sengaja menghindari SELURUH masalah z-index/stacking-context CSS
// (mis. transform pada kartu di bawahnya diam-diam bikin stacking
// context baru yang menang) -- karena dropdown-nya sekarang benar-benar
// terpisah dari hierarki DOM/CSS section program kerja, jadi tidak ada
// lagi "kartu yang menang lawan dropdown".

const periodeSelect = document.getElementById("periodeSelect");
const periodeBtn = document.getElementById("periodeBtn");
const periodeLabel = document.getElementById("periodeLabel");
const periodeList = document.getElementById("periodeList");
const periodeItems = document.querySelectorAll("#periodeList li");
const prokerGroups = document.querySelectorAll(".proker-group");
const railTopLabel = document.querySelector(".rail-dot-top em");
const railBottomLabel = document.querySelector(".rail-dot-bottom em");

if(periodeSelect && periodeBtn && periodeList){

  // Pindahkan dropdown ke akhir <body>, keluar dari hierarki section
  // program-kerja sepenuhnya.
  document.body.appendChild(periodeList);
  periodeList.classList.add("periode-list-portal");

  function positionPeriodeList(){
    const rect = periodeBtn.getBoundingClientRect();
    const listWidth = Math.max(periodeList.offsetWidth, rect.width);
    let left = rect.left + rect.width / 2 - listWidth / 2;
    left = Math.max(12, Math.min(left, window.innerWidth - listWidth - 12));

    periodeList.style.left = `${left}px`;
    periodeList.style.top = `${rect.bottom + 10}px`;
    periodeList.style.minWidth = `${rect.width}px`;
  }

  function openPeriodeList(){
    positionPeriodeList();
    periodeList.classList.add("active");
    periodeSelect.classList.add("active");
    periodeBtn.setAttribute("aria-expanded", "true");
  }

  function closePeriodeList(){
    periodeList.classList.remove("active");
    periodeSelect.classList.remove("active");
    periodeBtn.setAttribute("aria-expanded", "false");
  }

  periodeBtn.addEventListener("click", (e)=>{
    e.stopPropagation();
    const willOpen = !periodeList.classList.contains("active");
    if(willOpen) openPeriodeList(); else closePeriodeList();
  });

  document.addEventListener("click", (e)=>{
    if(periodeList.contains(e.target)) return;
    closePeriodeList();
  });

  // Dropdown-nya sekarang position:fixed (relatif viewport) -- kalau
  // halaman di-scroll atau ukuran window berubah selagi terbuka,
  // posisinya bisa basi. Paling aman: tutup saja, biar user klik ulang
  // tombolnya (otomatis dihitung ulang posisinya waktu dibuka lagi).
  window.addEventListener("scroll", ()=>{
    if(periodeList.classList.contains("active")) closePeriodeList();
  }, { passive:true });
  window.addEventListener("resize", ()=>{
    if(periodeList.classList.contains("active")) closePeriodeList();
  });

  periodeItems.forEach(item=>{

    item.addEventListener("click", (e)=>{

      e.stopPropagation();

      const target = item.dataset.periode;

      // Update label tombol + state "active" di list
      periodeItems.forEach(i=> i.classList.remove("active"));
      item.classList.add("active");
      periodeLabel.textContent = item.textContent.trim();

      // Tampilkan cuma grup program yang sesuai periode terpilih
      prokerGroups.forEach(group=>{
        group.hidden = group.dataset.periodeGroup !== target;
      });

      // Update angka tahun di bulatan awal/akhir rail
      const [start, end] = target.split("-");
      if(railTopLabel) railTopLabel.textContent = start;
      if(railBottomLabel) railBottomLabel.textContent = end;

      closePeriodeList();

    });

  });

}