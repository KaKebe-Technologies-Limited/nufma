/* Lightweight rich-text editor for admin story & newsletter bodies.
   Usage: <textarea data-rich data-upload="upload-image.php"> — the textarea keeps the HTML and is what gets submitted. */
(function(){
  function el(tag, cls, html){ var e = document.createElement(tag); if(cls) e.className = cls; if(html) e.innerHTML = html; return e; }
  document.querySelectorAll("textarea[data-rich]").forEach(function(ta){
    var form = ta.closest("form");
    var wrap = el("div", "rte");
    var bar = el("div", "rte-bar");
    var area = el("div", "rte-area");
    area.contentEditable = "true";
    area.setAttribute("role", "textbox");
    area.setAttribute("aria-multiline", "true");
    var src = el("textarea", "rte-src");
    src.hidden = true;
    var showSrc = false;

    function toEditor(html){ return (html || "").replace(/(src=")(?!https?:|\/|\.\.\/)(assets\/)/g, "$1../$2"); }
    function fromEditor(html){ return html.replace(/(src=")\.\.\/(assets\/)/g, "$1$2"); }
    function sync(){ ta.value = fromEditor(showSrc ? src.value : area.innerHTML).replace(/^\s*<br>\s*$/, ""); }

    area.innerHTML = toEditor(ta.value) || "<p><br></p>";
    ta.style.display = "none";
    ta.parentNode.insertBefore(wrap, ta);
    wrap.appendChild(bar); wrap.appendChild(area); wrap.appendChild(src); wrap.appendChild(ta);

    function cmd(c, v){ area.focus(); document.execCommand(c, false, v || null); sync(); }
    var buttons = [
      ["P", "Paragraph", function(){ cmd("formatBlock", "p"); }],
      ["H2", "Heading", function(){ cmd("formatBlock", "h2"); }],
      ["H3", "Sub-heading", function(){ cmd("formatBlock", "h3"); }],
      ["B", "Bold", function(){ cmd("bold"); }, "b"],
      ["I", "Italic", function(){ cmd("italic"); }, "i"],
      ["U", "Underline", function(){ cmd("underline"); }, "u"],
      ["• List", "Bulleted list", function(){ cmd("insertUnorderedList"); }],
      ["1. List", "Numbered list", function(){ cmd("insertOrderedList"); }],
      ["❝", "Quote", function(){ cmd("formatBlock", "blockquote"); }],
      ["Link", "Add link", function(){ var u = prompt("Link address (https://…)"); if(u && /^(https?:|mailto:|\/)/i.test(u)) cmd("createLink", u); }],
      ["Unlink", "Remove link", function(){ cmd("unlink"); }],
      ["Image", "Upload and insert an image", function(){ pick(); }],
      ["―", "Divider", function(){ cmd("insertHorizontalRule"); }],
      ["Clear", "Clear formatting", function(){ cmd("removeFormat"); cmd("formatBlock", "p"); }],
      ["</>", "Edit HTML", function(){ toggleSrc(); }]
    ];
    buttons.forEach(function(b){
      var btn = el("button", "rte-btn" + (b[3] ? " rte-" + b[3] : ""), b[0]);
      btn.type = "button"; btn.title = b[1]; btn.setAttribute("aria-label", b[1]);
      btn.addEventListener("mousedown", function(e){ e.preventDefault(); });
      btn.addEventListener("click", b[2]);
      bar.appendChild(btn);
    });

    function toggleSrc(){
      showSrc = !showSrc;
      if(showSrc){ src.value = fromEditor(area.innerHTML); src.hidden = false; area.hidden = true; }
      else { area.innerHTML = toEditor(src.value) || "<p><br></p>"; src.hidden = true; area.hidden = false; }
      sync();
    }

    function pick(){
      var url = ta.getAttribute("data-upload");
      if(!url) return;
      var f = document.createElement("input"); f.type = "file"; f.accept = "image/jpeg,image/png,image/webp";
      f.onchange = function(){
        if(!f.files[0]) return;
        var tok = form && form.querySelector('input[name="csrf"]');
        var fd = new FormData(); fd.append("image", f.files[0]); fd.append("csrf", tok ? tok.value : "");
        bar.classList.add("rte-busy");
        fetch(url, { method: "POST", body: fd, credentials: "same-origin" }).then(function(r){ return r.json(); }).then(function(j){
          bar.classList.remove("rte-busy");
          if(j.error){ alert(j.error); return; }
          area.focus();
          document.execCommand("insertHTML", false, '<figure><img src="../' + j.path + '" alt="' + (prompt("Describe the image (for accessibility)") || "").replace(/"/g, "&quot;") + '"></figure><p><br></p>');
          sync();
        }).catch(function(){ bar.classList.remove("rte-busy"); alert("The image could not be uploaded."); });
      };
      f.click();
    }

    area.addEventListener("input", sync);
    area.addEventListener("blur", sync);
    src.addEventListener("input", sync);
    area.addEventListener("paste", function(e){   // paste as clean text/HTML without styles
      var t = (e.clipboardData || window.clipboardData);
      if(!t) return;
      var html = t.getData("text/html");
      if(html){ e.preventDefault(); var tmp = document.createElement("div"); tmp.innerHTML = html; tmp.querySelectorAll("*").forEach(function(n){ n.removeAttribute("style"); n.removeAttribute("class"); n.removeAttribute("id"); }); tmp.querySelectorAll("script,style,meta,link").forEach(function(n){ n.remove(); }); document.execCommand("insertHTML", false, tmp.innerHTML); sync(); }
    });
    if(form) form.addEventListener("submit", function(){ sync(); });
    sync();
  });
})();
