// ================= PENGHITUNGAN SUARA (layar proyektor) =================
// Tiap surat suara: keluar dari kotak -> lipatan dibuka -> lubang coblos
// muncul -> diumumkan -> terbang ke papan paslon -> 1 turus digambar.
(function(){

  const DATA = window.HITUNG;
  const ballots = DATA.ballots;
  const options = DATA.options;
  const optByKey = Object.fromEntries(options.map(o => [o.key, o]));

  const $ = (sel, root = document) => root.querySelector(sel);
  const ballot = $("#ballot");
  const flapTop = $(".ballot-flap-top");
  const flapBottom = $(".ballot-flap-bottom");
  const stamp = $(".ballot-stamp");
  const announce = $("#announce");
  const progCur = $("#progCur");
  const boxLeft = $("#boxLeft");
  const playBtn = $("#playBtn");
  const skipBtn = $("#skipBtn");
  const finalEl = $("#final");

  const cols = {};
  document.querySelectorAll(".board-col").forEach(col => {
    cols[col.dataset.key] = {
      el: col,
      tally: $(".board-tally", col),
      num: $(".board-num", col),
      pct: $(".board-pct", col),
    };
  });

  const counts = Object.fromEntries(options.map(o => [o.key, 0]));
  let idx = 0;
  let counted = 0;
  let busy = false; // true selama 1 surat suara sedang dibuka
  let skipRequested = false;
  let finished = false;
  let speed = window.matchMedia("(prefers-reduced-motion: reduce)").matches ? 4 : 1;

  // ---------- util animasi ----------
  const wait = ms => new Promise(r => setTimeout(r, ms / speed));
  const anim = (el, frames, ms, easing = "ease") =>
    el.animate(frames, { duration: ms / speed, easing, fill: "forwards" }).finished;

  // ---------- turus (lidi) ----------
  // 1 kelompok = 4 garis tegak + 1 garis miring, persis cara hitung manual.
  const SVG_NS = "http://www.w3.org/2000/svg";
  function newTallyGroup(){
    const svg = document.createElementNS(SVG_NS, "svg");
    svg.setAttribute("viewBox", "0 0 60 52");
    svg.classList.add("tally-group");
    const lines = [[10,6,10,46],[22,6,22,46],[34,6,34,46],[46,6,46,46],[3,40,55,12]];
    lines.forEach(([x1,y1,x2,y2]) => {
      const l = document.createElementNS(SVG_NS, "line");
      l.setAttribute("x1", x1); l.setAttribute("y1", y1);
      l.setAttribute("x2", x2); l.setAttribute("y2", y2);
      l.setAttribute("pathLength", "1");
      svg.appendChild(l);
    });
    return svg;
  }

  function addMark(key, instant){
    const col = cols[key];
    counts[key]++;
    counted++;
    const n = counts[key];
    const pos = (n - 1) % 5;
    if(pos === 0) col.tally.appendChild(newTallyGroup());
    const line = col.tally.lastElementChild.children[pos];
    if(instant) line.classList.add("instant");
    line.classList.add("on");

    col.num.textContent = n;
    if(!instant){
      col.num.classList.remove("bump"); void col.num.offsetWidth; col.num.classList.add("bump");
    }
    refreshStats();
  }

  function refreshStats(){
    const max = Math.max(...Object.values(counts));
    for(const key in cols){
      const c = counts[key];
      cols[key].pct.textContent = (counted ? Math.round(c / counted * 1000) / 10 : 0) + "%";
      cols[key].el.classList.toggle("leading", max > 0 && c === max);
    }
    if(progCur) progCur.textContent = counted;
    boxLeft.textContent = ballots.length - counted;
  }

  // ---------- satu surat suara ----------
  async function openBallot(key){
    const opt = optByKey[key];

    // Reset tampilan surat suara
    ballot.getAnimations().forEach(a => a.cancel());
    [flapTop, flapBottom, stamp].forEach(el => el.getAnimations().forEach(a => a.cancel()));
    ballot.querySelectorAll(".ballot-opt").forEach(b => {
      b.classList.toggle("is-coblos", b.dataset.key === key);
      b.querySelector(".ballot-hole").getAnimations().forEach(a => a.cancel());
    });
    announce.classList.remove("show");

    // 1. Keluar dari kotak suara
    await anim(ballot,
      [{ transform: "translateY(160px) scale(.5) rotate(-8deg)", opacity: 0 },
       { transform: "translateY(0) scale(1) rotate(0)", opacity: 1 }],
      480, "cubic-bezier(.2,.8,.25,1)");

    // 2. Lipatan dibuka
    await Promise.all([
      anim(flapTop, [{ transform: "rotateX(0deg)" }, { transform: "rotateX(180deg)" }], 560, "cubic-bezier(.5,0,.3,1)"),
      anim(flapBottom, [{ transform: "rotateX(0deg)" }, { transform: "rotateX(-180deg)" }], 560, "cubic-bezier(.5,0,.3,1)"),
    ]);

    // 3. Lubang coblos + stempel SAH
    const hole = ballot.querySelector(".ballot-opt.is-coblos .ballot-hole");
    await anim(hole, [{ transform: "scale(0)" }, { transform: "scale(1.35)" }, { transform: "scale(1)" }], 300, "ease-out");
    anim(stamp, [{ transform: "rotate(-14deg) scale(2.2)", opacity: 0 }, { transform: "rotate(-14deg) scale(1)", opacity: .9 }], 260, "ease-in");

    announce.innerHTML = `<small>Suara sah untuk</small><b>${escapeHtml(opt.label)}</b>`;
    announce.classList.add("show");
    await wait(650);

    // 4. Terbang ke papan paslon + gambar turus
    const from = ballot.getBoundingClientRect();
    const to = cols[key].tally.getBoundingClientRect();
    const dx = (to.left + to.width / 2) - (from.left + from.width / 2);
    const dy = (to.top + to.height / 2) - (from.top + from.height / 2);
    const fly = anim(ballot,
      [{ transform: "translate(0,0) scale(1)", opacity: 1 },
       { transform: `translate(${dx}px, ${dy}px) scale(.12) rotate(10deg)`, opacity: .2 }],
      560, "cubic-bezier(.55,0,.75,.4)");
    await wait(430);
    addMark(key, false);
    await fly;
    ballot.style.opacity = 0;
  }

  // ---------- kontrol ----------
  // Satu klik = satu surat suara. Setelah turusnya tercatat, berhenti
  // dan menunggu admin klik lagi.
  async function next(){
    if(busy || finished) return;
    busy = true;
    setPlayLabel();
    await openBallot(ballots[idx]);
    idx++;
    busy = false;
    if(skipRequested || idx >= ballots.length) fastForward();
    setPlayLabel();
  }

  function fastForward(){
    while(idx < ballots.length){
      addMark(ballots[idx], true);
      idx++;
    }
    ballot.getAnimations().forEach(a => a.cancel());
    ballot.style.opacity = 0;
    announce.classList.remove("show");
    finish();
  }

  function skip(){
    if(finished) return;
    skipRequested = true;
    if(!busy) fastForward(); // kalau sedang membuka, dilewati setelah surat suara itu selesai
  }

  function finish(){
    if(finished) return;
    finished = true;
    playBtn.disabled = true;
    skipBtn.disabled = true;
    setPlayLabel();

    const total = counted;
    const max = Math.max(...Object.values(counts));
    const winners = options.filter(o => counts[o.key] === max && max > 0);
    const titleEl = $("#finalTitle");
    const subEl = $("#finalSub");

    if(winners.length === 1){
      const w = winners[0];
      titleEl.textContent = w.kosong ? "Kotak Kosong unggul" : `${w.label} unggul`;
      subEl.textContent = (w.kosong ? "" : w.names + ", ") +
        `${max} dari ${total} suara (${Math.round(max / total * 1000) / 10}%)`;
      cols[w.key].el.classList.add("winner");
      setTimeout(confetti, 150);
    }else{
      titleEl.textContent = "Hasil imbang";
      subEl.textContent = winners.map(w => w.label).join(" & ") + ` sama-sama ${max} suara. Keputusan dikembalikan ke forum.`;
    }
    setTimeout(() => { finalEl.hidden = false; }, 700);
  }

  function setPlayLabel(){
    const icon = playBtn.querySelector(".material-icons");
    const text = playBtn.querySelector("span:last-child");
    playBtn.disabled = busy || finished;
    if(finished){ icon.textContent = "check"; text.textContent = "Selesai"; return; }
    if(busy){ icon.textContent = "hourglass_top"; text.textContent = "Membuka..."; return; }
    icon.textContent = "play_arrow";
    text.textContent = idx === 0 ? "Buka surat suara pertama" : `Buka surat suara ke-${idx + 1}`;
  }

  playBtn.addEventListener("click", next);
  skipBtn.addEventListener("click", skip);

  document.querySelectorAll(".speed button").forEach(btn => {
    if(Number(btn.dataset.speed) === speed) {
      document.querySelectorAll(".speed button").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
    }
    btn.addEventListener("click", () => {
      speed = Number(btn.dataset.speed);
      document.querySelectorAll(".speed button").forEach(b => b.classList.toggle("active", b === btn));
    });
  });

  const toggleFs = () => document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen();
  $("#fsBtn").addEventListener("click", toggleFs);
  $("#finalClose").addEventListener("click", () => { finalEl.hidden = true; });

  document.addEventListener("keydown", e => {
    if(e.repeat) return; // tombol ditahan tidak membuka banyak surat suara
    if(e.target instanceof Element && e.target.closest("button, input, form")) return;
    if(e.code === "Space" || e.key === "ArrowRight" || e.key === "Enter"){ e.preventDefault(); next(); }
    if(e.key === "f" || e.key === "F") toggleFs();
  });

  // ---------- konfeti ----------
  function confetti(){
    const box = $("#confetti");
    const colors = ["#8f7bff", "#5b3ff5", "#ffffff", "#ffd166", "#3ee08a"];
    for(let i = 0; i < 90; i++){
      const p = document.createElement("i");
      p.style.left = Math.random() * 100 + "vw";
      p.style.background = colors[i % colors.length];
      p.style.animationDelay = Math.random() * 0.8 + "s";
      p.style.animationDuration = 2.4 + Math.random() * 1.8 + "s";
      p.style.setProperty("--drift", (Math.random() * 200 - 100) + "px");
      p.style.setProperty("--spin", (Math.random() * 720 - 360) + "deg");
      box.appendChild(p);
    }
    setTimeout(() => { box.innerHTML = ""; }, 5000);
  }

  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, c => ({ "&":"&amp;", "<":"&lt;", ">":"&gt;", '"':"&quot;", "'":"&#39;" }[c]));
  }

  refreshStats();
  setPlayLabel();

})();
