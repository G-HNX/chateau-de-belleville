/**
 * Contrôleur Stimulus : Notation par étoiles
 *
 * Composant interactif de notation (1-5 étoiles) utilisé pour les avis
 * sur les vins. Gère le survol (prévisualisation) et la sélection
 * (coche le bouton radio correspondant).
 *
 * Targets : star (éléments étoile cliquables), input (radios cachés)
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['star', 'input'];

    connect() {
        this.render(this.currentValue);
    }

    /** Récupère la valeur actuellement sélectionnée via le radio coché */
    get currentValue() {
        const checked = this.element.querySelector('input[type="radio"]:checked');
        return checked ? parseInt(checked.value, 10) : 0;
    }

    /** Sélectionne une note : coche le radio et met à jour l'affichage */
    select(event) {
        const value = parseInt(event.currentTarget.dataset.value, 10);
        const radio = this.element.querySelector(`input[type="radio"][value="${value}"]`);
        if (radio) {
            radio.checked = true;
        }
        this.render(value);
    }

    /** Au survol : prévisualise la note sans la valider */
    hover(event) {
        const value = parseInt(event.currentTarget.dataset.value, 10);
        this.render(value);
    }

    /** Quand la souris quitte la zone : revient à la note sélectionnée */
    leave() {
        this.render(this.currentValue);
    }

    /** Met à jour les classes CSS des étoiles selon la valeur active */
    render(activeValue) {
        this.starTargets.forEach((star) => {
            const value = parseInt(star.dataset.value, 10);
            if (value <= activeValue) {
                star.classList.add('star-active');
            } else {
                star.classList.remove('star-active');
            }
        });
    }
}
