<?php

    /**
     * Représente un rôle SIFAC attribué à un utilisateur.
     *
     * Un rôle peut être :
     * - direct (attribué directement)
     * - indirect (hérité d'un rôle complexe)
     *
     * Cette classe permet également de gérer la période de validité
     * d'un rôle et son statut de synchronisation avec SIFAC.
     *
     * @author Pascal COMTE / Université Paris 1 Panthéon-Sorbonne
     */
    class SifacRoleClass
    {

	    // Rôle affecté directement à l'agent ou via un rôle complexe
        /**
         * Rôle affecté directement à l'utilisateur.
         */
        public const SIFAC_ROLE_DIRECT = '';

        /**
         * Rôle affecté indirectement (hérité d'un rôle complexe).
         */
        public const SIFAC_ROLE_INDIRECT = 'X';

        /**
         * Indique que le rôle est déjà enregistré dans SIFAC.
         */
        public const SAVE_TO_SIFAC = 'X';

        // Statut du rôle
        /**
         * Indique que le rôle doit être enregistré dans SIFAC.
         */
        public const NOT_SAVE_TO_SIFAC = '';

        /**
         * Séparateur utilisé dans les chaînes d'entitlement.
         */
        public const ENTITLEMENTSSEPARATOR = '#';

        /**
         * Nom du rôle SIFAC.
         *
         * @var string
         */
        public string $name = '';

        /**
         * Description du rôle.
         *
         * @var string
         */
        public string $description = '';

        /**
         * Date de début de validité du rôle.
         *
         * @var string
         */
        public string $startdate = '';

        /**
         * Date de fin de validité du rôle.
         *
         * @var string
         */
        public string $enddate = '';

        /**
         * Type d'affectation du rôle (direct ou indirect).
         *
         * @var string
         */
        public string $affectation = self::SIFAC_ROLE_DIRECT;

        /**
         * Statut de sauvegarde du rôle dans SIFAC.
         *
         * @var string
         */
        public string $status = self::SAVE_TO_SIFAC;
   
        /**
         * Charge les informations d'un rôle SIFAC à partir d'un tableau.
         *
         * Les clés acceptées varient selon la source : soit le format du cache
         * de rôles (NAME, TEXT ou DESCRIPTION, STARTDATE, ENDDATE), soit
         * directement les colonnes SIFAC agr_users/agr_define (AGR_NAME,
         * FROM_DAT, TO_DAT, COL_FLAG). Les deux jeux de clés peuvent être
         * présents simultanément ; en ce cas AGR_NAME/FROM_DAT/TO_DAT/COL_FLAG
         * écrasent NAME/STARTDATE/ENDDATE (traités en second).
         *
         * @param array $roleData Données du rôle, issues d'une requête SIFAC
         *                        ou du cache de rôles.
         * @return bool Toujours true (aucune condition d'échec).
         */
        public function load(array $roleData) :bool
        {

            $roleData = array_change_key_case_recursive($roleData,CASE_UPPER);
            if (isset($roleData['NAME'])) $this->name = trim($roleData['NAME'] . "");
            if (isset($roleData['TEXT'])) $this->description = trim($roleData['TEXT'] . "");
            if (isset($roleData['DESCRIPTION'])) $this->description = trim($roleData['DESCRIPTION'] . "");
            if (isset($roleData['STARTDATE'])) $this->startdate = trim($roleData['STARTDATE'] . "");
            if (isset($roleData['ENDDATE'])) $this->enddate = trim($roleData['ENDDATE'] . "");

            if (isset($roleData['AGR_NAME'])) $this->name = trim($roleData['AGR_NAME'] . "");
            if (isset($roleData['FROM_DAT'])) $this->startdate = trim($roleData['FROM_DAT'] . "");
            if (isset($roleData['TO_DAT'])) $this->enddate = trim($roleData['TO_DAT'] . "");
            if (isset($roleData['COL_FLAG'])) $this->affectation = trim($roleData['COL_FLAG'] . "");
            return true;
        }
    }

?>