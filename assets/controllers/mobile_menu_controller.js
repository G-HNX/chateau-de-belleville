import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu', 'overlay', 'hamburger'];

    toggle() {
        const isOpen = this.menuTarget.classList.contains('open');
        if (isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.menuTarget.classList.add('open');
        this.overlayTarget.style.display = 'block';
        requestAnimationFrame(() => this.overlayTarget.classList.add('active'));
        this.hamburgerTarget.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.menuTarget.classList.remove('open');
        this.overlayTarget.classList.remove('active');
        this.hamburgerTarget.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            this.overlayTarget.style.display = 'none';
        }, 300);
    }
}
