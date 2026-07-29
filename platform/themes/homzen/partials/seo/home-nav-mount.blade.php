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

/* Homepage mobile accordion — kept here so it applies even when nav HTML is AJAX-injected */
@media (max-width: 767.98px) {
    .serik-home-seo-nav-mount .seo-city-navigation--home {
        padding: 1rem 0 1.25rem !important;
    }
    .serik-home-seo-nav-mount .seo-city-navigation--home .seo-nav-row {
        row-gap: 0.65rem !important;
        margin: 0 !important;
    }
    .serik-home-seo-nav-mount .seo-city-navigation--home .seo-nav-col {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
    }
    .serik-home-seo-nav-mount .seo-city-navigation--home .seo-nav-title {
        margin: 0 !important;
        padding: 0 !important;
        border-bottom: 0 !important;
        font-size: 1rem !important;
    }
    .serik-home-seo-nav-mount .seo-city-navigation--home .seo-nav-block {
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        background: #fff !important;
        padding: 0.75rem 0.9rem !important;
        height: auto !important;
    }
    .serik-home-seo-nav-mount .seo-city-navigation--home .seo-nav-toggle {
        display: flex !important;
        width: 100% !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 0.75rem !important;
        border: 0 !important;
        background: transparent !important;
        padding: 0 !important;
        text-align: left !important;
        color: inherit !important;
        font: inherit !important;
        font-weight: 700 !important;
        cursor: pointer !important;
    }
    .serik-home-seo-nav-mount .seo-city-navigation--home .seo-nav-title-static {
        display: none !important;
    }
    .serik-home-seo-nav-mount .seo-city-navigation--home .seo-nav-toggle__icon {
        flex-shrink: 0 !important;
        width: 1.5rem !important;
        height: 1.5rem !important;
        border-radius: 999px !important;
        border: 1px solid #cbd5e1 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.05rem !important;
        line-height: 1 !important;
        color: #0255a1 !important;
        font-weight: 600 !important;
        background: #fff !important;
    }
    .serik-home-seo-nav-mount .seo-city-navigation--home .seo-nav-block.is-open .seo-nav-toggle__icon {
        transform: rotate(45deg);
        background: #e8f2fc !important;
    }
    .serik-home-seo-nav-mount .seo-city-navigation--home .seo-nav-list {
        display: none !important;
        margin-top: 0.65rem !important;
        padding-top: 0.55rem !important;
        border-top: 1px solid #e8ecf1 !important;
        max-height: none !important;
        overflow: visible !important;
    }
    .serik-home-seo-nav-mount .seo-city-navigation--home .seo-nav-block.is-open .seo-nav-list {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.25rem !important;
        max-height: none !important;
        overflow: visible !important;
    }
}
@media (min-width: 768px) {
    .serik-home-seo-nav-mount .seo-city-navigation--home .seo-nav-toggle {
        display: none !important;
    }
    .serik-home-seo-nav-mount .seo-city-navigation--home .seo-nav-title-static {
        display: block !important;
    }
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

  function enhanceAccordion(root) {
    if (!root) return;
    root.querySelectorAll('.seo-nav-block').forEach(function (block, index) {
      block.setAttribute('data-seo-nav-block', '');
      var title = block.querySelector('.seo-nav-title');
      var list = block.querySelector('.seo-nav-list');
      if (!title || !list) return;
      if (block.querySelector('[data-seo-nav-toggle]')) return;

      var labelText = (title.textContent || '').replace(/\s+/g, ' ').trim();
      var sectionId = list.id || ('seo-nav-home-' + index);
      list.id = sectionId;

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'seo-nav-toggle';
      btn.setAttribute('data-seo-nav-toggle', '');
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-controls', sectionId);
      btn.innerHTML = '<span class="seo-nav-toggle__label"></span><span class="seo-nav-toggle__icon" aria-hidden="true">+</span>';
      btn.querySelector('.seo-nav-toggle__label').textContent = labelText;

      var staticTitle = document.createElement('span');
      staticTitle.className = 'seo-nav-title-static';
      staticTitle.textContent = labelText;

      title.textContent = '';
      title.appendChild(btn);
      title.appendChild(staticTitle);
    });
  }

  var load = function () {
    fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'text/html' } })
      .then(function (r) { return r.ok ? r.text() : ''; })
      .then(function (html) {
        if (!html) return;
        mount.innerHTML = html;
        enhanceAccordion(mount);
        mount.removeAttribute('aria-hidden');
      })
      .catch(function () {});
  };
  if ('requestIdleCallback' in window) requestIdleCallback(load, { timeout: 400 });
  else setTimeout(load, 50);

  if (!window.__serikSeoNavAccordionBound) {
    window.__serikSeoNavAccordionBound = true;
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-seo-nav-toggle]');
      if (!btn || !mount.contains(btn)) return;
      var block = btn.closest('[data-seo-nav-block]');
      if (!block) return;
      e.preventDefault();
      var open = block.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
})();
</script>
