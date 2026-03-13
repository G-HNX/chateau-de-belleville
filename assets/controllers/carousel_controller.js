/**
 * Contrôleur Stimulus : Carousel
 *
 * Gère un carrousel d'images avec défilement automatique, navigation
 * par flèches/points et support du swipe tactile sur mobile.
 *
 * Targets : track, slide, dot, prev, next
 * Values  : current (index), autoPlay, delay (ms)
 */

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

        // Détection du swipe tactile (mobile)
        this._startX = null;
        this.element.addEventListener('touchstart', e => { this._startX = e.touches[0].clientX; }, { passive: true });
        this.element.addEventListener('touchend',   e => {
            if (this._startX === null) return;
            const dx = e.changedTouches[0].clientX - this._startX;
            // Seuil de 40px pour distinguer un swipe d'un simple tap
            if (Math.abs(dx) > 40) dx < 0 ? this.next() : this.prev();
            this._startX = null;
        }, { passive: true });
    }

    disconnect() {
        this._stopAutoPlay();
    }

    /** Slide précédente (boucle circulaire) */
    prev() {
        this.currentValue = (this.currentValue - 1 + this.slideTargets.length) % this.slideTargets.length;
        this._resetAutoPlay();
    }

    /** Slide suivante (boucle circulaire) */
    next() {
        this.currentValue = (this.currentValue + 1) % this.slideTargets.length;
        this._resetAutoPlay();
    }

    /** Navigation directe vers une slide via un point indicateur */
    goTo(event) {
        this.currentValue = parseInt(event.currentTarget.dataset.index, 10);
        this._resetAutoPlay();
    }

    /** Callback Stimulus : met a jour l'affichage quand l'index change */
    currentValueChanged() {
        this._update();
    }

    // ── methodes privees ──────────────────────────────────────

    /** Met a jour la position du track et l'etat actif des points */
    _update() {
        const total = this.slideTargets.length;
        if (!total) return;

        // Decalage horizontal via translateX pour afficher la slide courante
        this.trackTarget.style.transform = `translateX(-${this.currentValue * 100}%)`;

        // Mise a jour des points indicateurs
        this.dotTargets.forEach((dot, i) => {
            dot.classList.toggle('active', i === this.currentValue);
            dot.setAttribute('aria-current', i === this.currentValue ? 'true' : 'false');
        });

        // Les fleches restent toujours actives car le carousel boucle
        if (this.hasPrevTarget) this.prevTarget.disabled = false;
        if (this.hasNextTarget) this.nextTarget.disabled = false;
    }

    /** Cree dynamiquement les boutons-points de navigation */
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

    /** Demarre le defilement automatique selon le delai configure */
    _startAutoPlay() {
        this._timer = setInterval(() => this.next(), this.delayValue);
    }

    _stopAutoPlay() {
        clearInterval(this._timer);
    }

    /** Reinitialise le timer d'autoplay apres une interaction manuelle */
    _resetAutoPlay() {
        if (!this.autoPlayValue) return;
        this._stopAutoPlay();
        this._startAutoPlay();
    }
}
