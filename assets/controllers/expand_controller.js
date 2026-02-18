import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['content', 'chevron'];
    static values = { open: { type: Boolean, default: false } };

    toggle() {
        this.openValue = !this.openValue;
    }

    openValueChanged() {
        const content = this.contentTarget;

        if (this.openValue) {
            // Ouvrir : mesurer la hauteur réelle pour animer
            content.style.maxHeight = content.scrollHeight + 'px';
            content.style.opacity = '1';
            if (this.hasChevronTarget) {
                this.chevronTarget.style.transform = 'rotate(180deg)';
            }
            this.element.style.cursor = 'default';
        } else {
            content.style.maxHeight = '0';
            content.style.opacity = '0';
            if (this.hasChevronTarget) {
                this.chevronTarget.style.transform = 'rotate(0deg)';
            }
            this.element.style.cursor = 'pointer';
        }
    }
}
