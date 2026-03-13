import { Controller } from "@hotwired/stimulus"

/**
 * Counter controller — anime un nombre de 0 à sa valeur cible lorsqu'il
 * entre dans le viewport.
 *
 * Usage :
 *   <span data-controller="counter"
 *         data-counter-target-value="1970"
 *         data-counter-suffix-value=" ans">
 *   </span>
 */
export default class extends Controller {
    static values = {
        target:   { type: Number, default: 0 },
        duration: { type: Number, default: 1600 },
        prefix:   { type: String, default: '' },
        suffix:   { type: String, default: '' },
        separator:{ type: String, default: '' }, // séparateur milliers, ex: ' '
    }

    connect() {
        if (!('IntersectionObserver' in window)) {
            this._render(this.targetValue)
            return
        }

        const observer = new IntersectionObserver(([entry]) => {
            if (!entry.isIntersecting) return
            this._animate()
            observer.disconnect()
        }, { threshold: 0.5 })

        observer.observe(this.element)
    }

    _animate() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this._render(this.targetValue)
            return
        }

        const start    = performance.now()
        const duration = this.durationValue
        const target   = this.targetValue

        const step = (now) => {
            const elapsed  = now - start
            const progress = Math.min(elapsed / duration, 1)
            // Ease-out cubic pour un décompte qui ralentit sur la fin
            const eased   = 1 - Math.pow(1 - progress, 3)
            this._render(Math.round(eased * target))
            if (progress < 1) requestAnimationFrame(step)
        }

        requestAnimationFrame(step)
    }

    _render(value) {
        let formatted = value.toString()
        if (this.separatorValue) {
            formatted = value.toLocaleString('fr-FR').replace(/\s/g, this.separatorValue)
        }
        this.element.textContent = this.prefixValue + formatted + this.suffixValue
    }
}
