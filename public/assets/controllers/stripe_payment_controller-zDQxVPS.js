/**
 * Contrôleur Stimulus : Paiement Stripe
 *
 * Intègre Stripe Elements (Payment Element) pour le paiement sécurisé
 * par carte bancaire. Charge Stripe.js dynamiquement si nécessaire,
 * monte le formulaire de paiement et gère la confirmation du paiement.
 *
 * Targets : paymentElement, errors, submitButton, buttonText, spinner
 * Values  : publicKey (clé publique Stripe), clientSecret (PI), returnUrl
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['paymentElement', 'errors', 'submitButton', 'buttonText', 'spinner'];
    static values = {
        publicKey: String,
        clientSecret: String,
        returnUrl: String,
    };

    async connect() {
        // Charger Stripe.js dynamiquement si pas encore présent
        if (!window.Stripe) {
            await this.loadStripeJs();
        }

        // Initialiser Stripe avec l'apparence personnalisée du domaine
        this.stripe = window.Stripe(this.publicKeyValue);
        this.elements = this.stripe.elements({
            clientSecret: this.clientSecretValue,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#5B4638',
                    colorBackground: '#ffffff',
                    colorText: '#2C2420',
                    colorDanger: '#e74c3c',
                    fontFamily: 'Montserrat, system-ui, sans-serif',
                    borderRadius: '8px',
                },
            },
        });

        // Monter le Payment Element dans le conteneur cible
        this.paymentElement = this.elements.create('payment');
        this.paymentElement.mount(this.paymentElementTarget);
    }

    /** Charge le script Stripe.js v3 depuis le CDN */
    loadStripeJs() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://js.stripe.com/v3/';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /** Confirme le paiement via Stripe et redirige vers la page de retour */
    async submit() {
        this.setLoading(true);

        const { error } = await this.stripe.confirmPayment({
            elements: this.elements,
            confirmParams: {
                return_url: this.returnUrlValue,
            },
        });

        // En cas de succès, Stripe redirige automatiquement ; on ne gère que les erreurs
        if (error) {
            this.showError(error.message);
            this.setLoading(false);
        }
    }

    /** Affiche un message d'erreur sous le formulaire de paiement */
    showError(message) {
        this.errorsTarget.textContent = message;
        this.errorsTarget.style.display = 'block';
    }

    /** Bascule l'état de chargement du bouton (spinner/texte) */
    setLoading(isLoading) {
        this.submitButtonTarget.disabled = isLoading;
        this.buttonTextTarget.style.display = isLoading ? 'none' : 'inline';
        this.spinnerTarget.style.display = isLoading ? 'inline' : 'none';
    }
}
