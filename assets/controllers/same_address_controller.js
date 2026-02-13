import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['checkbox', 'shippingBlock'];

    connect() {
        this.toggle();
    }

    toggle() {
        this.shippingBlockTarget.style.display = this.checkboxTarget.checked ? 'none' : '';
    }
}