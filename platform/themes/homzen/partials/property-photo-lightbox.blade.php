<style>
.serik-photo-lightbox {
    position: fixed;
    inset: 0;
    z-index: 999999;
    background: rgba(0, 0, 0, 0.92);
    display: none;
    align-items: center;
    justify-content: center;
    touch-action: none;
}

.serik-photo-lightbox.is-open {
    display: flex;
}

.serik-photo-lightbox__img {
    max-width: min(96vw, 1400px);
    max-height: 88vh;
    object-fit: contain;
    user-select: none;
    -webkit-user-drag: none;
}

.serik-photo-lightbox__close,
.serik-photo-lightbox__nav {
    position: absolute;
    border: none;
    background: rgba(255, 255, 255, 0.92);
    color: #161e2d;
    cursor: pointer;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.serik-photo-lightbox__close {
    top: 16px;
    right: 16px;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    font-size: 28px;
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
    bottom: 18px;
    left: 50%;
    transform: translateX(-50%);
    color: #fff;
    background: rgba(0, 0, 0, 0.55);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

@media (max-width: 768px) {
    .serik-photo-lightbox__nav {
        width: 40px;
        height: 40px;
        font-size: 26px;
    }

    .serik-photo-lightbox__nav.prev { left: 8px; }
    .serik-photo-lightbox__nav.next { right: 8px; }

    .serik-photo-lightbox__close {
        top: 10px;
        right: 10px;
    }
}
</style>

<div id="serikPhotoLightbox" class="serik-photo-lightbox" aria-hidden="true">
    <button type="button" class="serik-photo-lightbox__close" aria-label="Close">&times;</button>
    <button type="button" class="serik-photo-lightbox__nav prev" aria-label="Previous">&#8249;</button>
    <img class="serik-photo-lightbox__img" src="" alt="">
    <button type="button" class="serik-photo-lightbox__nav next" aria-label="Next">&#8250;</button>
    <div class="serik-photo-lightbox__counter">1 / 1</div>
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
    const counterEl = root.querySelector('.serik-photo-lightbox__counter');
    const closeBtn = root.querySelector('.serik-photo-lightbox__close');
    const prevBtn = root.querySelector('.serik-photo-lightbox__nav.prev');
    const nextBtn = root.querySelector('.serik-photo-lightbox__nav.next');

    let images = [];
    let index = 0;
    let touchStartX = 0;

    function render() {
        if (!images.length) {
            return;
        }

        index = ((index % images.length) + images.length) % images.length;
        imgEl.src = images[index];
        counterEl.textContent = `${index + 1} / ${images.length}`;
        prevBtn.style.display = images.length > 1 ? 'flex' : 'none';
        nextBtn.style.display = images.length > 1 ? 'flex' : 'none';
    }

    function close() {
        root.classList.remove('is-open');
        root.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        imgEl.src = '';
        images = [];
    }

    function open(list, startIndex = 0) {
        images = (Array.isArray(list) ? list : []).filter(Boolean);
        if (!images.length) {
            return;
        }

        index = Number(startIndex) || 0;
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
    prevBtn.addEventListener('click', () => step(-1));
    nextBtn.addEventListener('click', () => step(1));

    root.addEventListener('click', (e) => {
        if (e.target === root) {
            close();
        }
    });

    root.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0]?.clientX || 0;
    }, { passive: true });

    root.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0]?.clientX || 0;
        const diff = touchEndX - touchStartX;
        if (Math.abs(diff) < 40) {
            return;
        }
        step(diff > 0 ? -1 : 1);
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
        }
    });

    window.SerikPhotoLightbox = { open, close };
})();
</script>
