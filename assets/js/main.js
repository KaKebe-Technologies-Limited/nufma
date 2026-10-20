/* NUFA site interactions — header state, mobile nav, reveal-on-scroll, gallery lightbox */
(function(){
  "use strict";

  /* ---------- sticky header ---------- */
  var header = document.querySelector(".site-header");
  function onScroll(){
    if(!header) return;
    if(window.scrollY > 24) header.classList.add("is-scrolled");
    else header.classList.remove("is-scrolled");

    var toTop = document.querySelector(".to-top");
    if(toTop){
      if(window.scrollY > 700) toTop.classList.add("is-visible");
      else toTop.classList.remove("is-visible");
    }
  }
  document.addEventListener("scroll", onScroll, {passive:true});
  onScroll();

  /* ---------- mobile nav ---------- */
  var navToggle = document.querySelector(".nav-toggle");
  var mobileNav = document.querySelector(".mobile-nav");
  var navClose = document.querySelector(".mobile-nav-close");
  function openNav(){
    mobileNav.classList.add("is-open"); mobileNav.setAttribute("aria-hidden", "false");
    document.body.classList.add("nav-locked");
    if(navClose) navClose.focus();
  }
  function closeNav(){
    mobileNav.classList.remove("is-open"); mobileNav.setAttribute("aria-hidden", "true");
    document.body.classList.remove("nav-locked");
    if(navToggle) navToggle.focus();
  }
  document.addEventListener("keydown", function(e){
    if(e.key === "Escape" && mobileNav && mobileNav.classList.contains("is-open")) closeNav();
  });
  if(navToggle) navToggle.addEventListener("click", openNav);
  if(navClose) navClose.addEventListener("click", closeNav);

  document.querySelectorAll(".mobile-nav [data-toggle-sub]").forEach(function(btn){
    btn.addEventListener("click", function(){
      var sub = btn.closest("li").querySelector(".mobile-sub");
      if(!sub) return;
      sub.classList.toggle("is-open");
      btn.classList.toggle("is-open");
    });
  });

  /* ---------- desktop dropdown (click-to-toggle, works on touch too) ---------- */
  document.querySelectorAll(".main-nav .caret-btn").forEach(function(btn){
    btn.addEventListener("click", function(e){
      e.preventDefault();
      e.stopPropagation();
      var li = btn.closest("li");
      var wasOpen = li.classList.contains("dd-open");
      document.querySelectorAll(".main-nav li.dd-open").forEach(function(l){
        l.classList.remove("dd-open");
        var b = l.querySelector(".caret-btn");
        if(b) b.setAttribute("aria-expanded", "false");
      });
      if(!wasOpen){
        li.classList.add("dd-open");
        btn.setAttribute("aria-expanded", "true");
      }
    });
  });
  document.addEventListener("click", function(e){
    if(!e.target.closest(".main-nav li.has-dropdown")){
      document.querySelectorAll(".main-nav li.dd-open").forEach(function(l){ l.classList.remove("dd-open"); });
    }
  });

  document.querySelectorAll(".mobile-nav a:not([data-toggle-sub])").forEach(function(a){
    a.addEventListener("click", closeNav);
  });

  /* ---------- back to top ---------- */
  var toTop = document.querySelector(".to-top");
  if(toTop){
    toTop.addEventListener("click", function(){
      window.scrollTo({top:0, behavior:"smooth"});
    });
  }

  /* ---------- reveal on scroll ---------- */
  var revealEls = document.querySelectorAll("[data-reveal],[data-reveal-stagger]");
  if("IntersectionObserver" in window && revealEls.length){
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(entry.isIntersecting){
          entry.target.classList.add("is-visible");
          io.unobserve(entry.target);
        }
      });
    }, {threshold:0.12, rootMargin:"0px 0px -60px 0px"});
    revealEls.forEach(function(el){ io.observe(el); });
  } else {
    revealEls.forEach(function(el){ el.classList.add("is-visible"); });
  }

  /* ---------- gallery filter tabs ---------- */
  var tabs = document.querySelectorAll(".gallery-tab");
  var items = document.querySelectorAll("[data-gallery-item]");
  if(tabs.length){
    tabs.forEach(function(tab){
      tab.addEventListener("click", function(){
        tabs.forEach(function(t){ t.classList.remove("is-active"); });
        tab.classList.add("is-active");
        var filter = tab.getAttribute("data-filter");
        items.forEach(function(item){
          var match = filter === "all" || item.getAttribute("data-gallery-item") === filter;
          item.style.display = match ? "" : "none";
        });
      });
    });
  }

  /* ---------- lightbox ---------- */
  var lightbox = document.querySelector(".lightbox");
  if(lightbox){
    var lbImg = lightbox.querySelector("img");
    var lbCaption = lightbox.querySelector(".lightbox-caption");
    var gallerySources = Array.prototype.map.call(
      document.querySelectorAll("[data-lightbox]"),
      function(el){ return {src: el.getAttribute("data-lightbox"), caption: el.getAttribute("data-caption") || ""}; }
    );
    var current = 0;

    function show(i){
      current = (i + gallerySources.length) % gallerySources.length;
      lbImg.src = gallerySources[current].src;
      lbCaption.textContent = gallerySources[current].caption;
    }

    document.querySelectorAll("[data-lightbox]").forEach(function(el, i){
      el.addEventListener("click", function(){
        show(i);
        lightbox.classList.add("is-open"); lightbox.setAttribute("aria-hidden", "false");
        document.body.classList.add("nav-locked");
      });
    });

    function close(){
      lightbox.classList.remove("is-open"); lightbox.setAttribute("aria-hidden", "true");
      document.body.classList.remove("nav-locked");
    }

    var closeBtn = lightbox.querySelector(".lightbox-close");
    var prevBtn = lightbox.querySelector(".lightbox-prev");
    var nextBtn = lightbox.querySelector(".lightbox-next");
    if(closeBtn) closeBtn.addEventListener("click", close);
    if(prevBtn) prevBtn.addEventListener("click", function(){ show(current - 1); });
    if(nextBtn) nextBtn.addEventListener("click", function(){ show(current + 1); });
    lightbox.addEventListener("click", function(e){ if(e.target === lightbox) close(); });
    document.addEventListener("keydown", function(e){
      if(!lightbox.classList.contains("is-open")) return;
      if(e.key === "Escape") close();
      if(e.key === "ArrowRight") show(current + 1);
      if(e.key === "ArrowLeft") show(current - 1);
    });
  }


  /* ---------- current year ---------- */
  document.querySelectorAll("[data-year]").forEach(function(el){
    el.textContent = new Date().getFullYear();
  });
})();

/* ---------- homepage hero: lazy-load extra photos after the page, then crossfade ---------- */
(function(){
  var media = document.querySelector(".hero-media");
  if(!media) return;
  var imgs = [].slice.call(media.querySelectorAll("img"));
  var dots = [].slice.call(document.querySelectorAll(".hero-dots button"));
  if(imgs.length < 2) return;
  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var current = 0, timer = null, loaded = false;

  function show(n){
    n = (n + imgs.length) % imgs.length;
    if(n === current) return;
    imgs[current].classList.remove("is-active");
    imgs[n].classList.add("is-active");
    if(dots[current]){ dots[current].classList.remove("is-active"); dots[current].setAttribute("aria-pressed", "false"); }
    if(dots[n]){ dots[n].classList.add("is-active"); dots[n].setAttribute("aria-pressed", "true"); }
    current = n;
  }
  function ensure(i, cb){
    var im = imgs[i];
    if(!im.getAttribute("data-src")){ cb(); return; }
    im.onload = im.onerror = cb;
    im.src = im.getAttribute("data-src"); im.removeAttribute("data-src");
  }
  function next(){ var n = (current + 1) % imgs.length; ensure(n, function(){ show(n); }); }
  function start(){ if(reduce || timer || document.hidden) return; timer = setInterval(next, 6500); }
  function stop(){ if(timer){ clearInterval(timer); timer = null; } }

  function init(){
    if(loaded) return; loaded = true;
    dots.forEach(function(d, i){ d.addEventListener("click", function(){ stop(); ensure(i, function(){ show(i); start(); }); }); });
    var hero = media.closest(".hero");
    if(hero){ hero.addEventListener("mouseenter", stop); hero.addEventListener("mouseleave", start); hero.addEventListener("focusin", stop); hero.addEventListener("focusout", start); }
    document.addEventListener("visibilitychange", function(){ document.hidden ? stop() : start(); });
    // warm the second photo so the first crossfade is smooth, then begin
    ensure(1, start);
  }
  // wait until the first photo and page have loaded so the extra photos never compete with them
  if(document.readyState === "complete") setTimeout(init, 800);
  else window.addEventListener("load", function(){ setTimeout(init, 800); });
})();

/* ---------- live clock in the top bar ---------- */
(function(){
  var el = document.querySelector("[data-clock]");
  if(!el || !window.Intl || !Intl.DateTimeFormat) return;
  var tz = el.getAttribute("data-tz") || "Africa/Kampala";
  var dateEl = el.querySelector(".clock-date"), timeEl = el.querySelector(".clock-time"), zoneEl = el.querySelector(".clock-zone");
  var fd, ft, fz;
  try {
    fd = new Intl.DateTimeFormat("en-GB", {timeZone: tz, weekday: "short", day: "numeric", month: "short", year: "numeric"});
    ft = new Intl.DateTimeFormat("en-GB", {timeZone: tz, hour: "2-digit", minute: "2-digit", second: "2-digit", hour12: false});
    fz = new Intl.DateTimeFormat("en-GB", {timeZone: tz, timeZoneName: "short"});
  } catch(e) { return; }
  function tick(){
    var d = new Date();
    if(timeEl) timeEl.textContent = ft.format(d);
    if(dateEl) dateEl.textContent = fd.format(d).replace(",", "");
  }
  if(zoneEl){ try { var p = fz.formatToParts(new Date()).filter(function(x){ return x.type === "timeZoneName"; })[0]; if(p) zoneEl.textContent = p.value.replace("GMT+3", "EAT"); } catch(e){} }
  tick();
  setInterval(tick, 1000);
})();

/* ---------- install the site as an app ---------- */
(function(){
  var btns = [].slice.call(document.querySelectorAll("[data-install]"));
  var banner = document.querySelector("[data-install-banner]");
  var ios = document.querySelector("[data-ios-help]");
  if(!btns.length) return;
  var standalone = (window.matchMedia && window.matchMedia("(display-mode: standalone)").matches) || window.navigator.standalone === true;
  if(standalone) return;
  var deferred = null;
  var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
  var DISMISS = "nufa_install_dismissed";
  function dismissed(){ try { var t = parseInt(localStorage.getItem(DISMISS) || "0", 10); return t && Date.now() - t < 14 * 864e5; } catch(e){ return false; } }
  function setDismissed(){ try { localStorage.setItem(DISMISS, String(Date.now())); } catch(e){} }
  function showButtons(){ btns.forEach(function(b){ b.hidden = false; }); }
  function hideAll(){ btns.forEach(function(b){ b.hidden = true; }); if(banner) banner.hidden = true; }
  function go(){
    if(deferred){
      deferred.prompt();
      deferred.userChoice.then(function(){ deferred = null; hideAll(); });
    } else if(isIOS && ios){ ios.hidden = false; }
  }
  function maybeBanner(){
    if(!banner || dismissed() || window.innerWidth > 860) return;
    setTimeout(function(){ if(deferred || isIOS) banner.hidden = false; }, 15000);
  }
  window.addEventListener("beforeinstallprompt", function(e){ e.preventDefault(); deferred = e; showButtons(); maybeBanner(); });
  window.addEventListener("appinstalled", function(){ deferred = null; hideAll(); });
  btns.forEach(function(b){ b.addEventListener("click", go); });
  if(banner){
    var g = banner.querySelector("[data-install-go]"), x = banner.querySelector("[data-install-dismiss]");
    if(g) g.addEventListener("click", function(){ banner.hidden = true; go(); });
    if(x) x.addEventListener("click", function(){ banner.hidden = true; setDismissed(); });
  }
  if(ios){
    var c = ios.querySelector("[data-ios-close]");
    if(c) c.addEventListener("click", function(){ ios.hidden = true; });
    ios.addEventListener("click", function(e){ if(e.target === ios) ios.hidden = true; });
  }
  if(isIOS){ showButtons(); maybeBanner(); }   // iOS has no install prompt event: show our own instructions
})();
