<script>
(function (global) {
    'use strict';

    let communityIndexRows = null;
    let communityIndexLoading = false;

    function expandCommunityRow(row) {
        return {
            name: row.n || '',
            city: row.c || '',
            lat: row.la ?? null,
            lng: row.lo ?? null,
            count: row.t ?? 0,
        };
    }

    function suffixFamily(value) {
        const match = String(value || '').toLowerCase().match(/(ville|borough|burg|wood|dale|view|park|hill|side|town|grove|valley)$/);
        return match ? match[1] : '';
    }

    function communityKeywordMatches(needle, name, city) {
        const nameLower = String(name || '').toLowerCase();
        const cityLower = String(city || '').toLowerCase();

        if (nameLower.includes(needle) || cityLower.includes(needle)) {
            return true;
        }

        if (needle.length < 5) {
            return false;
        }

        let prefixLen = 0;
        const max = Math.min(needle.length, nameLower.length);
        while (prefixLen < max && needle[prefixLen] === nameLower[prefixLen]) {
            prefixLen++;
        }

        const needleFamily = suffixFamily(needle);
        const nameFamily = suffixFamily(nameLower);
        if (needleFamily && needleFamily === nameFamily) {
            return prefixLen >= 6;
        }

        return false;
    }

    function filterCommunityIndex(keyword, limit) {
        const trimmed = String(keyword || '').trim().toLowerCase();
        if (!trimmed || trimmed.length < 2 || !Array.isArray(communityIndexRows)) {
            return null;
        }

        const matches = [];
        for (const row of communityIndexRows) {
            if (!communityKeywordMatches(trimmed, row.n, row.c)) {
                continue;
            }
            matches.push(expandCommunityRow(row));
        }

        matches.sort((a, b) => {
            const aName = a.name.toLowerCase();
            const bName = b.name.toLowerCase();
            const aStarts = aName.startsWith(trimmed) ? 0 : 1;
            const bStarts = bName.startsWith(trimmed) ? 0 : 1;
            if (aStarts !== bStarts) {
                return aStarts - bStarts;
            }
            if (a.count !== b.count) {
                return b.count - a.count;
            }
            return a.name.localeCompare(b.name);
        });

        return matches.slice(0, limit || 8);
    }

    function ensureCommunityIndexLoaded() {
        if (communityIndexRows || communityIndexLoading) {
            return;
        }

        communityIndexLoading = true;
        fetch('/api/v1/community-index', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((res) => (res.ok ? res.json() : []))
            .then((rows) => {
                communityIndexRows = Array.isArray(rows) ? rows : [];
            })
            .catch(() => {
                communityIndexRows = [];
            })
            .finally(() => {
                communityIndexLoading = false;
            });
    }

    function geocodeCommunityInBackground(communityName) {
        const name = String(communityName || '').trim();
        if (!name) {
            return Promise.resolve({ geocoded: 0 });
        }

        return fetch(`/api/v1/geocode-community?community=${encodeURIComponent(name)}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((res) => (res.ok ? res.json() : { geocoded: 0 }))
            .catch(() => ({ geocoded: 0 }));
    }

    global.SerikCommunitySearch = {
        ensureLoaded: ensureCommunityIndexLoaded,
        filter: filterCommunityIndex,
        isReady: () => Array.isArray(communityIndexRows),
        geocodeInBackground: geocodeCommunityInBackground,
    };

    // Defer index prefetch so it does not compete with first-paint assets.
    // Search still works immediately via /api/v1/community-suggestions fallback.
    if (typeof requestIdleCallback === 'function') {
        requestIdleCallback(function () { ensureCommunityIndexLoaded(); }, { timeout: 4000 });
    } else {
        setTimeout(ensureCommunityIndexLoaded, 2000);
    }
})(window);
</script>
