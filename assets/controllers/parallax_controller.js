import { Controller } from "@hotwired/stimulus"

/**
 * Parallax controller — déplace le fond de l'élément en fonction du scroll.
 *
 * Usage :
 *   <section class="hero"
 *            data-controller="parallax"
 *            data-parallax-speed-value="0.35">
 *
 * La valeur speed correspond à la fraction du scroll appliquée au décalage.
 * 0 = pas d'effet, 0.5 = fort effet.
 */
export default class extends Controller {
    static values = {
        speed: { type: Number, default: 0.35 },
    }

    connect() {
        // Respecter l'accessibilité (animations réduites)
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return

        this._raf = null
        this._bound = this._onScroll.bind(this)
        window.addEventListener('scroll', this._bound, { passive: true })
        this._onScroll()
    }

    disconnect() {
        window.removeEventListener('scroll', this._bound)
        if (this._raf) cancelAnimationFrame(this._raf)
    }

    _onScroll() {
        if (this._raf) return
        this._raf = requestAnimationFrame(() => {
            this._raf = null
            const rect = this.element.getBoundingClientRect()
            // N'animer que si l'élément est visible
            if (rect.bottom < 0 || rect.top > window.innerHeight) return
            const offset = (window.scrollY - this.element.offsetTop) * this.speedValue
            this.element.style.backgroundPositionY = `calc(50% + ${offset}px)`
        })
    }
}
