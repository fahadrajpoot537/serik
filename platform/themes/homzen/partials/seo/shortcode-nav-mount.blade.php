@php
    $mountId = 'serikSeoNavShortcode-' . substr(md5(($ajaxUrl ?? '') . uniqid('', true)), 0, 8);
@endphp
<div id="{{ $mountId }}" class="serik-seo-nav-shortcode-mount" data-url="{{ $ajaxUrl }}" data-hydrate-url="{{ $ajaxUrl }}" aria-hidden="true"></div>
<script>
(function () {
  var mount = document.getElementById(@json($mountId));
  var url = mount && (mount.getAttribute('data-hydrate-url') || mount.dataset.url || '');
  if (!mount || !url) return;
  var load = function () {
    fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'text/html' } })
      .then(function (r) { return r.ok ? r.text() : ''; })
      .then(function (html) {
        if (!html) return;
        mount.innerHTML = html;
        mount.removeAttribute('aria-hidden');
      })
      .catch(function () {});
  };
  if ('requestIdleCallback' in window) requestIdleCallback(load, { timeout: 400 });
  else setTimeout(load, 50);
})();
</script>
