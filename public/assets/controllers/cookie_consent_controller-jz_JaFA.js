/**
 * Contrôleur Stimulus : Consentement cookies + vérification d'âge (18 ans)
 *
 * Affiche une bannière modale au premier accès. Le choix de l'utilisateur
 * est persisté dans localStorage sous la clé 'belleville_consent'.
 * Trois options : accepter tout, accepter le nécessaire, ou quitter le site.
 *
 * Target : banner (la bannière modale)
 */

import { Controller } from '@hotwired/stimulus'

const STORAGE_KEY = 'belleville_consent'

export default class extends Controller {
    static targets = ['banner']

    connect () {
        // Afficher la bannière uniquement si aucun consentement n'a été donné
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

    /** Ferme la bannière et réactive le scroll */
    _close () {
        this.bannerTarget.setAttribute('hidden', '')
        document.body.style.overflow = ''
    }
}
