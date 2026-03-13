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
        this.menuTarget.removeAttribute('aria-hidden');
        this.overlayTarget.style.display = 'block';
        this.overlayTarget.removeAttribute('aria-hidden');
        requestAnimationFrame(() => this.overlayTarget.classList.add('active'));
        this.hamburgerTarget.classList.add('active');
        this.hamburgerTarget.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.menuTarget.classList.remove('open');
        this.menuTarget.setAttribute('aria-hidden', 'true');
        this.overlayTarget.classList.remove('active');
        this.hamburgerTarget.classList.remove('active');
        this.hamburgerTarget.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        setTimeout(() => {
            this.overlayTarget.style.display = 'none';
            this.overlayTarget.setAttribute('aria-hidden', 'true');
        }, 300);
    }
}
