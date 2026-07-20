/**
 * Shared visitor location detection for homepage + map search.
 * Priority: session cache → browser GPS → IP geolocation → Ontario default.
 */
(function (window) {
    'use strict';

    const COOKIE_CITY = 'serik_visitor_city';
    const LS_CITY = 'serik_visitor_city';
    const SESSION_KEY = 'serik_visitor_location';
    const CITY_COOKIE_DAYS = 30;
    const SESSION_TTL_MS = 30 * 60 * 1000;

    const ONTARIO_BOUNDS = { south: 41.6, north: 56.9, west: -95.2, east: -74.0 };
    const ONTARIO_DEFAULT = {
        lat: 43.6532,
        lng: -79.3832,
        city: 'Toronto',
        zoom: 11,
        source: 'default',
    };

    let detectPromise = null;

    function roundCoord(value) {
        const n = Number(value);
        if (!Number.isFinite(n)) {
            return null;
        }

        return Math.round(n * 1e6) / 1e6;
    }

    function isLocalHost() {
        return ['localhost', '127.0.0.1'].includes(window.location.hostname);
    }

    function isInOntarioBounds(lat, lng) {
        return lat >= ONTARIO_BOUNDS.south
            && lat <= ONTARIO_BOUNDS.north
            && lng >= ONTARIO_BOUNDS.west
            && lng <= ONTARIO_BOUNDS.east;
    }

    function isOntarioRegion(data) {
        if (!data) {
            return false;
        }

        const code = String(data.region_code || data.regionCode || '').toUpperCase();
        const region = String(data.region || data.state || '').toLowerCase();

        return code === 'ON' || region.includes('ontario');
    }

    function getCityFromNominatimAddress(address) {
        if (!address) {
            return null;
        }

        const fields = ['city', 'town', 'municipality', 'village', 'hamlet', 'suburb', 'county'];

        for (let i = 0; i < fields.length; i++) {
            const value = address[fields[i]];
            if (value) {
                return String(value).trim();
            }
        }

        return null;
    }

    function isOntarioNominatimAddress(address) {
        if (!address) {
            return false;
        }

        const state = String(address.state || '').toLowerCase();

        return state.includes('ontario') || state === 'on';
    }

    function saveCity(city) {
        if (!city) {
            return;
        }

        const normalized = String(city).trim();
        if (!normalized || normalized.length < 2) {
            return;
        }

        try {
            localStorage.setItem(LS_CITY, normalized);
        } catch (e) {
            // ignore storage errors
        }

        const date = new Date();
        date.setTime(date.getTime() + CITY_COOKIE_DAYS * 24 * 60 * 60 * 1000);
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = COOKIE_CITY + '=' + encodeURIComponent(normalized)
            + '; expires=' + date.toUTCString()
            + '; path=/; SameSite=Lax' + secure;
    }

    function saveSessionLocation(location) {
        if (!location || !Number.isFinite(location.lat) || !Number.isFinite(location.lng)) {
            return;
        }

        const payload = {
            lat: roundCoord(location.lat),
            lng: roundCoord(location.lng),
            city: location.city ? String(location.city).trim() : null,
            source: location.source || 'unknown',
            accuracy: location.accuracy || null,
            ts: Date.now(),
        };

        try {
            sessionStorage.setItem(SESSION_KEY, JSON.stringify(payload));
        } catch (e) {
            // ignore storage errors
        }

        if (payload.city) {
            saveCity(payload.city);
        }
    }

    function getSessionLocation() {
        try {
            const raw = sessionStorage.getItem(SESSION_KEY);
            if (!raw) {
                return null;
            }

            const location = JSON.parse(raw);
            if (!location || !Number.isFinite(location.lat) || !Number.isFinite(location.lng)) {
                return null;
            }

            if (Date.now() - Number(location.ts || 0) > SESSION_TTL_MS) {
                sessionStorage.removeItem(SESSION_KEY);
                return null;
            }

            return {
                lat: roundCoord(location.lat),
                lng: roundCoord(location.lng),
                city: location.city || null,
                source: location.source || 'cache',
                accuracy: location.accuracy || null,
            };
        } catch (e) {
            return null;
        }
    }

    function getVisitorCity() {
        const match = document.cookie.match(/(?:^|;\s*)serik_visitor_city=([^;]+)/);
        if (match) {
            return decodeURIComponent(match[1]).trim();
        }

        try {
            return (localStorage.getItem(LS_CITY) || '').trim();
        } catch (e) {
            return '';
        }
    }

    function hasStoredCity() {
        return Boolean(getVisitorCity());
    }

    function fetchJson(url) {
        return fetch(url, { credentials: 'omit' })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .catch(function () {
                return null;
            });
    }

    function reverseGeocode(lat, lng) {
        const url = 'https://nominatim.openstreetmap.org/reverse?format=json&lat='
            + encodeURIComponent(lat)
            + '&lon='
            + encodeURIComponent(lng)
            + '&zoom=14';

        return fetchJson(url).then(function (data) {
            if (!data || !data.address) {
                return null;
            }

            if (!isOntarioNominatimAddress(data.address) && !isInOntarioBounds(lat, lng)) {
                return null;
            }

            return {
                city: getCityFromNominatimAddress(data.address),
                address: data.address,
            };
        });
    }

    function detectFromBrowser() {
        return new Promise(function (resolve, reject) {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation unavailable'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    resolve({
                        lat: roundCoord(position.coords.latitude),
                        lng: roundCoord(position.coords.longitude),
                        accuracy: position.coords.accuracy,
                        source: 'browser',
                    });
                },
                reject,
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000,
                }
            );
        });
    }

    function geocodeCityInOntario(city) {
        if (!city) {
            return Promise.resolve(null);
        }

        const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=ca&q='
            + encodeURIComponent(String(city).trim() + ', Ontario, Canada');

        return fetchJson(url).then(function (results) {
            if (!results || !results[0]) {
                return null;
            }

            const lat = roundCoord(parseFloat(results[0].lat));
            const lng = roundCoord(parseFloat(results[0].lon));

            if (!Number.isFinite(lat) || !Number.isFinite(lng) || !isInOntarioBounds(lat, lng)) {
                return null;
            }

            return { lat: lat, lng: lng };
        });
    }

    function buildCityOnlyLocation(city) {
        const normalizedCity = String(city).trim();

        return geocodeCityInOntario(normalizedCity).then(function (coords) {
            if (coords) {
                return {
                    lat: coords.lat,
                    lng: coords.lng,
                    city: normalizedCity,
                    source: 'ip',
                    accuracy: 'city',
                };
            }

            return {
                lat: ONTARIO_DEFAULT.lat,
                lng: ONTARIO_DEFAULT.lng,
                city: normalizedCity,
                source: 'ip',
                accuracy: 'city',
            };
        });
    }

    function detectFromIpInfo() {
        return fetchJson('https://ipinfo.io/json').then(function (data) {
            if (!data || !isOntarioRegion(data)) {
                return null;
            }

            if (data.loc) {
                const parts = String(data.loc).split(',');
                const lat = roundCoord(parseFloat(parts[0]));
                const lng = roundCoord(parseFloat(parts[1]));

                if (Number.isFinite(lat) && Number.isFinite(lng) && isInOntarioBounds(lat, lng)) {
                    return {
                        lat: lat,
                        lng: lng,
                        city: data.city ? String(data.city).trim() : null,
                        source: 'ip',
                        accuracy: 'ip',
                    };
                }
            }

            if (data.city) {
                return buildCityOnlyLocation(data.city);
            }

            return null;
        });
    }

    function detectFromIpApi() {
        return fetchJson('https://ipapi.co/json/').then(function (data) {
            if (!data || !isOntarioRegion(data)) {
                return null;
            }

            const lat = roundCoord(parseFloat(data.latitude));
            const lng = roundCoord(parseFloat(data.longitude));
            const city = data.city ? String(data.city).trim() : null;

            if (Number.isFinite(lat) && Number.isFinite(lng) && isInOntarioBounds(lat, lng)) {
                return {
                    lat: lat,
                    lng: lng,
                    city: city,
                    source: 'ip',
                    accuracy: 'ip',
                };
            }

            if (city) {
                return buildCityOnlyLocation(city);
            }

            return null;
        });
    }

    function detectFromIp() {
        if (isLocalHost()) {
            return detectFromIpInfo();
        }

        return detectFromIpApi().then(function (location) {
            return location || detectFromIpInfo();
        });
    }

    function detectLocation(options) {
        const settings = Object.assign({
            preferCached: true,
            preferBrowser: true,
        }, options || {});

        if (settings.preferCached) {
            const cached = getSessionLocation();
            if (cached) {
                return Promise.resolve(cached);
            }
        }

        if (detectPromise) {
            return detectPromise;
        }

        detectPromise = new Promise(function (resolve) {
            function finish(location) {
                saveSessionLocation(location);
                detectPromise = null;
                resolve(location);
            }

            function tryIp() {
                detectFromIp().then(function (ipLocation) {
                    if (ipLocation) {
                        finish(ipLocation);
                        return;
                    }

                    finish(Object.assign({}, ONTARIO_DEFAULT));
                });
            }

            if (!settings.preferBrowser) {
                tryIp();
                return;
            }

            detectFromBrowser()
                .then(function (browserLocation) {
                    if (!isInOntarioBounds(browserLocation.lat, browserLocation.lng)) {
                        tryIp();
                        return;
                    }

                    return reverseGeocode(browserLocation.lat, browserLocation.lng)
                        .then(function (geocode) {
                            finish({
                                lat: browserLocation.lat,
                                lng: browserLocation.lng,
                                city: geocode ? geocode.city : null,
                                source: 'browser',
                                accuracy: browserLocation.accuracy,
                            });
                        });
                })
                .catch(function () {
                    tryIp();
                });
        });

        return detectPromise;
    }

    function detectCityInBackground() {
        if (hasStoredCity() && getSessionLocation()) {
            return;
        }

        detectLocation({ preferCached: true, preferBrowser: true }).catch(function () {
            // silent background detection
        });
    }

    window.SerikVisitorLocation = {
        detectLocation: detectLocation,
        detectCityInBackground: detectCityInBackground,
        getVisitorCity: getVisitorCity,
        getSessionLocation: getSessionLocation,
        saveSessionLocation: saveSessionLocation,
        hasStoredCity: hasStoredCity,
        isInOntarioBounds: isInOntarioBounds,
        roundCoord: roundCoord,
        ONTARIO_DEFAULT: ONTARIO_DEFAULT,
        ONTARIO_BOUNDS: ONTARIO_BOUNDS,
    };
})(window);
