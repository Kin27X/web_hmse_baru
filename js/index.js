// ================= CARD KEGIATAN: CAROUSEL + DOT (mobile) =================
const cardsContainer = document.querySelector(".cards");
const cardsDots = document.querySelectorAll("#cardsDots .dot");
const cardItems = document.querySelectorAll(".hero-card");

function updateCardsFocus(){

  if(!cardsContainer || cardItems.length === 0) return;

  const isScrollable = cardsContainer.scrollWidth > cardsContainer.clientWidth + 5;

  if(!isScrollable){
    cardItems.forEach(card=>{
      card.style.opacity = "";
      card.style.filter = "";
    });
    return;
  }

  const containerRect = cardsContainer.getBoundingClientRect();
  const containerCenter = containerRect.left + containerRect.width / 2;

  let closestIndex = 0;
  let closestDist = Infinity;

  cardItems.forEach((card, i)=>{

    const cardRect = card.getBoundingClientRect();
    const cardCenter = cardRect.left + cardRect.width / 2;
    const dist = Math.abs(cardCenter - containerCenter);

    if(dist < closestDist){
      closestDist = dist;
      closestIndex = i;
    }

    const ratio = Math.min(dist / (containerRect.width / 2), 1);
    card.style.opacity = String(1 - ratio * 0.65);
    card.style.filter = `brightness(${1 - ratio * 0.35})`;

  });

  cardsDots.forEach((dot, i)=> dot.classList.toggle("active", i === closestIndex));

}

function goToCard(index){
  const card = cardItems[index];
  if(!card || !cardsContainer) return;
  cardsContainer.scrollTo({
    left: card.offsetLeft - (cardsContainer.clientWidth - card.clientWidth) / 2,
    behavior:"smooth"
  });
}

if(cardsContainer && cardsDots.length){

  cardsContainer.addEventListener("scroll", ()=>{
    window.requestAnimationFrame(updateCardsFocus);
  });

  cardsDots.forEach((dot, i)=>{
    dot.addEventListener("click", ()=> goToCard(i));
  });

  window.addEventListener("load", ()=>{
    // Mulai dari kartu paling tengah
    const middleIndex = Math.floor(cardItems.length / 2);
    cardsContainer.scrollLeft =
      cardItems[middleIndex].offsetLeft -
      (cardsContainer.clientWidth - cardItems[middleIndex].clientWidth) / 2;
    updateCardsFocus();
  });

  window.addEventListener("resize", updateCardsFocus);

  let isDragging = false;
  let dragStartX = 0;
  let dragScrollStart = 0;

  cardsContainer.addEventListener("mousedown", (e)=>{
    isDragging = true;
    cardsContainer.classList.add("dragging");
    dragStartX = e.pageX - cardsContainer.offsetLeft;
    dragScrollStart = cardsContainer.scrollLeft;
  });

  window.addEventListener("mouseup", ()=>{
    if(!isDragging) return;
    isDragging = false;
    cardsContainer.classList.remove("dragging");
  });

  cardsContainer.addEventListener("mouseleave", ()=>{
    if(!isDragging) return;
    isDragging = false;
    cardsContainer.classList.remove("dragging");
  });

  cardsContainer.addEventListener("mousemove", (e)=>{
    if(!isDragging) return;
    e.preventDefault();
    const x = e.pageX - cardsContainer.offsetLeft;
    const walk = x - dragStartX;
    cardsContainer.scrollLeft = dragScrollStart - walk;
  });

}

// ================= CIRCUIT CHIP INTERAKTIF =================
const circuitWrap = document.getElementById("circuitWrap");
const circuitGlowPaths = document.querySelectorAll(".circuit-glow");
const circuitSparks = document.querySelectorAll(".circuit-spark");
const circuitSparkAnims = document.querySelectorAll(".circuit-sparks animateMotion");

circuitGlowPaths.forEach(path => {
  const len = path.getTotalLength();
  path.style.strokeDasharray = len;
  path.style.strokeDashoffset = len;
});

let circuitOffTimer;
const sparkTimers = [];

function fireCircuit(){

  clearTimeout(circuitOffTimer);
  sparkTimers.forEach(t => clearTimeout(t));

  circuitWrap.classList.remove("circuit-active");
  void circuitWrap.offsetWidth; // paksa reflow biar animasi bisa diulang

  circuitWrap.classList.add("circuit-active");

  circuitSparks.forEach((spark, i) => {

    const anim = circuitSparkAnims[i];
    const durMs = parseFloat(anim.getAttribute("dur")) * 1000;

    spark.style.opacity = "1";
    anim.beginElement();

    sparkTimers.push(setTimeout(()=>{
      spark.style.opacity = "0";
    }, durMs + 150));

  });

  // Otomatis padam lagi setelah sirkuit sempat menyala
  circuitOffTimer = setTimeout(()=>{
    circuitWrap.classList.remove("circuit-active");
  }, 1800);

}

if(circuitWrap){

  circuitWrap.addEventListener("click", fireCircuit);

  circuitWrap.addEventListener("keydown", (e)=>{
    if(e.key === "Enter" || e.key === " "){
      e.preventDefault();
      fireCircuit();
    }
  });

}

// ================= HAMBURGER / PANEL MENU =================
const hamburger = document.getElementById("hamburger");
const menu = document.querySelector(".menu");
const navbar = document.querySelector(".navbar");
const navbarWrap = document.getElementById("navbar");
const menuOverlay = document.getElementById("menuOverlay");
const menuClose = document.getElementById("menuClose");

function openMenu(){
  menu.classList.add("active");
  navbar.classList.add("expand");
  hamburger.classList.add("active");
  menuOverlay.classList.add("active");
}

function closeMenu(){
  menu.classList.remove("active");
  navbar.classList.remove("expand");
  hamburger.classList.remove("active");
  menuOverlay.classList.remove("active");
}

hamburger.addEventListener("click", ()=>{

  if(menu.classList.contains("active")){
    closeMenu();
  }else{
    openMenu();
  }

});

menuClose.addEventListener("click", closeMenu);
menuOverlay.addEventListener("click", closeMenu);

// ================= HIGHLIGHT ITEM YANG DIPILIH =================
const selectableItems = document.querySelectorAll(".menu .nav-li:not(.mobile-btn)");

selectableItems.forEach(item => {

  item.addEventListener("click", ()=>{

    selectableItems.forEach(other => other.classList.remove("current"));
    item.classList.add("current");

  });

});

// ================= NAVBAR HIDE ON SCROLL =================
let lastScrollY = window.scrollY;

window.addEventListener("scroll", ()=>{

  const currentScrollY = window.scrollY;

  if(menu.classList.contains("active")){
    lastScrollY = currentScrollY;
    return;
  }

  if(currentScrollY > lastScrollY && currentScrollY > 80){
    navbarWrap.classList.add("nav-hidden");
  }else{
    navbarWrap.classList.remove("nav-hidden");
  }

  lastScrollY = currentScrollY;

});

// ================= DROPDOWN MOBILE =================
const dropBtn = document.querySelector(".drop-btn");
const drop = document.querySelector(".drop");
const dropdown = document.querySelector(".dropdown");

dropBtn.addEventListener("click", function(e){

  e.stopPropagation();

  if(window.innerWidth <= 900){

    dropdown.classList.toggle("active");
    drop.classList.toggle("active");

  }

});

document.addEventListener("click", function(e){

  if(!drop.contains(e.target)){

    dropdown.classList.remove("active");
    drop.classList.remove("active");

  }

});

// Reveal On Scroll
const reveals = document.querySelectorAll('.reveal');
function revealOnScroll(){

  const windowHeight = window.innerHeight;

  reveals.forEach(el => {

    const elementTop = el.getBoundingClientRect().top;

    if(elementTop < windowHeight - 80){
      el.classList.add('active');
    }

  });

}

window.addEventListener('scroll', revealOnScroll);
revealOnScroll();

// FILOSOFI SCROLL (hex-container dan #hexBox adalah elemen yang sama)
const hexBox = document.getElementById('hexBox');

function revealHex(){

  const posisi = hexBox.getBoundingClientRect().top;
  const layar = window.innerHeight;

  if(posisi < layar - 150){
    hexBox.classList.add('show');
  }

}

window.addEventListener('scroll', revealHex);
revealHex();

// Cube section: klik/tap buat pause atau lanjutkan rotasi ambient-nya,
// perilakunya sama persis di semua device (gak ada lagi beda desktop/mobile)
const hmseCube = document.getElementById("hmseCube");
hmseCube.addEventListener("click", ()=>{
  hmseCube.classList.toggle("paused");
});