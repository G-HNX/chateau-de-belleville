/**
 * Contrôleur Stimulus : Calendrier de dégustations
 *
 * Affiche un calendrier mensuel interactif avec les créneaux disponibles
 * pour les dégustations. L'utilisateur sélectionne une date pour voir
 * les horaires et places restantes, avec lien de réservation.
 *
 * Targets : calendar (grille), slots (liste créneaux), monthLabel, prevBtn
 * Values  : slots (tableau de créneaux JSON), tastingSlug
 */

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['calendar', 'slots', 'monthLabel', 'prevBtn'];
    static values = {
        slots: Array,
        tastingSlug: String,
    };

    connect() {
        this.today = new Date();
        this.today.setHours(0, 0, 0, 0);
        this.currentMonth = new Date(this.today.getFullYear(), this.today.getMonth(), 1);
        this.selectedDate = null;

        // Indexer les créneaux par date pour un accès rapide
        this.slotsByDate = {};
        this.slotsValue.forEach(slot => {
            if (!this.slotsByDate[slot.date]) {
                this.slotsByDate[slot.date] = [];
            }
            this.slotsByDate[slot.date].push(slot);
        });

        this.render();
    }

    /** Mois précédent */
    prev() {
        this.currentMonth.setMonth(this.currentMonth.getMonth() - 1);
        this.render();
    }

    /** Mois suivant */
    next() {
        this.currentMonth.setMonth(this.currentMonth.getMonth() + 1);
        this.render();
    }

    /** Sélectionne une date cliquée dans le calendrier */
    selectDay(event) {
        const dateStr = event.currentTarget.dataset.date;
        if (!dateStr) return;

        this.selectedDate = dateStr;
        this.render();
    }

    /** Re-rend le calendrier, les créneaux et la navigation */
    render() {
        this.renderCalendar();
        this.renderSlots();
        this.updateNavigation();
    }

    /** Désactive le bouton "mois précédent" si on est au mois courant ou avant */
    updateNavigation() {
        const thisMonth = new Date(this.today.getFullYear(), this.today.getMonth(), 1);
        const isPastOrCurrent = this.currentMonth <= thisMonth;
        this.prevBtnTarget.disabled = isPastOrCurrent;
        this.prevBtnTarget.style.opacity = isPastOrCurrent ? '0.3' : '1';
        this.prevBtnTarget.style.cursor = isPastOrCurrent ? 'default' : 'pointer';

        // Afficher le nom du mois en français avec majuscule initiale
        const monthName = this.currentMonth.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
        this.monthLabelTarget.textContent = monthName.charAt(0).toUpperCase() + monthName.slice(1);
    }

    /** Génère le HTML de la grille calendrier (jours de la semaine + cases) */
    renderCalendar() {
        const year = this.currentMonth.getFullYear();
        const month = this.currentMonth.getMonth();

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);

        // Calcul du jour de début (Lundi = 0, Dimanche = 6)
        let startDow = firstDay.getDay() - 1;
        if (startDow < 0) startDow = 6;

        const days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        let html = '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;">';

        // En-tête des jours de la semaine
        days.forEach(d => {
            html += `<div style="padding:0.5rem 0;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:#8B7355;font-weight:500;">${d}</div>`;
        });

        // Cases vides avant le premier jour du mois
        for (let i = 0; i < startDow; i++) {
            html += '<div></div>';
        }

        // Cases des jours du mois
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dateObj = new Date(year, month, day);
            const isPast = dateObj < this.today;
            const hasSlots = !!this.slotsByDate[dateStr];
            const isSelected = this.selectedDate === dateStr;
            const isToday = dateObj.getTime() === this.today.getTime();

            // Styles conditionnels selon l'état du jour
            let bgColor = 'transparent';
            let textColor = '#2C2420';
            let cursor = 'default';
            let fontWeight = '400';
            let border = 'none';

            if (isSelected) {
                bgColor = '#5B4638';
                textColor = '#fff';
                fontWeight = '600';
            } else if (isToday) {
                border = '2px solid #C9A87C';
            }

            if (isPast) {
                textColor = '#D0C8BF';
                cursor = 'default';
            } else if (hasSlots) {
                cursor = 'pointer';
                if (!isSelected) fontWeight = '500';
            }

            // Rendre cliquable uniquement les jours futurs avec des créneaux
            const clickable = !isPast && hasSlots;
            const actionAttr = clickable ? 'data-action="click->tasting-calendar#selectDay"' : '';
            const dateAttr = clickable ? `data-date="${dateStr}"` : '';

            html += `<div ${actionAttr} ${dateAttr} style="padding:0.6rem 0.25rem;border-radius:8px;background:${bgColor};color:${textColor};cursor:${cursor};font-weight:${fontWeight};border:${border};position:relative;transition:background 0.15s;">`;
            html += `${day}`;
            // Point doré sous les jours avec créneaux disponibles
            if (hasSlots && !isPast) {
                const dotColor = isSelected ? '#C9A87C' : '#C9A87C';
                html += `<span style="display:block;width:6px;height:6px;background:${dotColor};border-radius:50%;margin:3px auto 0;"></span>`;
            }
            html += '</div>';
        }

        html += '</div>';
        this.calendarTarget.innerHTML = html;
    }

    /** Affiche les créneaux horaires disponibles pour la date sélectionnée */
    renderSlots() {
        if (!this.selectedDate || !this.slotsByDate[this.selectedDate]) {
            if (!this.selectedDate) {
                this.slotsTarget.innerHTML = '<p style="text-align:center;color:#8B7355;padding:1.5rem 0;">Sélectionnez une date dans le calendrier.</p>';
            } else {
                this.slotsTarget.innerHTML = '<p style="text-align:center;color:#8B7355;padding:1.5rem 0;">Aucun créneau pour cette date.</p>';
            }
            return;
        }

        // Formater la date sélectionnée en français (ex: "Samedi 15 mars 2026")
        const dateObj = new Date(this.selectedDate + 'T00:00:00');
        const dateLabel = dateObj.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        const dateLabelCap = dateLabel.charAt(0).toUpperCase() + dateLabel.slice(1);

        const slots = this.slotsByDate[this.selectedDate];
        let html = `<h4 style="font-family:'Cormorant Garamond',serif;font-size:1.3rem;color:#5B4638;margin-bottom:1rem;">${dateLabelCap}</h4>`;
        html += '<div style="display:flex;flex-direction:column;gap:0.75rem;">';

        slots.forEach(slot => {
            const spots = slot.remainingSpots;
            const spotsLabel = spots > 1 ? `${spots} places restantes` : `${spots} place restante`;
            // Couleur dorée si peu de places restantes (urgence)
            const spotsColor = spots <= 2 ? '#C9A87C' : '#8B7355';

            html += `<div style="background:white;padding:1.25rem 1.5rem;border-radius:8px;box-shadow:0 2px 10px rgba(91,70,56,0.08);display:flex;justify-content:space-between;align-items:center;">`;
            html += `<div>`;
            html += `<div style="font-weight:500;color:#5B4638;font-size:1.05rem;">${slot.startTime} - ${slot.endTime}</div>`;
            html += `<div style="font-size:0.85rem;color:${spotsColor};margin-top:0.2rem;">${spotsLabel}</div>`;
            html += `</div>`;
            html += `<a href="/degustations/${this.tastingSlugValue}/reserver/${slot.id}" class="btn btn-primary btn-sm" style="white-space:nowrap;">Réserver</a>`;
            html += `</div>`;
        });

        html += '</div>';
        this.slotsTarget.innerHTML = html;
    }
}
