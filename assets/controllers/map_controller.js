import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';
import 'leaflet/dist/leaflet.min.css';

// Correction du chemin des icônes Leaflet (problème connu avec les bundlers)
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

export default class extends Controller {
    static values = {
        lat: { type: Number, default: 47.004759 },
        lng: { type: Number, default: -0.218672 },
        zoom: { type: Number, default: 15 },
        label: { type: String, default: 'Château de Belleville' },
    };

    connect() {
        this.map = L.map(this.element, { scrollWheelZoom: false }).setView(
            [this.latValue, this.lngValue],
            this.zoomValue
        );

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(this.map);

        const icon = L.divIcon({
            html: `<div style="
                background: #5B4638;
                color: white;
                border-radius: 50% 50% 50% 0;
                width: 36px; height: 36px;
                display: flex; align-items: center; justify-content: center;
                font-size: 18px;
                transform: rotate(-45deg);
                box-shadow: 0 4px 12px rgba(91,70,56,0.4);
                border: 2px solid white;
            "><span style="transform: rotate(45deg); display:block;">🍷</span></div>`,
            className: '',
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -40],
        });

        L.marker([this.latValue, this.lngValue], { icon })
            .addTo(this.map)
            .bindPopup(`
                <div style="font-family: serif; text-align: center; padding: 0.25rem 0.5rem;">
                    <strong style="color: #5B4638; font-size: 1rem;">${this.labelValue}</strong><br>
                    <span style="color: #8B7355; font-size: 0.85rem;">36 rue de la Garde, 79100 Sainte-Verge</span>
                </div>
            `, { maxWidth: 220 })
            .openPopup();
    }

    disconnect() {
        if (this.map) {
            this.map.remove();
        }
    }
}