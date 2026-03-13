/**
 * Contrôleur Stimulus : Champ mot de passe amélioré
 *
 * Ajoute dynamiquement un bouton oeil pour afficher/masquer le mot de passe
 * et, optionnellement, une barre de force du mot de passe avec indicateurs
 * de critères (longueur, minuscule, majuscule, chiffre, caractère spécial).
 *
 * Value : strength (active la barre de force si true)
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        strength: { type: Boolean, default: false },
    };

    connect() {
        this.input = this.element.querySelector('input[type="password"]');
        if (!this.input) return;

        this._wrapInput();
        this._addToggle();

        if (this.strengthValue) {
            this._addStrengthBar();
            this.input.addEventListener('input', () => this._updateStrength());
        }
    }

    /** Nettoie le DOM ajouté dynamiquement lors de la déconnexion */
    disconnect() {
        if (this._inputWrapper && this._inputWrapper.parentNode) {
            this._inputWrapper.parentNode.insertBefore(this.input, this._inputWrapper);
            this._inputWrapper.remove();
        }
        if (this._strengthContainer) {
            this._strengthContainer.remove();
        }
    }

    // --- Méthodes internes ---

    /** Enveloppe l'input dans un conteneur relatif pour positionner le bouton oeil */
    _wrapInput() {
        this._inputWrapper = document.createElement('div');
        this._inputWrapper.style.cssText = 'position: relative; display: block;';
        this.input.parentNode.insertBefore(this._inputWrapper, this.input);
        this._inputWrapper.appendChild(this.input);
    }

    /** Crée le bouton oeil (afficher/masquer) et l'insère dans le wrapper */
    _addToggle() {
        this._toggle = document.createElement('button');
        this._toggle.type = 'button';
        this._toggle.setAttribute('aria-label', 'Afficher le mot de passe');
        this._toggle.style.cssText = [
            'position: absolute',
            'right: 0.75rem',
            'top: 50%',
            'transform: translateY(-50%)',
            'background: none',
            'border: none',
            'cursor: pointer',
            'color: var(--color-accent)',
            'padding: 0.2rem',
            'display: flex',
            'align-items: center',
            'line-height: 1',
        ].join(';');
        this._toggle.innerHTML = this._eyeIcon();
        this._toggle.addEventListener('click', () => this._handleToggle());

        // Padding droit pour éviter que le texte passe sous l'icône
        this.input.style.paddingRight = '2.75rem';

        this._inputWrapper.appendChild(this._toggle);
    }

    /** Bascule la visibilité du mot de passe et met à jour l'icône */
    _handleToggle() {
        const isHidden = this.input.type === 'password';
        this.input.type = isHidden ? 'text' : 'password';
        this._toggle.innerHTML = isHidden ? this._eyeOffIcon() : this._eyeIcon();
        this._toggle.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    }

    /** Construit la barre de force et la liste des critères sous le champ */
    _addStrengthBar() {
        this._strengthContainer = document.createElement('div');
        this._strengthContainer.style.cssText = 'margin-top: 0.5rem;';

        // Piste de la barre de progression
        const track = document.createElement('div');
        track.style.cssText = 'height: 4px; background: var(--color-beige); border-radius: 2px; overflow: hidden;';

        // Remplissage animé de la barre
        this._fill = document.createElement('div');
        this._fill.style.cssText = 'height: 100%; width: 0%; transition: width 0.3s ease, background-color 0.3s ease; border-radius: 2px;';
        track.appendChild(this._fill);

        this._strengthLabel = document.createElement('div');
        this._strengthLabel.style.cssText = 'font-size: 0.75rem; margin-top: 0.35rem; min-height: 1.1em; font-weight: 500; transition: color 0.3s;';

        this._criteriaList = document.createElement('ul');
        this._criteriaList.style.cssText = 'list-style: none; padding: 0; margin: 0.5rem 0 0; display: flex; flex-direction: column; gap: 0.2rem;';

        // Définition des 5 critères de robustesse
        const criteria = [
            { key: 'length',  label: '12 caractères minimum',          test: v => v.length >= 12 },
            { key: 'lower',   label: 'Une lettre minuscule',            test: v => /[a-z]/.test(v) },
            { key: 'upper',   label: 'Une lettre majuscule',            test: v => /[A-Z]/.test(v) },
            { key: 'digit',   label: 'Un chiffre',                      test: v => /\d/.test(v) },
            { key: 'special', label: 'Un caractère spécial (!@#...)',   test: v => /[\W_]/.test(v) },
        ];
        this._criteria = criteria;

        criteria.forEach(c => {
            const li = document.createElement('li');
            li.style.cssText = 'display: flex; align-items: center; gap: 0.4rem; font-size: 0.75rem; color: var(--color-secondary); transition: color 0.2s;';
            li.dataset.key = c.key;
            li.innerHTML = `<span class="criterion-icon" style="width:14px;height:14px;flex-shrink:0;">${this._crossIcon()}</span>${c.label}`;
            this._criteriaList.appendChild(li);
        });

        this._strengthContainer.appendChild(track);
        this._strengthContainer.appendChild(this._strengthLabel);
        this._strengthContainer.appendChild(this._criteriaList);

        // Insérer après le wrapper input
        this._inputWrapper.parentNode.insertBefore(this._strengthContainer, this._inputWrapper.nextSibling);
    }

    /** Recalcule la force du mot de passe et met à jour la barre + les critères */
    _updateStrength() {
        const val = this.input.value;
        let passed = 0;

        // Vérifier chaque critère et mettre à jour son icône
        this._criteria.forEach(c => {
            const ok = c.test(val);
            if (ok) passed++;
            const li = this._criteriaList.querySelector(`[data-key="${c.key}"]`);
            if (li) {
                li.querySelector('.criterion-icon').innerHTML = ok ? this._checkIcon() : this._crossIcon();
                li.style.color = ok ? '#2E7D32' : 'var(--color-secondary)';
            }
        });

        // Calcul du pourcentage de critères remplis
        const pct = val.length === 0 ? 0 : Math.round((passed / this._criteria.length) * 100);

        // Niveaux de force avec couleur et label associés
        const levels = [
            { min: 0,  max: 20,  color: '#ef4444', label: 'Très faible' },
            { min: 21, max: 40,  color: '#f97316', label: 'Faible' },
            { min: 41, max: 60,  color: '#f59e0b', label: 'Moyen' },
            { min: 61, max: 80,  color: '#84cc16', label: 'Fort' },
            { min: 81, max: 100, color: '#16a34a', label: 'Très fort' },
        ];

        const level = levels.find(l => pct >= l.min && pct <= l.max) || levels[0];

        this._fill.style.width = `${pct}%`;
        this._fill.style.backgroundColor = pct === 0 ? 'transparent' : level.color;
        this._strengthLabel.textContent = val.length ? level.label : '';
        this._strengthLabel.style.color = level.color;
    }

    // --- Icônes SVG ---

    _eyeIcon() {
        return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
    }

    _eyeOffIcon() {
        return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;
    }

    _checkIcon() {
        return `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2E7D32" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`;
    }

    _crossIcon() {
        return `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>`;
    }
}
