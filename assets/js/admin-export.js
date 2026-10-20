/* Adds an "Export" menu (PDF, Excel, Word, CSV) above every admin data table, using exactly the rows currently shown. */
(function(){
  var meta = document.querySelector('meta[name="admin-csrf"]');
  var token = meta ? meta.getAttribute("content") : "";
  function clean(t){ return (t || "").replace(/\s+/g, " ").trim(); }
  function pageTitle(table){
    var card = table.closest(".admin-card");
    var h = card && card.querySelector(".admin-card-head h2, .admin-card-head h3");
    var t = h ? clean(h.textContent) : "";
    var main = clean((document.querySelector(".admin-topbar h1, .admin-page-title, title") || {}).textContent || "");
    main = main.replace(/\s*[—-]\s*NUFA Admin$/, "");
    return t && main && t.toLowerCase() !== main.toLowerCase() ? main + " — " + t : (t || main || "Export");
  }
  function extract(table){
    var head = table.querySelector("thead tr"); if(!head) return null;
    var ths = [].slice.call(head.children), keep = [], headers = [];
    ths.forEach(function(th, i){ var t = clean(th.textContent); if(t && !/^(actions?|select)$/i.test(t)){ keep.push(i); headers.push(t); } });
    if(!headers.length) return null;
    var rows = [];
    [].slice.call(table.querySelectorAll("tbody tr")).forEach(function(tr){
      var tds = [].slice.call(tr.children); if(tds.length === 1 && tds[0].colSpan > 1) return;
      var row = keep.map(function(i){ var td = tds[i]; if(!td) return ""; var clone = td.cloneNode(true); [].slice.call(clone.querySelectorAll("button,script,style,input[type=hidden],svg")).forEach(function(n){ n.remove(); });
        [].slice.call(clone.querySelectorAll("br")).forEach(function(b){ b.replaceWith(" · "); });
        var inp = clone.querySelector("input[type=text],input[type=email],input:not([type]),select,textarea"); if(inp){ return clean(inp.value || inp.getAttribute("value") || ""); }
        return clean(clone.textContent).replace(/ · $/, ""); });
      if(row.some(function(c){ return c !== ""; })) rows.push(row);
    });
    return {headers: headers, rows: rows};
  }
  function send(fmt, table){
    var d = extract(table); if(!d || !d.rows.length){ alert("There is nothing to export in this table yet."); return; }
    var f = document.createElement("form"); f.method = "POST"; f.action = "export.php"; f.style.display = "none";
    [["csrf", token], ["format", fmt], ["title", pageTitle(table)], ["data", JSON.stringify(d)]].forEach(function(p){ var i = document.createElement("input"); i.type = "hidden"; i.name = p[0]; i.value = p[1]; f.appendChild(i); });
    document.body.appendChild(f); f.submit(); setTimeout(function(){ f.remove(); }, 1500);
  }
  var items = [["pdf", "PDF document"], ["xlsx", "Excel spreadsheet"], ["docx", "Word document"], ["csv", "CSV file"]];
  [].slice.call(document.querySelectorAll("table.admin-table")).forEach(function(table){
    if(table.hasAttribute("data-noexport") || table.closest("[data-noexport]")) return;
    var d = extract(table); if(!d || d.rows.length < 1) return;
    var bar = document.createElement("div"); bar.className = "exp-bar";
    bar.innerHTML = '<span class="exp-count">' + d.rows.length + ' row' + (d.rows.length === 1 ? '' : 's') + '</span><span class="exp-menu"><button type="button" class="admin-btn admin-btn-line admin-btn-sm exp-toggle" aria-haspopup="true">⬇ Export ▾</button><span class="exp-list" hidden></span></span>';
    var list = bar.querySelector(".exp-list");
    items.forEach(function(it){ var a = document.createElement("a"); a.href = "#"; a.textContent = it[1]; a.addEventListener("click", function(e){ e.preventDefault(); list.hidden = true; send(it[0], table); }); list.appendChild(a); });
    var wrap = table.parentElement && table.parentElement.classList.contains("admin-table-wrap") ? table.parentElement : table;
    wrap.parentNode.insertBefore(bar, wrap);
  });
  document.addEventListener("click", function(e){
    var t = e.target.closest ? e.target.closest(".exp-toggle") : null;
    [].slice.call(document.querySelectorAll(".exp-list")).forEach(function(l){ if(!t || l.parentNode !== t.parentNode) l.hidden = true; });
    if(t){ var l = t.parentNode.querySelector(".exp-list"); l.hidden = !l.hidden; }
  });
})();
