import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { delay: { type: Number, default: 5000 } };

    connect() {
        this.timeout = setTimeout(() => this.dismiss(), this.delayValue);
    }

    disconnect() {
        clearTimeout(this.timeout);
    }

    dismiss() {
        clearTimeout(this.timeout);
        this.element.classList.add('flash-hiding');
        this.element.addEventListener('animationend', () => {
            this.element.remove();
        }, { once: true });
    }
}
