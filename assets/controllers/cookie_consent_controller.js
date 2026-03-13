import { Controller } from '@hotwired/stimulus'

const STORAGE_KEY = 'belleville_consent'

export default class extends Controller {
    static targets = ['banner']

    connect () {
        if (!localStorage.getItem(STORAGE_KEY)) {
            this.bannerTarget.removeAttribute('hidden')
            document.body.style.overflow = 'hidden'
        }
    }

    // Accepte tous les cookies (techniques + fonctionnels)
    acceptAll () {
        localStorage.setItem(STORAGE_KEY, 'full')
        this._close()
    }

    // Accepte uniquement les cookies strictement nécessaires
    acceptNecessary () {
        localStorage.setItem(STORAGE_KEY, 'necessary')
        this._close()
    }

    // Quitte le site (mineur ou refus total)
    decline () {
        window.location.href = 'https://www.google.fr'
    }

    _close () {
        this.bannerTarget.setAttribute('hidden', '')
        document.body.style.overflow = ''
    }
}
