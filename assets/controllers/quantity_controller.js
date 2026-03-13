/**
 * Contrôleur Stimulus : Sélecteur de quantité (+/-)
 *
 * Fournit des boutons d'incrémentation et de décrémentation pour un
 * champ input numérique. Émet un événement 'change' après modification
 * pour déclencher la mise à jour du panier.
 *
 * Target : input (champ numérique)
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input'];

    /** Diminue la quantité d'un pas et notifie le formulaire */
    decrement() {
        const input = this.inputTarget;
        input.stepDown();
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /** Augmente la quantité d'un pas et notifie le formulaire */
    increment() {
        const input = this.inputTarget;
        input.stepUp();
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
