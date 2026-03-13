/**
 * Contrôleur Stimulus : Accordéon / Expand
 *
 * Permet d'ouvrir et fermer une section de contenu avec animation
 * de hauteur (maxHeight) et rotation du chevron indicateur.
 * Utilisé pour les FAQ, les détails de commande, etc.
 *
 * Targets : content (zone dépliable), chevron (icône de flèche)
 * Value   : open (booléen, état ouvert/fermé)
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['content', 'chevron'];
    static values = { open: { type: Boolean, default: false } };

    /** Bascule l'état ouvert/fermé */
    toggle() {
        this.openValue = !this.openValue;
    }

    /** Callback Stimulus : anime l'ouverture ou la fermeture du contenu */
    openValueChanged() {
        const content = this.contentTarget;

        if (this.openValue) {
            // Ouvrir : mesurer la hauteur réelle pour animer
            content.style.maxHeight = content.scrollHeight + 'px';
            content.style.opacity = '1';
            if (this.hasChevronTarget) {
                this.chevronTarget.style.transform = 'rotate(180deg)';
            }
            this.element.style.cursor = 'default';
        } else {
            content.style.maxHeight = '0';
            content.style.opacity = '0';
            if (this.hasChevronTarget) {
                this.chevronTarget.style.transform = 'rotate(0deg)';
            }
            this.element.style.cursor = 'pointer';
        }
    }
}
