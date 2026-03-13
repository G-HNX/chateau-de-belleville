/**
 * Contrôleur Stimulus : Ajout au panier (AJAX)
 *
 * Soumet le formulaire d'ajout au panier en fetch asynchrone,
 * met à jour le badge du panier dans la navigation et affiche
 * un message flash de confirmation ou d'erreur.
 *
 * Target : button (bouton de soumission)
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button'];

    /** Envoie le formulaire d'ajout au panier via fetch (POST, JSON) */
    async add(event) {
        event.preventDefault();

        const form = this.element;
        const button = this.buttonTarget;
        const originalText = button.textContent;

        button.disabled = true;
        button.textContent = '...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            });

            const data = await response.json();

            if (data.success) {
                this.updateCartBadge(data.cartCount);
                this.showFlash('success', data.message);
                button.textContent = 'Ajouté !';
                // Restaurer le texte original après 2 secondes
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                }, 2000);
            } else {
                this.showFlash('error', data.message);
                button.textContent = originalText;
                button.disabled = false;
            }
        } catch {
            this.showFlash('error', 'Une erreur est survenue.');
            button.textContent = originalText;
            button.disabled = false;
        }
    }

    /** Met à jour le compteur du panier dans la barre de navigation */
    updateCartBadge(count) {
        const badge = document.getElementById('cart-badge');
        if (!badge) return;

        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    /** Crée et affiche un message flash temporaire (succès ou erreur) */
    showFlash(type, message) {
        // Crée le conteneur flash s'il n'existe pas encore dans le DOM
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

        const text = document.createTextNode(message);
        flash.appendChild(text);

        // Bouton de fermeture manuelle
        const dismissBtn = document.createElement('button');
        dismissBtn.className = 'flash-dismiss';
        dismissBtn.setAttribute('aria-label', 'Fermer');
        dismissBtn.textContent = '\u2715';
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
