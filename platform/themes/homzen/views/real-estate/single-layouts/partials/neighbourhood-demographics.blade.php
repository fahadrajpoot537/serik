<style>
.hs-census-section { margin: 28px 0 8px; }
.hs-census-section .hs-census-head { margin-bottom: 16px; }
.hs-census-section .hs-census-head h3 {
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0 0 4px;
    color: inherit;
}
.hs-census-section .hs-census-head p {
    margin: 0;
    color: #64748b;
    font-size: 0.92rem;
}
.hs-census-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.hs-census-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 12px;
    text-align: center;
    min-height: 88px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.hs-census-card .hs-census-label {
    font-size: 0.78rem;
    color: #64748b;
    line-height: 1.3;
    margin-bottom: 6px;
}
.hs-census-card .hs-census-value {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.hs-census-charts {
    margin-top: 28px;
    padding-top: 8px;
}
.hs-census-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 18px;
}
.hs-census-tab {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
    border-radius: 999px;
    padding: 8px 14px;
    font-size: 0.86rem;
    font-weight: 600;
    line-height: 1.2;
    cursor: pointer;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.hs-census-tab:hover { border-color: var(--primary-color, #0255a1); color: var(--primary-color, #0255a1); }
.hs-census-tab.is-active {
    background: var(--primary-color, #0255a1);
    border-color: var(--primary-color, #0255a1);
    color: #fff;
}
.hs-census-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 10px 16px;
    justify-content: center;
    margin: 0 0 14px;
}
.hs-census-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #334155;
    font-weight: 500;
}
.hs-census-legend-swatch {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    flex: 0 0 auto;
    box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.08);
}
.hs-census-chart-wrap {
    position: relative;
    width: 100%;
    max-width: none;
    margin: 0;
    min-height: 340px;
    padding: 16px 16px 8px;
    background: linear-gradient(180deg, #f0fdfa 0%, #f8fafc 45%, #fff7ed 100%);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
}
.hs-census-chart-wrap canvas {
    width: 100% !important;
    max-height: 440px;
}
.hs-census-source {
    margin-top: 14px;
    font-size: 0.8rem;
    color: #64748b;
    line-height: 1.45;
}
.hs-census-source a { color: #334155; text-decoration: underline; }
.hs-census-status {
    padding: 18px 14px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 10px;
    color: #64748b;
    font-size: 0.95rem;
}
.hs-census-section.is-loading .hs-census-status { animation: hsCensusPulse 1.2s ease-in-out infinite; }
@keyframes hsCensusPulse {
    0%, 100% { opacity: 0.7; }
    50% { opacity: 1; }
}
@media (max-width: 991px) {
    .hs-census-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 767px) {
    .hs-census-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .hs-census-chart-wrap { min-height: 280px; }
}
@media (max-width: 420px) {
    .hs-census-grid { grid-template-columns: 1fr; }
}
</style>

@if ($model instanceof \Botble\RealEstate\Models\Property)
@php
    $censusBootstrap = null;
    try {
        $censusCached = \Illuminate\Support\Facades\Cache::get(
            'census:property:' . (int) $model->getKey() . ':v12'
        );
        if (is_array($censusCached) && ($censusCached['status'] ?? null) === 'ok') {
            $censusBootstrap = $censusCached;
        }
    } catch (\Throwable $e) {
        $censusBootstrap = null;
    }
@endphp
<section
    class="single-property-element hs-census-section is-loading"
    id="neighbourhoodDemographics"
    data-property-census-id="{{ (int) $model->getKey() }}"
    aria-busy="true"
>
    <div class="hs-census-head">
        <h3>{{ __('Neighbourhood Demographics') }}</h3>
        <p>{{ __('2021 Census data for the property\'s local Census area') }}</p>
    </div>
    <div class="hs-census-body">
        <div class="hs-census-status">{{ __('Loading neighbourhood demographics…') }}</div>
    </div>
</section>
@if ($censusBootstrap)
<script type="application/json" id="hsCensusBootstrap">{!! json_encode($censusBootstrap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) !!}</script>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script>
(function () {
    var root = document.getElementById('neighbourhoodDemographics');
    if (!root) return;
    var propertyId = root.getAttribute('data-property-census-id');
    if (!propertyId) return;

    var chartInstance = null;
    var chartsData = [];

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderError(message) {
        root.classList.remove('is-loading');
        root.setAttribute('aria-busy', 'false');
        root.querySelector('.hs-census-body').innerHTML =
            '<div class="hs-census-status">' + esc(message || 'Census data temporarily unavailable.') + '</div>';
    }

    function whenChartReady(cb) {
        if (window.Chart) { cb(); return; }
        var tries = 0;
        var t = setInterval(function () {
            tries++;
            if (window.Chart || tries > 40) {
                clearInterval(t);
                cb();
            }
        }, 100);
    }

    function renderChart(index) {
        var chart = chartsData[index];
        if (!chart || !window.Chart) return;

        var canvas = root.querySelector('#hsCensusPie');
        var legendEl = root.querySelector('#hsCensusLegend');
        if (!canvas) return;

        var palette = [
            '#2563eb', '#06b6d4', '#10b981', '#eab308',
            '#f97316', '#ec4899', '#a855f7', '#14b8a6',
            '#ef4444', '#84cc16'
        ];
        var labels = chart.slices.map(function (s) { return s.label; });
        var values = chart.slices.map(function (s) { return s.percent != null ? s.percent : s.value; });
        var pointColors = chart.slices.map(function (s, i) {
            return palette[i % palette.length];
        });

        if (legendEl) {
            legendEl.innerHTML = (chart.slices || []).map(function (s, i) {
                var c = palette[i % palette.length];
                return '<span class="hs-census-legend-item">' +
                    '<span class="hs-census-legend-swatch" style="background:' + esc(c) + '"></span>' +
                    esc(s.label) + ' ' + esc(s.display || ((s.percent != null ? s.percent + '%' : '') + (s.count != null ? ' (' + s.count + ')' : ''))) +
                    '</span>';
            }).join('');
        }

        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }

        var ctx = canvas.getContext('2d');
        var gradient = ctx.createLinearGradient(0, 0, 0, 320);
        gradient.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
        gradient.addColorStop(0.45, 'rgba(16, 185, 129, 0.22)');
        gradient.addColorStop(1, 'rgba(249, 115, 22, 0.08)');

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: chart.label || 'Share (%)',
                    data: values,
                    borderColor: '#2563eb',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: pointColors,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointHoverBorderWidth: 3,
                    tension: 0.35,
                    fill: true,
                    segment: {
                        borderColor: function (ctx) {
                            var i = ctx.p0DataIndex;
                            return pointColors[i] || '#2563eb';
                        }
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.92)',
                        titleColor: '#fff',
                        bodyColor: '#e2e8f0',
                        padding: 10,
                        callbacks: {
                            label: function (ctx) {
                                var slice = chart.slices[ctx.dataIndex] || {};
                                if (slice.display) {
                                    return (ctx.label || '') + ': ' + slice.display;
                                }
                                var pct = slice.percent != null ? slice.percent + '%' : ctx.formattedValue;
                                var count = slice.count != null ? ' (' + slice.count + ')' : '';
                                return (ctx.label || '') + ': ' + pct + count;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0,
                            autoSkip: true,
                            color: '#475569',
                            font: { size: 11, weight: '500' }
                        },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#475569',
                            callback: function (v) { return v + '%'; }
                        },
                        grid: { color: 'rgba(37, 99, 235, 0.12)' },
                        title: {
                            display: true,
                            text: 'Share (%)',
                            color: '#334155',
                            font: { size: 12, weight: '600' }
                        }
                    }
                }
            }
        });
    }

    function bindTabs() {
        var tabs = root.querySelectorAll('.hs-census-tab');
        tabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                tabs.forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                renderChart(parseInt(btn.getAttribute('data-chart-index'), 10) || 0);
            });
        });
    }

    function renderOk(data) {
        var metrics = Array.isArray(data.metrics) ? data.metrics : [];
        if (!metrics.length) {
            renderError(data.message || 'Census data temporarily unavailable.');
            return;
        }

        chartsData = Array.isArray(data.charts) ? data.charts : [];

        var cards = metrics.map(function (m) {
            return (
                '<div class="hs-census-card">' +
                    '<div class="hs-census-label">' + esc(m.label) + '</div>' +
                    '<div class="hs-census-value">' + esc(m.display) + '</div>' +
                '</div>'
            );
        }).join('');

        var chartsHtml = '';
        if (chartsData.length) {
            var tabs = chartsData.map(function (c, i) {
                return '<button type="button" class="hs-census-tab' + (i === 0 ? ' is-active' : '') +
                    '" data-chart-index="' + i + '">' + esc(c.label) + '</button>';
            }).join('');

            chartsHtml =
                '<div class="hs-census-charts">' +
                    '<div class="hs-census-tabs" role="tablist">' + tabs + '</div>' +
                    '<div class="hs-census-legend" id="hsCensusLegend"></div>' +
                    '<div class="hs-census-chart-wrap"><canvas id="hsCensusPie" aria-label="Neighbourhood demographics chart"></canvas></div>' +
                '</div>';
        }

        var sourceBits = [];
        sourceBits.push('Source: ' + esc(data.source || 'Statistics Canada — 2021 Census'));
        if (data.geography_level && data.geography_level !== 'da') {
            var levelLabel = data.geography_level === 'ada'
                ? 'Aggregate dissemination area'
                : (data.geography_level === 'csd' ? 'Census subdivision' : String(data.geography_level));
            sourceBits.push('Geography: ' + esc(levelLabel));
        }

        root.classList.remove('is-loading');
        root.setAttribute('aria-busy', 'false');
        root.querySelector('.hs-census-body').innerHTML =
            '<div class="hs-census-grid">' + cards + '</div>' +
            chartsHtml +
            '<div class="hs-census-source">' + sourceBits.join('<br>') + '</div>';

        if (chartsData.length) {
            bindTabs();
            whenChartReady(function () { renderChart(0); });
        }
    }

    // Absolute URL — map property popup loads this page inside an iframe (?iframe=1).
    var url;
    try {
        url = new URL('/api/v1/property-census/' + encodeURIComponent(propertyId), window.location.href).toString();
    } catch (e) {
        url = (window.location.origin || '') + '/api/v1/property-census/' + encodeURIComponent(propertyId);
    }
    var isIframeEmbed = false;
    try {
        isIframeEmbed = /(?:\?|&)iframe=1(?:&|$)/.test(String(window.location.search || ''))
            || (window.self !== window.top);
    } catch (e) {
        isIframeEmbed = true;
    }

    function applyCensusPayload(data) {
        if (!data || typeof data !== 'object') {
            return false;
        }
        var msg = String(data.message || '');
        if (/server error/i.test(msg) || /maximum execution/i.test(msg)) {
            msg = 'Census data temporarily unavailable.';
        }
        if (data.status && data.status !== 'ok') {
            renderError(msg || 'Census data temporarily unavailable.');
            return true;
        }
        if (data.success === false) {
            renderError(msg || 'Census data temporarily unavailable.');
            return true;
        }
        if (data.status === 'ok' || data.success === true || (data.metrics && data.metrics.length)) {
            var payload = data;
            if (data.success == null) {
                payload = Object.assign({ success: true }, data);
            }
            renderOk(payload);
            return true;
        }
        return false;
    }

    function loadCensus(isRetry) {
        if (!isRetry) {
            if (window.__serikCensusStarted) {
                return;
            }
            window.__serikCensusStarted = true;
        }

        // DA census can take ~90–120s cold; keep client wait aligned with server budget.
        var controller = (typeof AbortController !== 'undefined') ? new AbortController() : null;
        var timer = setTimeout(function () {
            if (controller) controller.abort();
        }, 150000);

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            signal: controller ? controller.signal : undefined,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) {
                return r.text().then(function (text) {
                    var j = null;
                    try { j = JSON.parse(text); } catch (e) { j = null; }
                    return { ok: r.ok, status: r.status, json: j };
                });
            })
            .then(function (res) {
                clearTimeout(timer);
                if (!res.json) {
                    // Laravel FatalError HTML pages say "Server Error" — never show that raw.
                    if (!isRetry) {
                        window.setTimeout(function () { loadCensus(true); }, 1200);
                        return;
                    }
                    renderError('Census data temporarily unavailable.');
                    return;
                }
                if (!applyCensusPayload(res.json)) {
                    renderError('Census data temporarily unavailable.');
                }
            })
            .catch(function () {
                clearTimeout(timer);
                if (!isRetry) {
                    window.setTimeout(function () { loadCensus(true); }, 1200);
                    return;
                }
                renderError('Census data temporarily unavailable.');
            });
    }

    // Prefer server-embedded cache (map iframe skips a long API round-trip when
    // the full property page already warmed census:property:{id}:v12).
    var bootEl = document.getElementById('hsCensusBootstrap');
    var bootData = null;
    if (bootEl) {
        try { bootData = JSON.parse(bootEl.textContent || ''); } catch (e) { bootData = null; }
    }
    if (bootData && applyCensusPayload(bootData)) {
        window.__serikCensusStarted = true;
        return;
    }

    // Map popup iframe: IntersectionObserver often never fires (odd overflow roots),
    // so kick off census promptly instead of waiting for scroll.
    if (isIframeEmbed) {
        window.setTimeout(function () { loadCensus(false); }, 300);
    } else if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            if (entries.some(function (e) { return e.isIntersecting; })) {
                io.disconnect();
                loadCensus(false);
            }
        }, { rootMargin: '200px 0px' });
        io.observe(root);
        // Safety: if IO never intersects (rare layout), still load.
        window.setTimeout(function () { loadCensus(false); }, 12000);
    } else if ('requestIdleCallback' in window) {
        requestIdleCallback(function () { setTimeout(function () { loadCensus(false); }, 1000); }, { timeout: 5000 });
    } else {
        setTimeout(function () { loadCensus(false); }, 3000);
    }
})();
</script>
@endif
