/**
 * Contrôleur Stimulus : Adresse de livraison identique
 *
 * Sur la page de checkout, permet de masquer et désactiver le bloc
 * d'adresse de livraison quand la case "Même adresse que la facturation"
 * est cochée. Les champs désactivés ne sont pas envoyés au serveur.
 *
 * Targets : checkbox (case à cocher), shippingBlock (bloc adresse livraison)
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['checkbox', 'shippingBlock'];

    connect() {
        // Appliquer l'état initial au chargement
        this.toggle();
    }

    /** Affiche ou masque le bloc livraison selon l'état de la checkbox */
    toggle() {
        const sameAsBilling = this.checkboxTarget.checked;
        this.shippingBlockTarget.style.display = sameAsBilling ? 'none' : '';
        // Désactiver tous les champs pour qu'ils ne soient pas soumis
        this.shippingBlockTarget.querySelectorAll('input, select, textarea').forEach(input => {
            input.disabled = sameAsBilling;
        });
    }
}