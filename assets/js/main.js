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
