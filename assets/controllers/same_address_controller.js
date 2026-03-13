import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['checkbox', 'shippingBlock'];

    connect() {
        this.toggle();
    }

    toggle() {
        const sameAsBilling = this.checkboxTarget.checked;
        this.shippingBlockTarget.style.display = sameAsBilling ? 'none' : '';
        this.shippingBlockTarget.querySelectorAll('input, select, textarea').forEach(input => {
            input.disabled = sameAsBilling;
        });
    }
}