/* Local progressive enhancement; forms and reviews work without JavaScript. */
(() => {
 'use strict';
 document.querySelectorAll('[data-agp-launch]').forEach(node => {
  const at = Number(node.dataset.agpLaunch) * 1000;
  if (!Number.isFinite(at)) return;
  const tick = () => {
   const seconds = Math.max(0, Math.floor((at - Date.now()) / 1000));
   if (!seconds) { node.textContent = 'Launch time reached — reload for availability.'; return false; }
   const days = Math.floor(seconds / 86400);
   const hours = Math.floor(seconds % 86400 / 3600);
   const minutes = Math.floor(seconds % 3600 / 60);
   node.textContent = `${days}d ${hours}h ${minutes}m ${seconds % 60}s`;
   return true;
  };
  if (tick()) { const timer = setInterval(() => { if (!tick()) clearInterval(timer); }, 1000); }
 });
 document.querySelectorAll('[data-agp-controls]').forEach(controls => {
  const track = document.getElementById(controls.dataset.agpControls);
  if (!track) return;
  controls.hidden = false;
  controls.querySelectorAll('button').forEach(button => button.addEventListener('click', () => {
   track.scrollBy({left: (button.hasAttribute('data-agp-prev') ? -1 : 1) * track.clientWidth,
    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'});
  }));
 });
})();
