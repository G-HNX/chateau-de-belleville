/**
 * Contrôleur Stimulus : Menu mobile (hamburger)
 *
 * Gère l'ouverture et la fermeture du menu latéral sur mobile avec
 * overlay semi-transparent, gestion des attributs ARIA pour l'accessibilité,
 * et blocage du scroll en arrière-plan.
 *
 * Targets : menu (panneau latéral), overlay (fond sombre), hamburger (bouton)
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu', 'overlay', 'hamburger'];

    /** Bascule l'état ouvert/fermé du menu */
    toggle() {
        const isOpen = this.menuTarget.classList.contains('open');
        if (isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    /** Ouvre le menu, affiche l'overlay et bloque le scroll */
    open() {
        this.menuTarget.classList.add('open');
        this.menuTarget.removeAttribute('aria-hidden');
        this.overlayTarget.style.display = 'block';
        this.overlayTarget.removeAttribute('aria-hidden');
        // rAF pour permettre au navigateur de peindre avant d'ajouter la transition
        requestAnimationFrame(() => this.overlayTarget.classList.add('active'));
        this.hamburgerTarget.classList.add('active');
        this.hamburgerTarget.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    /** Ferme le menu avec animation et restaure le scroll */
    close() {
        this.menuTarget.classList.remove('open');
        this.menuTarget.setAttribute('aria-hidden', 'true');
        this.overlayTarget.classList.remove('active');
        this.hamburgerTarget.classList.remove('active');
        this.hamburgerTarget.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        // Attendre la fin de la transition CSS (300ms) avant de masquer l'overlay
        setTimeout(() => {
            this.overlayTarget.style.display = 'none';
            this.overlayTarget.setAttribute('aria-hidden', 'true');
        }, 300);
    }
}
