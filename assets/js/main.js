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
  function openNav(){ mobileNav.classList.add("is-open"); document.body.classList.add("nav-locked"); }
  function closeNav(){ mobileNav.classList.remove("is-open"); document.body.classList.remove("nav-locked"); }
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
        lightbox.classList.add("is-open");
        document.body.classList.add("nav-locked");
      });
    });

    function close(){
      lightbox.classList.remove("is-open");
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
