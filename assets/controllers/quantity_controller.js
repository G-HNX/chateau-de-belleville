import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input'];

    decrement() {
        const input = this.inputTarget;
        input.stepDown();
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    increment() {
        const input = this.inputTarget;
        input.stepUp();
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
