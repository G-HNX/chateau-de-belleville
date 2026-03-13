import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['button'];

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

        const text = document.createTextNode(message);
        flash.appendChild(text);

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

        setTimeout(() => {
            if (flash.parentNode) {
                flash.classList.add('flash-hiding');
                flash.addEventListener('animationend', () => flash.remove(), { once: true });
            }
        }, 5000);
    }
}
