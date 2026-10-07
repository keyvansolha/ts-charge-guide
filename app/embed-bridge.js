(() => {
  'use strict';
  if (window.parent === window) return;
  let targetOrigin;
  try {
    targetOrigin = new URL(document.referrer).origin;
    if (targetOrigin !== window.location.origin) return;
  } catch { return; }
  let scheduled = false;
  let lastHeight = 0;
  const send = (action, extra) => window.parent.postMessage(
    Object.assign({type: 'ts-charge-guide', action}, extra), targetOrigin
  );
  const measure = () => {
    scheduled = false;
    const height = Math.ceil(document.body.getBoundingClientRect().height);
    if (Math.abs(height - lastHeight) > 1) {
      lastHeight = height;
      send('resize', {height});
    }
  };
  const schedule = () => {
    if (!scheduled) { scheduled = true; requestAnimationFrame(measure); }
  };
  if ('ResizeObserver' in window) new ResizeObserver(schedule).observe(document.body);
  else new MutationObserver(schedule).observe(document.body, {subtree: true, childList: true, attributes: true});
  window.addEventListener('resize', schedule);
  window.addEventListener('load', schedule);
  document.fonts?.ready.then(schedule);
  document.querySelectorAll('img').forEach(image => image.addEventListener('load', schedule));
  document.addEventListener('click', event => {
    const link = event.target.closest('a[href^="#"]');
    if (!link) return;
    let id;
    try { id = decodeURIComponent(link.getAttribute('href').slice(1)); } catch { return; }
    const target = document.getElementById(id);
    if (!target) return;
    event.preventDefault();
    send('scroll', {
      top: target.getBoundingClientRect().top + window.scrollY,
      reduceMotion: document.body.classList.contains('low-motion')
    });
  });
  window.addEventListener('message', event => {
    if (event.source !== window.parent || event.origin !== targetOrigin) return;
    const data = event.data;
    if (data?.type !== 'ts-charge-guide' || data.action !== 'theme' || typeof data.dark !== 'boolean') return;
    document.body.classList.toggle('dark', data.dark);
  });
  send('ready', {});
  schedule();
})();
