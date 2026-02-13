import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['paymentElement', 'errors', 'submitButton', 'buttonText', 'spinner'];
    static values = {
        publicKey: String,
        clientSecret: String,
        returnUrl: String,
    };

    async connect() {
        if (!window.Stripe) {
            // Load Stripe.js dynamically
            await this.loadStripeJs();
        }

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

        this.paymentElement = this.elements.create('payment');
        this.paymentElement.mount(this.paymentElementTarget);
    }

    loadStripeJs() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://js.stripe.com/v3/';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    async submit() {
        this.setLoading(true);

        const { error } = await this.stripe.confirmPayment({
            elements: this.elements,
            confirmParams: {
                return_url: this.returnUrlValue,
            },
        });

        if (error) {
            this.showError(error.message);
            this.setLoading(false);
        }
    }

    showError(message) {
        this.errorsTarget.textContent = message;
        this.errorsTarget.style.display = 'block';
    }

    setLoading(isLoading) {
        this.submitButtonTarget.disabled = isLoading;
        this.buttonTextTarget.style.display = isLoading ? 'none' : 'inline';
        this.spinnerTarget.style.display = isLoading ? 'inline' : 'none';
    }
}
