import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['panel', 'toggle', 'messages', 'input', 'suggestions'];

    connect() {
        this.conversationHistory = [];
        const wasOpen = sessionStorage.getItem('sommelier-open') === 'true';
        if (wasOpen) {
            this.open();
        }

        this._onAsk = (e) => {
            this.open();
            this.inputTarget.value = e.detail.message ?? '';
            this.inputTarget.focus();
        };
        document.addEventListener('sommelier:ask', this._onAsk);
        this.setContextualSuggestions();
    }

    disconnect() {
        document.removeEventListener('sommelier:ask', this._onAsk);
    }

    setContextualSuggestions() {
        if (!this.hasSuggestionsTarget) return;
        const path = window.location.pathname;
        let suggestions;
        if (path.startsWith('/boutique') || path.startsWith('/vins')) {
            suggestions = ['Quel blanc pour l\'apéritif ?', 'Recommandez-moi un rouge', 'Quel vin offrir ?'];
        } else if (path.startsWith('/panier')) {
            suggestions = ['Accord mets-vins pour ce soir ?', 'Quelle bouteille ajouter ?', 'Vins à moins de 15€ ?'];
        } else if (path.startsWith('/degustations')) {
            suggestions = ['Quelles dégustations proposez-vous ?', 'C\'est quoi la dégustation Prestige ?', 'Comment réserver ?'];
        } else {
            suggestions = ['Quel vin avec du poisson ?', 'Recommandez-moi un rouge', 'Parlez-moi de vos dégustations'];
        }
        const buttons = this.suggestionsTarget.querySelectorAll('.sommelier-suggestion');
        buttons.forEach((btn, i) => { if (suggestions[i]) btn.textContent = suggestions[i]; });
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
        this.conversationHistory.push({ role: 'user', content: text });
        input.value = '';
        this.hideSuggestions();
        this.showTyping();
        this.callApi(text);
    }

    suggest(event) {
        const text = event.currentTarget.textContent.trim();
        this.addMessage(text, 'user');
        this.conversationHistory.push({ role: 'user', content: text });
        this.hideSuggestions();
        this.showTyping();
        this.callApi(text);
    }

    async callApi(message) {
        try {
            const response = await fetch('/api/sommelier', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: message,
                    history: this.conversationHistory.slice(0, -1).slice(-10), // max 10 derniers messages
                }),
            });

            const data = await response.json();
            this.hideTyping();

            if (data.response) {
                this.addMessage(data.response, 'bot');
                this.conversationHistory.push({ role: 'assistant', content: data.response });
            } else if (data.error) {
                this.addMessage('Désolé, une erreur est survenue. Réessayez !', 'bot');
            }
        } catch (error) {
            this.hideTyping();
            this.addMessage('Notre sommelier est momentanément indisponible. Réessayez dans quelques instants !', 'bot');
        }
    }

    addMessage(text, type) {
        const msg = document.createElement('div');
        msg.classList.add('sommelier-msg', type);
        if (type === 'bot') {
            msg.innerHTML = this.renderMarkdown(text);
        } else {
            msg.textContent = text;
        }
        this.messagesTarget.appendChild(msg);
        this.scrollToBottom();
    }

    renderMarkdown(text) {
        return text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/\n/g, '<br>');
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
