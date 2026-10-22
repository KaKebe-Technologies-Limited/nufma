/* NUFA film player: custom controls for MP4 files; the NUFA logo stays on screen in fullscreen. */
(function () {
  function fmt(s) { s = Math.max(0, Math.floor(s || 0)); var m = Math.floor(s / 60), h = Math.floor(m / 60); return h ? h + ':' + ('0' + (m % 60)).slice(-2) + ':' + ('0' + (s % 60)).slice(-2) : m + ':' + ('0' + (s % 60)).slice(-2); }
  document.querySelectorAll('[data-fp]').forEach(function (box) {
    var stage = box.querySelector('.fp-stage'), full = box.querySelector('.fp-full');
    function toggleFull() {
      var d = document;
      if (d.fullscreenElement || d.webkitFullscreenElement) { (d.exitFullscreen || d.webkitExitFullscreen).call(d); }
      else { var r = stage.requestFullscreen || stage.webkitRequestFullscreen; if (r) r.call(stage); }
    }
    if (full) full.addEventListener('click', toggleFull);
    var v = box.querySelector('video'); if (!v) return;
    var big = box.querySelector('.fp-big'), play = box.querySelector('.fp-play'), seek = box.querySelector('.fp-seek'), vol = box.querySelector('.fp-vol'), mute = box.querySelector('.fp-mute'),
        tt = box.querySelector('.fp-t'), td = box.querySelector('.fp-d'), idle;
    function icons() { var p = !v.paused; play.querySelector('.p').style.display = p ? 'none' : ''; play.querySelector('.q').style.display = p ? '' : 'none'; box.classList.toggle('is-playing', p); }
    function toggle() { if (v.paused) { var pr = v.play(); if (pr && pr.catch) pr.catch(function () {}); } else v.pause(); }
    big.addEventListener('click', toggle); play.addEventListener('click', toggle); v.addEventListener('click', toggle);
    v.addEventListener('play', icons); v.addEventListener('pause', icons); v.addEventListener('ended', icons);
    v.addEventListener('loadedmetadata', function () { td.textContent = fmt(v.duration); });
    v.addEventListener('timeupdate', function () { tt.textContent = fmt(v.currentTime); if (v.duration && !seek.dataset.drag) seek.value = Math.round(v.currentTime / v.duration * 1000); });
    seek.addEventListener('input', function () { seek.dataset.drag = 1; if (v.duration) v.currentTime = seek.value / 1000 * v.duration; });
    seek.addEventListener('change', function () { delete seek.dataset.drag; });
    vol.addEventListener('input', function () { v.volume = vol.value; v.muted = v.volume === 0; });
    mute.addEventListener('click', function () { v.muted = !v.muted; mute.classList.toggle('is-muted', v.muted); });
    v.addEventListener('dblclick', toggleFull);
    stage.addEventListener('mousemove', function () { box.classList.add('show-bar'); clearTimeout(idle); idle = setTimeout(function () { box.classList.remove('show-bar'); }, 2500); });
    box.addEventListener('keydown', function (e) {
      if (e.target.tagName === 'INPUT') return;
      if (e.key === ' ' || e.key === 'k') { e.preventDefault(); toggle(); } else if (e.key === 'f') toggleFull(); else if (e.key === 'ArrowRight') v.currentTime += 5; else if (e.key === 'ArrowLeft') v.currentTime -= 5;
    });
    box.tabIndex = 0;
  });
})();
