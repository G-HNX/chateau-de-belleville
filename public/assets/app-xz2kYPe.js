/**
 * Point d'entrée principal de l'application Château de Belleville.
 *
 * Charge les styles Tailwind et initialise les contrôleurs Stimulus,
 * puis active les animations de scroll reveal et la barre de navigation compacte.
 */

import './styles/app.css';
import './stimulus_bootstrap.js';

// ============================================================
// Scroll Reveal — anime les éléments portant [data-reveal]
// lorsqu'ils entrent dans le viewport.
//
// Valeurs supportées pour data-reveal :
//   "fade-up"    (défaut)  — monte depuis le bas
//   "fade-in"              — fondu simple
//   "slide-left"           — vient de gauche
//   "slide-right"          — vient de droite
//   "scale-in"             — léger zoom
//
// Délai optionnel via data-reveal-delay="200" (ms)
// ============================================================
function initScrollReveal() {
    if (!('IntersectionObserver' in window)) {
        document.querySelectorAll('[data-reveal]').forEach(el => el.classList.add('revealed'))
        return
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(({ target, isIntersecting }) => {
            if (!isIntersecting) return
            const delay = parseInt(target.dataset.revealDelay ?? 0)
            const reveal = () => target.classList.add('revealed')
            delay ? setTimeout(reveal, delay) : reveal()
            observer.unobserve(target)
        })
    }, { threshold: 0.12, rootMargin: '0px 0px -12% 0px' })

    document.querySelectorAll('[data-reveal]:not(.revealed)').forEach(el => observer.observe(el))
}

// ============================================================
// Nav Scroll — compacte la barre de navigation au défilement
// ============================================================
function initNavScroll() {
    const nav = document.querySelector('.site-nav')
    if (!nav) return

    let ticking = false
    const update = () => {
        nav.classList.toggle('scrolled', window.scrollY > 60)
        ticking = false
    }

    window.addEventListener('scroll', () => {
        if (!ticking) { requestAnimationFrame(update); ticking = true }
    }, { passive: true })

    update()
}

// ============================================================
// Init
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    // Double rAF : laisse le navigateur peindre opacity:0 avant de lancer les transitions
    requestAnimationFrame(() => requestAnimationFrame(() => initScrollReveal()))
    initNavScroll()
})

// Ré-initialise le scroll reveal après chaque navigation Turbo
document.addEventListener('turbo:render', () => {
    requestAnimationFrame(() => requestAnimationFrame(() => initScrollReveal()))
})
