(function(){
  "use strict";
  document.querySelectorAll("[data-confirm]").forEach(function(el){
    el.addEventListener("click", function(e){
      if(!confirm(el.getAttribute("data-confirm"))) e.preventDefault();
    });
  });

  var sidebar = document.getElementById("adminSidebar");
  var overlay = document.getElementById("adminOverlay");
  var toggle = document.getElementById("adminSidebarToggle");
  function closeSidebar(){
    if(sidebar) sidebar.classList.remove("is-open");
    if(overlay) overlay.classList.remove("is-open");
  }
  if(toggle){
    toggle.addEventListener("click", function(){
      sidebar.classList.toggle("is-open");
      overlay.classList.toggle("is-open");
    });
  }
  if(overlay) overlay.addEventListener("click", closeSidebar);
})();

(function(){
  var u = document.getElementById("adminUser");
  if(!u) return;
  var b = u.querySelector(".admin-user-btn");
  b.addEventListener("click", function(e){ e.stopPropagation(); var o = u.classList.toggle("is-open"); b.setAttribute("aria-expanded", o); });
  document.addEventListener("click", function(){ u.classList.remove("is-open"); b.setAttribute("aria-expanded", "false"); });
})();
