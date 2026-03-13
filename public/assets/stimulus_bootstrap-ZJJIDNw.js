/**
 * Bootstrap Stimulus — démarre l'application Stimulus via le bundle Symfony.
 *
 * Les contrôleurs situés dans assets/controllers/ sont auto-découverts.
 * Les contrôleurs tiers peuvent être enregistrés manuellement ci-dessous.
 */

import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// Enregistrer ici d'éventuels contrôleurs tiers
// app.register('nom_du_controleur', ControleurImporte);
