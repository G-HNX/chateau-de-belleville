/**
 * Contrôleur Stimulus : Bouton favori (coeur)
 *
 * Permet d'ajouter ou retirer un vin des favoris de l'utilisateur
 * via un appel fetch POST. Gère la redirection vers la connexion si
 * l'utilisateur n'est pas authentifié (401), et peut retirer la carte
 * du vin de la page favoris avec une animation de fondu.
 *
 * Values : wineId, active, token (CSRF), removeCard
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        wineId: Number,
        active: Boolean,
        token: String,
        removeCard: { type: Boolean, default: false },
    };

    /** Bascule l'état favori via un appel fetch POST */
    async toggle() {
        const formData = new FormData();
        formData.append('_token', this.tokenValue);

        try {
            const response = await fetch(`/vins/${this.wineIdValue}/favori`, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData,
            });

            // Rediriger vers la page de connexion si non authentifié
            if (response.status === 401) {
                window.location.href = '/connexion';
                return;
            }

            const data = await response.json();

            if (data.success) {
                this.activeValue = data.isFavorite;
                this.updateAppearance();
                this.showFlash('success', data.message);

                // Sur la page favoris, retirer la carte avec un fondu si le vin est retiré
                if (this.removeCardValue && !data.isFavorite) {
                    const card = this.element.closest('[data-favorite-card]');
                    if (card) {
                        card.style.transition = 'opacity 0.3s';
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 300);
                    }
                }
            } else {
                this.showFlash('error', data.message);
            }
        } catch {
            this.showFlash('error', 'Une erreur est survenue.');
        }
    }

    /** Met à jour l'apparence du bouton (coeur plein/vide) et le title */
    updateAppearance() {
        const heartFull = this.element.querySelector('[data-heart-full]');
        const heartEmpty = this.element.querySelector('[data-heart-empty]');

        if (heartFull) heartFull.style.display = this.activeValue ? 'block' : 'none';
        if (heartEmpty) heartEmpty.style.display = this.activeValue ? 'none' : 'block';

        this.element.title = this.activeValue ? 'Retirer de mes vins' : 'Ajouter à mes vins';
    }

    /** Crée et affiche un message flash temporaire */
    showFlash(type, message) {
        let container = document.querySelector('.flash-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'flash-container';
            container.setAttribute('role', 'status');
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }

        const flash = document.createElement('div');
        flash.className = `flash-message flash-${type}`;
        flash.appendChild(document.createTextNode(message));

        const dismissBtn = document.createElement('button');
        dismissBtn.className = 'flash-dismiss';
        dismissBtn.setAttribute('aria-label', 'Fermer');
        dismissBtn.textContent = '✕';
        flash.appendChild(dismissBtn);

        dismissBtn.addEventListener('click', () => {
            flash.classList.add('flash-hiding');
            flash.addEventListener('animationend', () => flash.remove(), { once: true });
        });

        container.appendChild(flash);

        // Disparition automatique après 5 secondes
        setTimeout(() => {
            if (flash.parentNode) {
                flash.classList.add('flash-hiding');
                flash.addEventListener('animationend', () => flash.remove(), { once: true });
            }
        }, 5000);
    }
}
