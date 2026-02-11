import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['star', 'input'];

    connect() {
        this.render(this.currentValue);
    }

    get currentValue() {
        const checked = this.element.querySelector('input[type="radio"]:checked');
        return checked ? parseInt(checked.value, 10) : 0;
    }

    select(event) {
        const value = parseInt(event.currentTarget.dataset.value, 10);
        const radio = this.element.querySelector(`input[type="radio"][value="${value}"]`);
        if (radio) {
            radio.checked = true;
        }
        this.render(value);
    }

    hover(event) {
        const value = parseInt(event.currentTarget.dataset.value, 10);
        this.render(value);
    }

    leave() {
        this.render(this.currentValue);
    }

    render(activeValue) {
        this.starTargets.forEach((star) => {
            const value = parseInt(star.dataset.value, 10);
            if (value <= activeValue) {
                star.classList.add('star-active');
            } else {
                star.classList.remove('star-active');
            }
        });
    }
}
