// =========================================================
// Helper bersama untuk news.js & news_detail.js
// (variabel `newsArticles` disiapkan news.php lewat <script> inline;
//  halaman detail tidak memakainya karena artikelnya dirender server)
// =========================================================

function escapeHtml(value){
  return String(value ?? "").replace(/[&<>"']/g, ch => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  }[ch]));
}

function getSortedArticles(){
  return [...newsArticles].sort((a, b) => new Date(b.dateISO) - new Date(a.dateISO));
}

// URL detail berita, dihitung relatif terhadap halaman yang sedang dibuka
function articleUrl(id){
  return new URL(`news_detail.php?id=${encodeURIComponent(id)}`, window.location.href).href;
}

function showToast(message){
  const old = document.getElementById("nwToast");
  if(old) old.remove();

  const el = document.createElement("div");
  el.id = "nwToast";
  el.setAttribute("role", "status");
  el.textContent = message;
  el.style.cssText = "position:fixed;left:50%;bottom:28px;transform:translateX(-50%);background:#1c2a44;color:#fff;padding:10px 18px;border-radius:999px;font-size:14px;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,.25);";
  document.body.appendChild(el);
  setTimeout(()=> el.remove(), 2200);
}

// navigator.clipboard hanya ada di HTTPS/localhost -> fallback ke execCommand untuk HTTP biasa
function copyToClipboard(text){
  if(navigator.clipboard && window.isSecureContext){
    return navigator.clipboard.writeText(text);
  }
  return new Promise((resolve, reject)=>{
    const ta = document.createElement("textarea");
    ta.value = text;
    ta.setAttribute("readonly", "");
    ta.style.cssText = "position:fixed;top:0;left:0;opacity:0;";
    document.body.appendChild(ta);
    ta.select();
    let ok = false;
    try{ ok = document.execCommand("copy"); }catch(e){ ok = false; }
    document.body.removeChild(ta);
    ok ? resolve() : reject();
  });
}

function shareLink(title, url){
  if(navigator.share){
    navigator.share({ title, url }).catch(()=>{});
    return;
  }
  copyToClipboard(url)
    .then(()=> showToast("Link berita disalin"))
    .catch(()=> window.prompt("Salin link berita ini:", url));
}

function shareArticle(article){
  shareLink(article.title, articleUrl(article.id));
}