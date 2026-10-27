<?php

    /*
     * Enumère les différents type de requêtes possibles sur SIFAC
     * - QUERY_ECC : Interroge SIFAC ECC
     * - QUERY_POSTGRE : Interroge l'entrepot de données SIFAC+ au format POSTGRESQL
     * - QUERY_DIRECT : Interroge les CDSViews SIFAC+ directement
     */
    enum SifacSqlMode: string {
        case QUERY_ECC = 'ECC';
        case QUERY_POSTGRE = 'POSTGRE';
        case QUERY_DIRECT = 'DIRECT';
    }

    /**
     * Représente une requête destinée au système SIFAC.
     *
     * Cette classe stocke les différentes parties d'une requête :
     * - clause SELECT
     * - clause FROM
     * - clause WHERE
     *
     * @author Pascal COMTE / Université Paris 1 Panthéon-Sorbonne
     */
    class SifacQueryClass
    {
        /**
         * Type de la requète (QUERY_ECC, QUERY_POSTGRE, QUERY_DIRECT).
         *
         * @var SifacSqlMode
         */
        public SifacSqlMode $querytype = SifacSqlMode::QUERY_ECC;


        /**
         * Liste des champs de la clause SELECT.
         *
         * @var string[]
         */
        public array $selectclause = [];

        /**
         * Nom de la table SIFAC interrogée (utilisé comme clause FROM).
         *
         * @var string
         */
        public string $fromclause = '';

        /**
         * Liste des conditions de la clause WHERE.
         *
         * La syntaxe attendue dépend de $querytype (voir QUERY_ECC, QUERY_POSTGRE,
         * QUERY_DIRECT) : SQL-like en mode ECC (ex. "FIELD = 'VALUE'",
         * "AND FIELD2 <> 'X'"), OData en mode DIRECT, PostgREST en mode POSTGRE.
         *
         * @var string[]
         */
        public array $whereclause = [];

        /**
         * Paramètre de pagination des données, démarre à la ligne skip.
         *
         * Utilisé uniquement en mode QUERY_DIRECT (pagination OData) ; ignoré
         * par les autres modes. -1 = non renseigné, readSifacTable() applique
         * alors la valeur par défaut 0.
         *
         * @var int
         */
        public int $skip = -1;

        /**
         * Paramètre de pagination des données, prend les top lignes à partir de la ligne skip.
         *
         * Utilisé uniquement en mode QUERY_DIRECT (pagination OData) ; ignoré
         * par les autres modes. -1 = non renseigné, readSifacTable() applique
         * alors la valeur par défaut 100.
         *
         * @var int
         */
        public int $top = -1;

    }

?>