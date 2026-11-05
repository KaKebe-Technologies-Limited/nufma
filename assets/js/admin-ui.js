/* Admin UI: command palette (Ctrl/Cmd+K), popovers, table tidy-ups. */
(function(){
  "use strict";
  var $ = function(s, r){ return (r || document).querySelector(s); };

  /* popovers */
  function pop(btnId, popId){
    var b = document.getElementById(btnId), p = document.getElementById(popId); if(!b || !p) return;
    b.addEventListener("click", function(e){ e.stopPropagation(); var open = p.hidden; document.querySelectorAll(".ax-pop").forEach(function(x){ x.hidden = true; }); p.hidden = !open; });
  }
  pop("axNewBtn", "axNew"); pop("axBellBtn", "axBell");
  document.addEventListener("click", function(e){ if(!e.target.closest(".ax-wrap")) document.querySelectorAll(".ax-pop").forEach(function(x){ x.hidden = true; }); });

  /* command palette */
  var cmd = document.getElementById("axCmd"), inp = document.getElementById("axCmdInput"), list = document.getElementById("axCmdList");
  var items = window.NUFA_CMD || [], sel = 0, shown = [];
  function esc(s){ return String(s).replace(/[&<>"]/g, function(c){ return {"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]; }); }
  function render(){
    var q = inp.value.trim().toLowerCase(); var res = items.filter(function(i){ return !q || (i[0] + " " + i[2]).toLowerCase().indexOf(q) !== -1; });
    res.sort(function(a, b){ if(!q) return 0; return (a[0].toLowerCase().indexOf(q) === 0 ? 0 : 1) - (b[0].toLowerCase().indexOf(q) === 0 ? 0 : 1); });
    shown = res.slice(0, 40); if(sel >= shown.length) sel = 0;
    var html = "", last = "";
    shown.forEach(function(i, n){ if(i[2] !== last){ html += "<h5>" + esc(i[2]) + "</h5>"; last = i[2]; } html += '<a href="' + esc(i[1]) + '" data-n="' + n + '" class="' + (n === sel ? "sel" : "") + '">' + esc(i[0]) + "</a>"; });
    list.innerHTML = html || '<p style="padding:16px;color:#757575;margin:0">Nothing found.</p>';
  }
  function open(){ if(!cmd) return; cmd.hidden = false; inp.value = ""; sel = 0; render(); setTimeout(function(){ inp.focus(); }, 20); }
  function close(){ if(cmd) cmd.hidden = true; }
  var s = document.getElementById("axSearch"); if(s) s.addEventListener("click", open);
  if(cmd){
    cmd.addEventListener("click", function(e){ if(e.target === cmd) close(); });
    inp.addEventListener("input", function(){ sel = 0; render(); });
    inp.addEventListener("keydown", function(e){
      if(e.key === "ArrowDown"){ e.preventDefault(); sel = Math.min(shown.length - 1, sel + 1); render(); var a = list.querySelector(".sel"); if(a) a.scrollIntoView({block: "nearest"}); }
      else if(e.key === "ArrowUp"){ e.preventDefault(); sel = Math.max(0, sel - 1); render(); var a2 = list.querySelector(".sel"); if(a2) a2.scrollIntoView({block: "nearest"}); }
      else if(e.key === "Enter" && shown[sel]){ e.preventDefault(); location.href = shown[sel][1]; }
    });
  }
  document.addEventListener("keydown", function(e){
    if((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k"){ e.preventDefault(); open(); }
    else if(e.key === "Escape") close();
    else if(e.key === "/" && !/input|textarea|select/i.test((document.activeElement || {}).tagName || "") && !(document.activeElement || {}).isContentEditable){ e.preventDefault(); open(); }
  });

  /* tables: keep dates on one line and let wide tables scroll on small screens */
  document.querySelectorAll("table.admin-table").forEach(function(t){
    t.querySelectorAll("td").forEach(function(td){ if(/^\s*\d{1,2} [A-Z][a-z]{2} \d{4}(\s|$)/.test(td.textContent) && td.textContent.trim().length < 24) td.classList.add("is-date"); });
    if(!t.parentElement.classList.contains("admin-table-wrap") && !t.closest("form")){ var w = document.createElement("div"); w.className = "admin-table-wrap"; t.parentNode.insertBefore(w, t); w.appendChild(t); }
  });
})();

/* Sidebar: collapsible sections (remembered) + live "find a page" filter */
(function(){
  "use strict";
  var secs = [].slice.call(document.querySelectorAll(".admin-nav-sec")); if(!secs.length) return;
  var KEY = "nufa_nav_open", saved = {};
  try { saved = JSON.parse(localStorage.getItem(KEY) || "{}") || {}; } catch(e) {}
  function persist(){ var o = {}; secs.forEach(function(s){ o[s.dataset.group] = !s.classList.contains("is-closed"); }); try { localStorage.setItem(KEY, JSON.stringify(o)); } catch(e) {} }
  function set(s, open){ s.classList.toggle("is-closed", !open); var b = s.querySelector(".admin-nav-group"); if(b) b.setAttribute("aria-expanded", open ? "true" : "false"); }
  secs.forEach(function(s){
    var open = s.dataset.active === "1" ? true : (s.dataset.group in saved ? !!saved[s.dataset.group] : s.dataset.default === "1");
    set(s, open);
    s.querySelector(".admin-nav-group").addEventListener("click", function(){ set(s, s.classList.contains("is-closed")); persist(); });
  });
  var f = document.getElementById("navFilter"), none = document.getElementById("navNone");
  if(f) f.addEventListener("input", function(){
    var q = f.value.trim().toLowerCase(), any = false;
    secs.forEach(function(s){
      var hit = 0;
      [].slice.call(s.querySelectorAll(".admin-nav-items a")).forEach(function(a){ var m = !q || a.textContent.toLowerCase().indexOf(q) > -1; a.classList.toggle("is-hidden", !m); if(m) hit++; });
      s.classList.toggle("is-hidden", !hit); if(hit) any = true;
      if(q) s.classList.remove("is-closed"); else set(s, s.dataset.active === "1" ? true : (s.dataset.group in saved ? !!saved[s.dataset.group] : s.dataset.default === "1"));
    });
    if(none) none.hidden = any || !q;
  });
})();
