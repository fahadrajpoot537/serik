{{-- Async SEO nav mount — placed under Sold History on the homepage. --}}
@php
    $ajaxUrl = route('public.ajax.seo-city-navigation', ['context' => 'home']);
@endphp
<div id="serikHomeSeoNavMount" class="serik-home-seo-nav-mount" data-url="{{ $ajaxUrl }}" aria-hidden="true"></div>
<style>
.serik-home-seo-nav-mount {
    margin-top: 0;
}
.serik-home-seo-nav-mount .seo-city-navigation {
    margin-top: 0;
    border-top: none;
    padding-top: 1.25rem;
}
</style>
<script>
(function () {
  var mount = document.getElementById('serikHomeSeoNavMount');
  if (!mount || !mount.dataset.url || mount.dataset.loaded === '1') return;
  mount.dataset.loaded = '1';
  var url = mount.dataset.url;
  try {
    var city = (document.cookie.match(/(?:^|;\s*)serik_visitor_city=([^;]+)/) || [])[1];
    if (city) {
      city = decodeURIComponent(city.replace(/\+/g, ' '));
      url += (url.indexOf('?') >= 0 ? '&' : '?') + 'city=' + encodeURIComponent(city.toLowerCase().replace(/\s+/g, '-'));
    }
  } catch (e) {}
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
