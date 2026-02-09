import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['panel', 'toggle', 'messages', 'input', 'suggestions'];

    connect() {
        const wasOpen = sessionStorage.getItem('sommelier-open') === 'true';
        if (wasOpen) {
            this.open();
        }
    }

    togglePanel() {
        if (this.panelTarget.classList.contains('open')) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.panelTarget.classList.add('open');
        this.toggleTarget.classList.add('hidden');
        sessionStorage.setItem('sommelier-open', 'true');
        this.scrollToBottom();
    }

    close() {
        this.panelTarget.classList.remove('open');
        this.toggleTarget.classList.remove('hidden');
        sessionStorage.setItem('sommelier-open', 'false');
    }

    send(event) {
        event.preventDefault();
        const input = this.inputTarget;
        const text = input.value.trim();
        if (!text) return;

        this.addMessage(text, 'user');
        input.value = '';
        this.hideSuggestions();
        this.showTyping();

        setTimeout(() => {
            this.hideTyping();
            this.addMessage(
                'Merci pour votre question ! Notre sommelier IA sera bient\u00f4t disponible pour vous conseiller personnellement. En attendant, n\'h\u00e9sitez pas \u00e0 explorer notre catalogue de vins.',
                'bot'
            );
        }, 1500);
    }

    suggest(event) {
        const text = event.currentTarget.textContent.trim();
        this.addMessage(text, 'user');
        this.hideSuggestions();
        this.showTyping();

        setTimeout(() => {
            this.hideTyping();
            const responses = {
                'Quel vin avec du poisson\u00a0?': 'Pour accompagner du poisson, je vous recommande notre Escapade, un Anjou Blanc frais et \u00e9l\u00e9gant (12,50\u00a0\u20ac). Ses notes d\'agrumes et de fleurs blanches s\'accordent parfaitement avec les fruits de mer et poissons grill\u00e9s.',
                'Recommandez-moi un rouge': 'Notre cuv\u00e9e L\'Invit\u00e9e est un Anjou Rouge d\'exception (14,50\u00a0\u20ac). Cabernet Franc \u00e9l\u00e9gant avec des ar\u00f4mes de fruits rouges et une belle structure. Id\u00e9al avec des viandes ou des fromages affin\u00e9s.',
                'Parlez-moi de vos d\u00e9gustations': 'Nous proposons 3 formules\u00a0: D\u00e9couverte (15\u00a0\u20ac, 1h, 3 vins), Prestige (25\u00a0\u20ac, 1h30, 5 vins + fromages) et Exception (55\u00a0\u20ac, 3h, 7 vins + d\u00e9jeuner). R\u00e9servez sur notre page D\u00e9gustations\u00a0!',
            };
            const response = responses[text] || 'Notre sommelier IA sera bient\u00f4t disponible pour r\u00e9pondre \u00e0 cette question. D\u00e9couvrez nos vins en attendant\u00a0!';
            this.addMessage(response, 'bot');
        }, 1200);
    }

    addMessage(text, type) {
        const msg = document.createElement('div');
        msg.classList.add('sommelier-msg', type);
        msg.textContent = text;
        this.messagesTarget.appendChild(msg);
        this.scrollToBottom();
    }

    showTyping() {
        const typing = document.createElement('div');
        typing.classList.add('sommelier-msg', 'typing');
        typing.dataset.typing = 'true';
        typing.innerHTML = '<div class="sommelier-typing-dots"><span></span><span></span><span></span></div>';
        this.messagesTarget.appendChild(typing);
        this.scrollToBottom();
    }

    hideTyping() {
        const typing = this.messagesTarget.querySelector('[data-typing]');
        if (typing) typing.remove();
    }

    hideSuggestions() {
        if (this.hasSuggestionsTarget) {
            this.suggestionsTarget.style.display = 'none';
        }
    }

    scrollToBottom() {
        requestAnimationFrame(() => {
            this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
        });
    }

    handleKeydown(event) {
        if (event.key === 'Enter') {
            this.send(event);
        }
    }
}
