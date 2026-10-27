<?php

    /**
     * Représente une mission professionnelle issue du système SIFAC.
     *
     * Cette classe encapsule les données d'une mission :
     * - identification de l'agent
     * - période de déplacement
     * - motif et destination
     * - état de validation
     * - informations de paiement
     *
     * Elle fournit également des méthodes permettant de retourner
     * les libellés associés aux codes d'autorisation et de calcul.
     *
     */
    class SifacMissionClass
    {

        /**
         * Matricule SIFAC de l'agent.
         *
         * @var string
         */
        public string $matricule = '';

        /**
         * Identifiant unique de la mission.
         *
         * Correspond au numéro d'ordre de mission SIFAC (REINR), sans les zéros
         * de tête.
         *
         * @var string
         */
        public string $missionid = '';

        /**
         * Motif de la mission.
         *
         * @var string
         */
        public string $motif = '';

        /**
         * Destination de la mission.
         *
         * @var string
         */
        public string $destination = '';

        /**
         * Montant du paiement (format texte).
         *
         * Format français avec séparateur décimal ',' et suffixe ' €'
         * (ex : "123,45 €"). Chaîne vide si la mission n'est pas encore
         * indemnisée.
         *
         * @var string
         */
        public string $paiement = '';

        /**
         * Date de paiement de la mission (format YYYYMMDD ou formaté).
         *
         * Chaîne vide si la mission n'est pas encore indemnisée.
         *
         * @var string
         */
        public string $datepaiement = '';

        /**
         * Date de début de la mission.
         *
         * Format brut SIFAC (format YYYYMMDD)
         *
         * @var string
         */

        public string $datedebut = '';

        /**
         * Date de fin de la mission.
         *
         * Format brut SIFAC (format YYYYMMDD)
         *
         * @var string
         */
        public string $datefin = '';

        /**
         * Code d'autorisation de la mission (champ SIFAC ANTRG).
         *
         * Voir getAutorisationLibelle() pour le libellé correspondant.
         * -1 = valeur non renseignée.
         *
         * @var int
         */
        public int $autorisation = -1;

        /**
         * Code de calcul de la mission (champ SIFAC ABREC).
         *
         * Voir getCalculLibelle() pour le libellé correspondant.
         * -1 = valeur non renseignée.
         *
         * @var int
         */
        public int $calcul = -1;
   
        /**
         * Retourne le libellé correspondant au statut d'autorisation de la mission.
         *
         * @return string Libellé lisible du statut d'autorisation ou chaîne vide si inconnu.
         */
        public function getAutorisationLibelle(): string
        {
            return match ($this->autorisation) 
            {
                1 => 'Demande saisie',
                2 => 'Demande acceptée',
                3 => 'Dépl. effectué',
                4 => 'Dépl. accepté',
                5 => 'Demande attend documents',
                6 => 'Déplacement attend documents',
                default => ''
            };
        }

        /**
         * Retourne le libellé correspondant à l'état de calcul de la mission.
         *
         * @return string Libellé lisible de l'état de calcul ou chaîne vide si inconnu.
         */
        public function getCalculLibelle(): string  
        {
            return match ($this->calcul) 
            {
                0 => 'Ouvert',
                1 => 'A calculer',
                2 => 'Calculé',
                3 => 'Annulé',
                default => ''
            };
        }
    }
?>