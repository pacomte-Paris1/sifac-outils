<?php

    /**
     * Représente les paramètres d'une recherche dans l'annuaire LDAP.
     *
     * Cette classe encapsule les éléments nécessaires pour effectuer une
     * requête LDAP :
     * - la valeur recherchée
     * - les attributs à comparer
     * - les attributs à retourner
     * - les options de recherche
     *
     * Elle est utilisée conjointement avec {@see LdapFunctions}.
     *
     * @author Pascal COMTE / Université Paris 1 Panthéon-Sorbonne
     */
    class LdapSearchClass
    {

        /**
         * Retourne toutes les réponses correspondant à la recherche.
         *
         * @var int
         */
        public const LDAP_OPTION_ALL_RESPONSES = 0;

        /**
         * Ne retourne qu'une seule réponse. Si plusieurs réponses correspondent
         * à la recherche, celle-ci est considérée par LdapFunctions::getLDAPInfos()
         * comme sans résultat exploitable (STATUT_OK, data vide), et non comme
         * une erreur.
         *
         * @var int
         */
        public const LDAP_OPTION_ONE_RESPONSE = 1;

        /**
         * Recherche dans tout l'annuaire LDAP, en ignorant la restriction
         * LDAP_EXTRACRITERE normalement appliquée. Par défaut (sans cette
         * option), la recherche ne porte que sur le personnel.
         *
         * @var int
         */
        public const LDAP_OPTION_FULL_SEARCH = 2;

        // Mode de recherche dans LDAP
        /**
         * Mode de recherche « saisie libre » : l'utilisateur saisit un critère
         * texte et valide via un bouton, ce qui déclenche une recherche LDAP
         * côté serveur (voir identites.php, action LOAD_USER_RESOURCE).
         *
         * Remarque : dans les deux modes (DIRECT et WSGROUPS), la recherche
         * LDAP finale est effectuée via LdapFunctions::getLDAPInfos() ; seule
         * l'interface de saisie diffère.
         *
         * @var string
         */
        public const LDAP_MODE_DIRECT = 'LDAP';

        /**
         * Mode de recherche assisté par un widget d'autocomplétion côté client,
         * interrogeant un webservice externe (WSGROUPURL/searchUserCAS) pour
         * proposer des suggestions de personnes en temps réel (recherche sur
         * eduPersonPrincipalName, sn, givenName, filtrée par
         * eduPersonAffiliation). Voir identites.php.
         *
         * @var string
         */
        public const LDAP_MODE_WSGROUPS = 'WSGROUPS';


        /**
         * Valeur utilisée pour la recherche LDAP.
         *
         * Si $matchattrs est vide, cette valeur est utilisée telle quelle comme
         * filtre LDAP brut (voir LdapFunctions::getLDAPInfos()).
         *
         * @var string
         */
        public string $searchinfo = '';

        /**
         * Liste des attributs LDAP sur lesquels comparer $searchinfo
         * (recherche en OU). Si ce tableau est vide, $searchinfo est utilisé
         * directement comme filtre LDAP (voir LdapFunctions::getLDAPInfos()).
         *
         * @var string[]
         */
        public array $matchattrs = [];

        /**
         * Liste des attributs LDAP à retourner dans le résultat.
         *
         * @var string[]
         */
        public array $returnedattrs = [];

        /**
         * Options de recherche LDAP (bitmask).
         *
         * Voir les constantes LDAP_OPTION_*.
         *
         * @var int
         */
        public int $options = self::LDAP_OPTION_ONE_RESPONSE;
    }


?>