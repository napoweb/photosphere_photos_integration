/* Personal settings entry opens tour manager without an intermediate settings page. */
(() => {
  const target = document.getElementById('ppi-personal-redirect');
  const url = target?.dataset.url;
  if (url && url.startsWith('/') && !url.startsWith('//')) window.location.replace(url);
})();
