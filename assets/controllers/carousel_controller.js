import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['track', 'slide', 'dot', 'prev', 'next'];
    static values  = {
        current:  { type: Number,  default: 0 },
        autoPlay: { type: Boolean, default: true },
        delay:    { type: Number,  default: 5000 },
    };

    connect() {
        this._buildDots();
        this._update();
        if (this.autoPlayValue) this._startAutoPlay();

        // Swipe support
        this._startX = null;
        this.element.addEventListener('touchstart', e => { this._startX = e.touches[0].clientX; }, { passive: true });
        this.element.addEventListener('touchend',   e => {
            if (this._startX === null) return;
            const dx = e.changedTouches[0].clientX - this._startX;
            if (Math.abs(dx) > 40) dx < 0 ? this.next() : this.prev();
            this._startX = null;
        }, { passive: true });
    }

    disconnect() {
        this._stopAutoPlay();
    }

    prev() {
        this.currentValue = (this.currentValue - 1 + this.slideTargets.length) % this.slideTargets.length;
        this._resetAutoPlay();
    }

    next() {
        this.currentValue = (this.currentValue + 1) % this.slideTargets.length;
        this._resetAutoPlay();
    }

    goTo(event) {
        this.currentValue = parseInt(event.currentTarget.dataset.index, 10);
        this._resetAutoPlay();
    }

    currentValueChanged() {
        this._update();
    }

    // ── private ────────────────────────────────────────────────

    _update() {
        const total = this.slideTargets.length;
        if (!total) return;

        this.trackTarget.style.transform = `translateX(-${this.currentValue * 100}%)`;

        // Dots
        this.dotTargets.forEach((dot, i) => {
            dot.classList.toggle('active', i === this.currentValue);
            dot.setAttribute('aria-current', i === this.currentValue ? 'true' : 'false');
        });

        // Arrows (disable at bounds if not looping — here we loop so always enabled)
        if (this.hasPrevTarget) this.prevTarget.disabled = false;
        if (this.hasNextTarget) this.nextTarget.disabled = false;
    }

    _buildDots() {
        const container = this.element.querySelector('[data-carousel-dots]');
        if (!container) return;
        this.slideTargets.forEach((_, i) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.carouselTarget = 'dot';
            btn.dataset.index = i;
            btn.dataset.action = 'click->carousel#goTo';
            btn.setAttribute('aria-label', `Slide ${i + 1}`);
            btn.classList.add('carousel-dot');
            container.appendChild(btn);
        });
    }

    _startAutoPlay() {
        this._timer = setInterval(() => this.next(), this.delayValue);
    }

    _stopAutoPlay() {
        clearInterval(this._timer);
    }

    _resetAutoPlay() {
        if (!this.autoPlayValue) return;
        this._stopAutoPlay();
        this._startAutoPlay();
    }
}
