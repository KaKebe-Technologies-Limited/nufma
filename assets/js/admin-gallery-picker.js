/* "Choose from gallery": adds a button beside every image upload field in the admin. The chosen photo(s) are put into the
   field exactly as if they had been uploaded from the computer, so every page's own save logic keeps working. */
(function () {
  "use strict";
  var css = ".gp-btn{margin-top:8px;display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1px solid #D50000;border-radius:8px;background:#fff;color:#D50000;font:600 13px/1 inherit;cursor:pointer}.gp-btn:hover{background:#D50000;color:#fff}" +
    ".gp-ov{position:fixed;inset:0;z-index:100000;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;padding:16px}.gp-box{background:#fff;color:#111;width:min(1100px,100%);max-height:92vh;border-radius:14px;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4)}" +
    ".gp-head{display:flex;gap:10px;flex-wrap:wrap;align-items:center;padding:14px 16px;border-bottom:1px solid #e5e5e5}.gp-head h3{margin:0 auto 0 0;font-size:1.05rem}.gp-head input,.gp-head select{padding:8px 10px;border:1px solid #ccc;border-radius:8px;font:inherit;font-size:14px;min-width:0}" +
    ".gp-grid{padding:14px 16px;overflow:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;flex:1}.gp-it{position:relative;aspect-ratio:1;border-radius:8px;overflow:hidden;background:#eee;cursor:pointer;border:3px solid transparent;padding:0}.gp-it img{width:100%;height:100%;object-fit:cover;display:block}" +
    ".gp-it.on{border-color:#D50000}.gp-it.on::after{content:'\\2713';position:absolute;top:6px;right:6px;width:24px;height:24px;border-radius:50%;background:#D50000;color:#fff;font:700 14px/24px sans-serif;text-align:center}" +
    ".gp-foot{display:flex;gap:10px;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid #e5e5e5;flex-wrap:wrap}.gp-foot .gp-sp{flex:1}.gp-foot button{padding:9px 16px;border-radius:8px;border:1px solid #ccc;background:#fff;font:600 14px/1 inherit;cursor:pointer}.gp-foot .gp-use{background:#D50000;color:#fff;border-color:#D50000}.gp-foot .gp-use:disabled{opacity:.45;cursor:not-allowed}.gp-msg{color:#5f5f5f;font-size:13px}";
  var st = document.createElement("style"); st.textContent = css; document.head.appendChild(st);

  function el(t, c, h) { var e = document.createElement(t); if (c) e.className = c; if (h != null) e.innerHTML = h; return e; }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]; }); }

  function openPicker(input) {
    var multi = input.multiple, picked = [], page = 1, loading = false;
    var ov = el("div", "gp-ov"), box = el("div", "gp-box"); ov.appendChild(box);
    var head = el("div", "gp-head", "<h3>Choose from the gallery</h3>");
    var q = el("input"); q.type = "search"; q.placeholder = "Search…";
    var cat = el("select", "", '<option value="">All albums</option>');
    head.appendChild(q); head.appendChild(cat);
    var grid = el("div", "gp-grid"), foot = el("div", "gp-foot");
    var msg = el("span", "gp-msg", multi ? "Click photos to select (you can pick several)." : "Click a photo to select it.");
    var more = el("button", "", "Load more"); more.type = "button"; more.hidden = true;
    var cancel = el("button", "", "Cancel"); cancel.type = "button";
    var use = el("button", "gp-use", "Use selected"); use.type = "button"; use.disabled = true;
    foot.appendChild(msg); foot.appendChild(el("span", "gp-sp")); foot.appendChild(more); foot.appendChild(cancel); foot.appendChild(use);
    box.appendChild(head); box.appendChild(grid); box.appendChild(foot); document.body.appendChild(ov);
    function close() { ov.remove(); document.removeEventListener("keydown", esc2); }
    function esc2(e) { if (e.key === "Escape") close(); }
    document.addEventListener("keydown", esc2);
    ov.addEventListener("click", function (e) { if (e.target === ov) close(); }); cancel.onclick = close;

    function sync() { use.disabled = !picked.length; use.textContent = picked.length ? "Use " + picked.length + " selected" : "Use selected"; }
    function load(reset) {
      if (loading) return; loading = true; if (reset) { page = 1; grid.innerHTML = ""; }
      fetch("gallery-picker.php?q=" + encodeURIComponent(q.value) + "&cat=" + encodeURIComponent(cat.value) + "&page=" + page, { credentials: "same-origin" })
        .then(function (r) { return r.json(); }).then(function (d) {
          if (!cat.dataset.f) { cat.dataset.f = 1; Object.keys(d.cats).forEach(function (k) { var o = el("option"); o.value = k; o.textContent = k + " (" + d.cats[k] + ")"; cat.appendChild(o); }); }
          d.items.forEach(function (it) {
            var b = el("button", "gp-it" + (picked.indexOf(it.path) > -1 ? " on" : "")); b.type = "button"; b.title = it.caption || it.path;
            b.innerHTML = '<img loading="lazy" alt="' + esc(it.caption || "") + '" src="../assets/images/' + esc(it.thumb) + '">';
            b.onclick = function () {
              var i = picked.indexOf(it.path);
              if (i > -1) { picked.splice(i, 1); b.classList.remove("on"); }
              else { if (!multi) { picked = []; grid.querySelectorAll(".gp-it.on").forEach(function (x) { x.classList.remove("on"); }); } picked.push(it.path); b.classList.add("on"); }
              sync();
            };
            grid.appendChild(b);
          });
          if (!d.total) grid.innerHTML = '<p class="gp-msg">No photos found.</p>';
          more.hidden = !d.more; page++; loading = false;
        }).catch(function () { loading = false; grid.innerHTML = '<p class="gp-msg">Could not load the gallery.</p>'; });
    }
    var t; q.oninput = function () { clearTimeout(t); t = setTimeout(function () { load(true); }, 250); };
    cat.onchange = function () { load(true); }; more.onclick = function () { load(false); };
    use.onclick = function () {
      use.disabled = true; use.textContent = "Adding…";
      Promise.all(picked.map(function (p) {
        return fetch("../assets/images/" + p, { credentials: "same-origin" }).then(function (r) { return r.blob(); })
          .then(function (b) { return new File([b], p.split("/").pop(), { type: b.type || "image/jpeg" }); });
      })).then(function (files) {
        var dt = new DataTransfer();
        if (multi) Array.prototype.forEach.call(input.files || [], function (f) { dt.items.add(f); });
        files.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files; input.dispatchEvent(new Event("change", { bubbles: true })); close();
      }).catch(function () { use.textContent = "Failed — try again"; use.disabled = false; });
    };
    load(true);
  }

  document.querySelectorAll('input[type="file"]').forEach(function (inp) {
    var acc = (inp.getAttribute("accept") || "").toLowerCase();
    if (inp.hasAttribute("data-no-picker") || acc.indexOf("image") === -1 && !/\.(jpe?g|png|webp)/.test(acc)) return;
    if (/pdf|csv|zip/.test(acc) && acc.indexOf("image") === -1) return;
    var b = el("button", "gp-btn", "&#128444; Choose from gallery"); b.type = "button";
    b.onclick = function () { openPicker(inp); };
    inp.insertAdjacentElement("afterend", b);
  });
})();
