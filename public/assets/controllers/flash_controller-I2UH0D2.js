/**
 * Contrôleur Stimulus : Messages flash
 *
 * Gère la disparition automatique des messages flash (succès, erreur, info)
 * après un délai configurable. L'utilisateur peut aussi fermer manuellement
 * via l'action dismiss.
 *
 * Value : delay (durée en ms avant disparition, défaut 5000)
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { delay: { type: Number, default: 5000 } };

    connect() {
        // Programmer la disparition automatique
        this.timeout = setTimeout(() => this.dismiss(), this.delayValue);
    }

    disconnect() {
        clearTimeout(this.timeout);
    }

    /** Déclenche l'animation de sortie puis supprime l'élément du DOM */
    dismiss() {
        clearTimeout(this.timeout);
        this.element.classList.add('flash-hiding');
        this.element.addEventListener('animationend', () => {
            this.element.remove();
        }, { once: true });
    }
}
