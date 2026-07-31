<style>
.serik-photo-lightbox {
    --spl-bg: rgba(8, 12, 20, 0.94);
    --spl-accent: #c9a227;
    --spl-radius: 10px;
    position: fixed;
    inset: 0;
    z-index: 999999;
    background: var(--spl-bg);
    display: none;
    align-items: stretch;
    justify-content: center;
    touch-action: none;
    padding: 0;
    color: #fff;
}

.serik-photo-lightbox.is-open {
    display: flex;
}

.serik-photo-lightbox__shell {
    display: grid;
    grid-template-rows: 1fr auto;
    width: min(1200px, 100%);
    height: 100%;
    max-height: 100dvh;
    margin: 0 auto;
    position: relative;
    gap: 0;
}

.serik-photo-lightbox__stage {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 0;
    padding: 56px 64px 12px;
    overflow: hidden;
}

.serik-photo-lightbox__img-wrap {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: var(--spl-radius);
    cursor: zoom-in;
}

.serik-photo-lightbox__img-wrap.is-zoomed {
    cursor: zoom-out;
    overflow: auto;
}

.serik-photo-lightbox__img {
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    user-select: none;
    -webkit-user-drag: none;
    opacity: 0;
    transition: opacity 0.22s ease;
    transform-origin: center center;
}

.serik-photo-lightbox__img.is-visible {
    opacity: 1;
}

.serik-photo-lightbox__img-wrap.is-zoomed .serik-photo-lightbox__img {
    max-width: none;
    max-height: none;
    width: auto;
    height: auto;
    transform: scale(1.85);
}

.serik-photo-lightbox__close,
.serik-photo-lightbox__nav,
.serik-photo-lightbox__zoom {
    position: absolute;
    border: none;
    background: rgba(255, 255, 255, 0.94);
    color: #161e2d;
    cursor: pointer;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
}

.serik-photo-lightbox__close {
    top: 14px;
    right: 14px;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    font-size: 28px;
}

.serik-photo-lightbox__zoom {
    top: 14px;
    right: 66px;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    font-size: 18px;
    font-weight: 700;
}

.serik-photo-lightbox__nav {
    top: 50%;
    transform: translateY(-50%);
    width: 46px;
    height: 46px;
    border-radius: 50%;
    font-size: 30px;
    font-weight: 700;
}

.serik-photo-lightbox__nav.prev { left: 12px; }
.serik-photo-lightbox__nav.next { right: 12px; }

.serik-photo-lightbox__counter {
    position: absolute;
    top: 18px;
    left: 18px;
    color: #fff;
    background: rgba(0, 0, 0, 0.55);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    z-index: 3;
    letter-spacing: 0.02em;
}

.serik-photo-lightbox__thumbs {
    display: flex;
    gap: 8px;
    padding: 12px 16px 18px;
    overflow-x: auto;
    overflow-y: hidden;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    background: rgba(0, 0, 0, 0.35);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.serik-photo-lightbox__thumb {
    flex: 0 0 auto;
    width: 78px;
    height: 58px;
    border-radius: 6px;
    overflow: hidden;
    border: 2px solid transparent;
    padding: 0;
    background: #1a2230;
    cursor: pointer;
    opacity: 0.72;
    transition: opacity 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
}

.serik-photo-lightbox__thumb:hover {
    opacity: 1;
}

.serik-photo-lightbox__thumb.is-active {
    opacity: 1;
    border-color: var(--spl-accent);
    transform: translateY(-1px);
}

.serik-photo-lightbox__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

@media (min-width: 992px) {
    .serik-photo-lightbox__shell {
        grid-template-columns: 1fr 148px;
        grid-template-rows: 1fr;
        width: min(1280px, 100%);
        max-height: min(92vh, 900px);
        margin: auto;
        border-radius: 14px;
        overflow: hidden;
        background: #0b1018;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
    }

    .serik-photo-lightbox.is-open {
        padding: 3vh 2vw;
        align-items: center;
    }

    .serik-photo-lightbox__stage {
        padding: 52px 56px 24px;
        grid-column: 1;
        grid-row: 1;
    }

    .serik-photo-lightbox__thumbs {
        grid-column: 2;
        grid-row: 1;
        flex-direction: column;
        overflow-x: hidden;
        overflow-y: auto;
        padding: 56px 12px 16px;
        border-top: none;
        border-left: 1px solid rgba(255, 255, 255, 0.08);
    }

    .serik-photo-lightbox__thumb {
        width: 100%;
        height: 78px;
    }
}

@media (max-width: 768px) {
    .serik-photo-lightbox__stage {
        padding: 52px 12px 8px;
    }

    .serik-photo-lightbox__nav {
        width: 40px;
        height: 40px;
        font-size: 26px;
    }

    .serik-photo-lightbox__nav.prev { left: 6px; }
    .serik-photo-lightbox__nav.next { right: 6px; }

    .serik-photo-lightbox__close {
        top: 10px;
        right: 10px;
    }

    .serik-photo-lightbox__zoom {
        top: 10px;
        right: 58px;
    }

    .serik-photo-lightbox__thumb {
        width: 68px;
        height: 50px;
    }
}
</style>

<div id="serikPhotoLightbox" class="serik-photo-lightbox" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Property photo gallery">
    <div class="serik-photo-lightbox__shell">
        <div class="serik-photo-lightbox__stage">
            <div class="serik-photo-lightbox__counter">1 / 1</div>
            <button type="button" class="serik-photo-lightbox__zoom" aria-label="Toggle zoom" title="Zoom">&#43;</button>
            <button type="button" class="serik-photo-lightbox__close" aria-label="Close">&times;</button>
            <button type="button" class="serik-photo-lightbox__nav prev" aria-label="Previous">&#8249;</button>
            <div class="serik-photo-lightbox__img-wrap">
                <img class="serik-photo-lightbox__img" src="" alt="Property photo" decoding="async">
            </div>
            <button type="button" class="serik-photo-lightbox__nav next" aria-label="Next">&#8250;</button>
        </div>
        <div class="serik-photo-lightbox__thumbs" role="listbox" aria-label="Photo thumbnails"></div>
    </div>
</div>

<script>
(function () {
    if (window.SerikPhotoLightbox) {
        return;
    }

    const root = document.getElementById('serikPhotoLightbox');
    if (!root) {
        return;
    }

    const imgEl = root.querySelector('.serik-photo-lightbox__img');
    const imgWrap = root.querySelector('.serik-photo-lightbox__img-wrap');
    const thumbsEl = root.querySelector('.serik-photo-lightbox__thumbs');
    const counterEl = root.querySelector('.serik-photo-lightbox__counter');
    const closeBtn = root.querySelector('.serik-photo-lightbox__close');
    const zoomBtn = root.querySelector('.serik-photo-lightbox__zoom');
    const prevBtn = root.querySelector('.serik-photo-lightbox__nav.prev');
    const nextBtn = root.querySelector('.serik-photo-lightbox__nav.next');

    let images = [];
    let index = 0;
    let touchStartX = 0;
    let touchStartY = 0;
    let zoomed = false;
    let fadeTimer = null;

    function setZoom(on) {
        zoomed = !!on;
        imgWrap.classList.toggle('is-zoomed', zoomed);
        zoomBtn.setAttribute('aria-pressed', zoomed ? 'true' : 'false');
        if (!zoomed) {
            imgWrap.scrollTop = 0;
            imgWrap.scrollLeft = 0;
        }
    }

    function buildThumbs() {
        thumbsEl.innerHTML = '';
        images.forEach((src, i) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'serik-photo-lightbox__thumb' + (i === index ? ' is-active' : '');
            btn.setAttribute('role', 'option');
            btn.setAttribute('aria-selected', i === index ? 'true' : 'false');
            btn.setAttribute('aria-label', 'Photo ' + (i + 1));
            btn.dataset.index = String(i);

            const img = document.createElement('img');
            img.alt = '';
            img.decoding = 'async';
            if (i <= index + 4 || i < 8) {
                img.src = src;
            } else {
                img.loading = 'lazy';
                img.dataset.src = src;
                // Lazy-load when near viewport via IntersectionObserver below
                img.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
            }
            btn.appendChild(img);
            thumbsEl.appendChild(btn);
        });

        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        delete img.dataset.src;
                    }
                    io.unobserve(img);
                });
            }, { root: thumbsEl, rootMargin: '80px' });
            thumbsEl.querySelectorAll('img[data-src]').forEach((img) => io.observe(img));
        } else {
            thumbsEl.querySelectorAll('img[data-src]').forEach((img) => {
                img.src = img.dataset.src;
                delete img.dataset.src;
            });
        }
    }

    function syncThumbActive() {
        const thumbs = thumbsEl.querySelectorAll('.serik-photo-lightbox__thumb');
        thumbs.forEach((btn, i) => {
            const active = i === index;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
            if (active) {
                btn.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
                const img = btn.querySelector('img');
                if (img && img.dataset.src) {
                    img.src = img.dataset.src;
                    delete img.dataset.src;
                }
            }
        });

        // Prefetch neighbors
        [index - 1, index + 1, index + 2].forEach((i) => {
            if (i < 0 || i >= images.length) return;
            const preload = new Image();
            preload.decoding = 'async';
            preload.src = images[i];
        });
    }

    function render() {
        if (!images.length) {
            return;
        }

        index = ((index % images.length) + images.length) % images.length;
        counterEl.textContent = `${index + 1} / ${images.length}`;
        prevBtn.style.display = images.length > 1 ? 'flex' : 'none';
        nextBtn.style.display = images.length > 1 ? 'flex' : 'none';
        setZoom(false);

        imgEl.classList.remove('is-visible');
        if (fadeTimer) {
            clearTimeout(fadeTimer);
        }

        const nextSrc = images[index];
        const apply = () => {
            imgEl.src = nextSrc;
            requestAnimationFrame(() => imgEl.classList.add('is-visible'));
        };

        fadeTimer = setTimeout(apply, 80);
        syncThumbActive();
    }

    function close() {
        root.classList.remove('is-open');
        root.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        imgEl.src = '';
        imgEl.classList.remove('is-visible');
        images = [];
        thumbsEl.innerHTML = '';
        setZoom(false);
    }

    function open(list, startIndex = 0) {
        images = (Array.isArray(list) ? list : []).filter(Boolean);
        if (!images.length) {
            return;
        }

        index = Number(startIndex) || 0;
        buildThumbs();
        render();
        root.classList.add('is-open');
        root.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function step(delta) {
        if (images.length <= 1) {
            return;
        }
        index += delta;
        render();
    }

    closeBtn.addEventListener('click', close);
    zoomBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        setZoom(!zoomed);
    });
    prevBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        step(-1);
    });
    nextBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        step(1);
    });

    imgWrap.addEventListener('click', (e) => {
        e.stopPropagation();
        setZoom(!zoomed);
    });

    thumbsEl.addEventListener('click', (e) => {
        const btn = e.target.closest('.serik-photo-lightbox__thumb');
        if (!btn) return;
        e.stopPropagation();
        const next = Number(btn.dataset.index);
        if (!Number.isFinite(next) || next === index) return;
        index = next;
        render();
    });

    root.addEventListener('click', (e) => {
        if (e.target === root) {
            close();
        }
    });

    root.addEventListener('touchstart', (e) => {
        if (zoomed) return;
        touchStartX = e.changedTouches[0]?.clientX || 0;
        touchStartY = e.changedTouches[0]?.clientY || 0;
    }, { passive: true });

    root.addEventListener('touchend', (e) => {
        if (zoomed) return;
        const touchEndX = e.changedTouches[0]?.clientX || 0;
        const touchEndY = e.changedTouches[0]?.clientY || 0;
        const diffX = touchEndX - touchStartX;
        const diffY = touchEndY - touchStartY;
        if (Math.abs(diffX) < 40 || Math.abs(diffX) < Math.abs(diffY)) {
            return;
        }
        step(diffX > 0 ? -1 : 1);
    }, { passive: true });

    document.addEventListener('keydown', (e) => {
        if (!root.classList.contains('is-open')) {
            return;
        }
        if (e.key === 'Escape') {
            close();
        } else if (e.key === 'ArrowLeft') {
            step(-1);
        } else if (e.key === 'ArrowRight') {
            step(1);
        } else if (e.key === '+' || e.key === '=') {
            setZoom(true);
        } else if (e.key === '-') {
            setZoom(false);
        }
    });

    window.SerikPhotoLightbox = { open, close };
})();
</script>
