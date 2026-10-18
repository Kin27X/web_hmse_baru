// ================= HERO: GRID MENYALA + RIAK =================
// Panggung kanan hero: grid kotak-kotak di <canvas>.
//  - kursor bergerak  -> kotak di sekitar kursor menyala, lalu memudar (jejak)
//  - klik / ketuk     -> riak melebar dari titik klik
//  - diam             -> percikan acak kecil supaya tetap "hidup"
// Ringan: grid digambar sekali ke layer cadangan, loop berhenti saat hero
// tidak terlihat / tab disembunyikan, dan mati total jika reduced-motion.
(function(){
  "use strict";

  const host   = document.getElementById("hhStageMain");
  const canvas = document.getElementById("hhCanvas");
  const hero   = document.getElementById("home");
  if(!host || !canvas || !hero) return;

  const ctx = canvas.getContext("2d");
  if(!ctx) return;

  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // ---------- pengaturan (aman diutak-atik) ----------
  const CELL          = 44;                      // ukuran kotak (px)
  const TRAIL_RADIUS  = 2;                       // seberapa lebar nyala di sekitar kursor (kotak)
  const FADE_SPEED    = 3.2;                     // makin besar -> jejak makin cepat hilang
  const RIPPLE_SPEED  = 560;                     // kecepatan riak (px/detik)
  const RIPPLE_LIFE   = 1.7;                     // umur riak (detik)
  const RIPPLE_RING   = CELL * 1.8;              // tebal cincin riak
  const MAX_RIPPLES   = 5;
  const IDLE_EVERY    = 900;                     // percikan acak tiap ... ms
  const LINE_COLOR    = "rgba(143,123,255,0.16)";
  const FILL_COLOR    = "#6e5aff";
  const EDGE_COLOR    = "#bfb0ff";

  // ---------- state ----------
  let w = 0, h = 0, dpr = 1, cols = 0, rows = 0;
  let heat = new Float32Array(0);               // 0..1 tingkat nyala tiap kotak
  const gridLayer = document.createElement("canvas");
  const gctx = gridLayer.getContext("2d");

  const pointer = { inside:false, x:0, y:0, tx:0, ty:0, lx:0, ly:0, spot:0 };
  const ripples = [];

  let running = false;
  let rafId = 0;
  let last = 0;
  let lastIdle = 0;
  let heroVisible = true;

  // ---------- ukuran & layer grid ----------
  function buildGridLayer(){
    gridLayer.width  = Math.round(w * dpr);
    gridLayer.height = Math.round(h * dpr);
    gctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    gctx.clearRect(0, 0, w, h);
    gctx.strokeStyle = LINE_COLOR;
    gctx.lineWidth = 1;
    gctx.beginPath();
    for(let c = 0; c <= cols; c++){
      const x = c * CELL + 0.5;
      gctx.moveTo(x, 0);
      gctx.lineTo(x, h);
    }
    for(let r = 0; r <= rows; r++){
      const y = r * CELL + 0.5;
      gctx.moveTo(0, y);
      gctx.lineTo(w, y);
    }
    gctx.stroke();
  }

  function resize(){
    const rect = host.getBoundingClientRect();
    const nw = Math.round(rect.width);
    const nh = Math.round(rect.height);
    if(nw < 2 || nh < 2) return;

    w = nw;
    h = nh;
    dpr = Math.min(window.devicePixelRatio || 1, 2);

    canvas.width  = Math.round(w * dpr);
    canvas.height = Math.round(h * dpr);

    cols = Math.ceil(w / CELL);
    rows = Math.ceil(h / CELL);
    heat = new Float32Array(cols * rows);

    buildGridLayer();
    drawStatic();
  }

  function drawStatic(){
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, w, h);
    ctx.drawImage(gridLayer, 0, 0, w, h);
  }

  // ---------- menyalakan kotak ----------
  function lightCell(c, r, v){
    if(c < 0 || r < 0 || c >= cols || r >= rows) return;
    const i = r * cols + c;
    if(v > heat[i]) heat[i] = v;
  }

  function lightAround(x, y){
    const c0 = Math.floor(x / CELL);
    const r0 = Math.floor(y / CELL);
    for(let dr = -TRAIL_RADIUS; dr <= TRAIL_RADIUS; dr++){
      for(let dc = -TRAIL_RADIUS; dc <= TRAIL_RADIUS; dc++){
        const d = Math.hypot(dc, dr);
        if(d > TRAIL_RADIUS) continue;
        lightCell(c0 + dc, r0 + dr, 1 - d / (TRAIL_RADIUS + 0.7));
      }
    }
  }

  // kursor yang bergerak cepat tidak boleh meninggalkan lubang di jejaknya
  function lightLine(x0, y0, x1, y1){
    const dist  = Math.hypot(x1 - x0, y1 - y0);
    const steps = Math.max(1, Math.ceil(dist / (CELL * 0.5)));
    for(let s = 1; s <= steps; s++){
      const t = s / steps;
      lightAround(x0 + (x1 - x0) * t, y0 + (y1 - y0) * t);
    }
  }

  function addRipple(x, y, delay){
    if(ripples.length >= MAX_RIPPLES) ripples.shift();
    ripples.push({ x:x, y:y, t0:performance.now() + (delay || 0) });
  }

  function applyRipples(now){
    for(let n = ripples.length - 1; n >= 0; n--){
      const rp  = ripples[n];
      const age = (now - rp.t0) / 1000;
      if(age < 0) continue;                       // belum mulai (delay)
      if(age > RIPPLE_LIFE){ ripples.splice(n, 1); continue; }

      const radius   = age * RIPPLE_SPEED;
      const strength = 1 - age / RIPPLE_LIFE;
      const reach    = radius + RIPPLE_RING;

      const minC = Math.max(0, Math.floor((rp.x - reach) / CELL));
      const maxC = Math.min(cols - 1, Math.floor((rp.x + reach) / CELL));
      const minR = Math.max(0, Math.floor((rp.y - reach) / CELL));
      const maxR = Math.min(rows - 1, Math.floor((rp.y + reach) / CELL));

      for(let r = minR; r <= maxR; r++){
        for(let c = minC; c <= maxC; c++){
          const cx   = (c + 0.5) * CELL;
          const cy   = (r + 0.5) * CELL;
          const diff = Math.abs(Math.hypot(cx - rp.x, cy - rp.y) - radius);
          if(diff < RIPPLE_RING){
            lightCell(c, r, (1 - diff / RIPPLE_RING) * strength);
          }
        }
      }
    }
  }

  function idleSpark(){
    if(!cols || !rows) return;
    const c = Math.floor(Math.random() * cols);
    const r = Math.floor(Math.random() * rows);
    lightCell(c, r, 0.75);
    if(Math.random() < 0.35){
      lightCell(c + 1, r, 0.4);
      lightCell(c, r + 1, 0.4);
    }
  }

  // ---------- loop gambar ----------
  function frame(now){
    rafId = 0;
    if(!running) return;

    const dt = Math.min((now - last) / 1000 || 0.016, 0.05);
    last = now;

    if(now - lastIdle > IDLE_EVERY){
      lastIdle = now;
      idleSpark();
    }
    applyRipples(now);

    // posisi kursor dihaluskan (lerp) supaya sorotan mengalir, tidak loncat
    pointer.x += (pointer.tx - pointer.x) * Math.min(1, dt * 14);
    pointer.y += (pointer.ty - pointer.y) * Math.min(1, dt * 14);
    pointer.spot += ((pointer.inside ? 1 : 0) - pointer.spot) * Math.min(1, dt * 8);

    // pudarkan
    const k = Math.exp(-FADE_SPEED * dt);
    for(let i = 0; i < heat.length; i++){
      if(heat[i] > 0){
        heat[i] *= k;
        if(heat[i] < 0.012) heat[i] = 0;
      }
    }

    // gambar
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, w, h);
    ctx.globalAlpha = 1;
    ctx.drawImage(gridLayer, 0, 0, w, h);

    // sorotan lembut di bawah kursor
    if(pointer.spot > 0.02){
      const g = ctx.createRadialGradient(pointer.x, pointer.y, 0, pointer.x, pointer.y, 180);
      g.addColorStop(0, "rgba(143,123,255,0.26)");
      g.addColorStop(1, "rgba(143,123,255,0)");
      ctx.globalAlpha = pointer.spot;
      ctx.fillStyle = g;
      ctx.fillRect(pointer.x - 180, pointer.y - 180, 360, 360);
      ctx.globalAlpha = 1;
    }

    // kotak yang menyala: isi dulu, lalu garis tepi
    ctx.fillStyle = FILL_COLOR;
    for(let r = 0; r < rows; r++){
      for(let c = 0; c < cols; c++){
        const a = heat[r * cols + c];
        if(a <= 0) continue;
        ctx.globalAlpha = a * 0.32;
        ctx.fillRect(c * CELL + 1, r * CELL + 1, CELL - 1, CELL - 1);
      }
    }
    ctx.strokeStyle = EDGE_COLOR;
    ctx.lineWidth = 1;
    for(let r = 0; r < rows; r++){
      for(let c = 0; c < cols; c++){
        const a = heat[r * cols + c];
        if(a <= 0) continue;
        ctx.globalAlpha = a * 0.85;
        ctx.strokeRect(c * CELL + 0.5, r * CELL + 0.5, CELL, CELL);
      }
    }
    ctx.globalAlpha = 1;

    rafId = requestAnimationFrame(frame);
  }

  function start(){
    if(running || reduceMotion || !heroVisible || document.hidden) return;
    running = true;
    last = performance.now();
    rafId = requestAnimationFrame(frame);
  }

  function stop(){
    running = false;
    if(rafId){ cancelAnimationFrame(rafId); rafId = 0; }
  }

  // ---------- input ----------
  function localPoint(e){
    const rect = host.getBoundingClientRect();
    return { x: e.clientX - rect.left, y: e.clientY - rect.top };
  }

  function onMove(e){
    const p = localPoint(e);
    pointer.tx = p.x;
    pointer.ty = p.y;
    if(!pointer.inside){
      pointer.inside = true;
      pointer.x = p.x;
      pointer.y = p.y;
      lightAround(p.x, p.y);
    }else{
      lightLine(pointer.lx, pointer.ly, p.x, p.y);
    }
    pointer.lx = p.x;
    pointer.ly = p.y;
  }

  function onLeave(){
    pointer.inside = false;
  }

  function onDown(e){
    const p = localPoint(e);
    pointer.tx = p.x;
    pointer.ty = p.y;
    pointer.lx = p.x;
    pointer.ly = p.y;
    lightAround(p.x, p.y);
    addRipple(p.x, p.y, 0);
  }

  // ---------- init ----------
  host.classList.add("is-live");
  resize();

  if(typeof ResizeObserver !== "undefined"){
    new ResizeObserver(resize).observe(host);
  }else{
    window.addEventListener("resize", resize);
  }

  if(reduceMotion) return;                         // grid statis saja

  host.addEventListener("pointermove",   onMove);
  host.addEventListener("pointerdown",   onDown);
  host.addEventListener("pointerleave",  onLeave);
  host.addEventListener("pointercancel", onLeave);

  if(typeof IntersectionObserver !== "undefined"){
    new IntersectionObserver((entries)=>{
      heroVisible = entries[0].isIntersecting;
      if(heroVisible) start(); else stop();
    }, { threshold:0.05 }).observe(hero);
  }

  document.addEventListener("visibilitychange", ()=>{
    if(document.hidden) stop(); else start();
  });

  // salam pembuka: satu riak dari tengah panggung
  addRipple(w * 0.45, h * 0.5, 450);

  start();
})();