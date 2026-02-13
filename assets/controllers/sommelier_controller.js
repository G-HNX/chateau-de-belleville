import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['panel', 'toggle', 'messages', 'input', 'suggestions'];

    connect() {
        this.conversationHistory = [];
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
                    history: this.conversationHistory.slice(0, -1), // exclude the message we just sent
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
