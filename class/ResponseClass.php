<?php

    /*
     * Enumère les différents type de requêtes possibles sur SIFAC
     * - STATUT_OK : Tout s'est bien passée
     * - STATUT_KO : Un problème a été détecté
     */
    enum ResponseStatus: string {
        case STATUT_OK = 'OK';
        case STATUT_KO = 'KO';
    }

    /**
     * Classe standard de réponse utilisée dans l'application.
     *
     * Elle permet d'encapsuler :
     * - le statut de l'opération
     * - la description du résultat
     * - les données retournées
     *
     * Cette classe est utilisée pour uniformiser les réponses
     * des appels LDAP, CIAM ou SIFAC.
     *
     * @author Pascal COMTE / Université Paris 1 Panthéon-Sorbonne
     */
    class ResponseClass
    {

        /**
         * Données retournées par l'opération.
         *
         * @var array<mixed>
         */
        public array $data = [];

        /**
         * Statut de la réponse (STATUT_OK ou STATUT_KO).
         *
         * @var ResponseStatus
         */
        public ResponseStatus $status = ResponseStatus::STATUT_KO;

        /**
         * Description du résultat.
         *
         * @var string
         */
        public string $description = '';

        /**
         * Retourne la représentation JSON de la réponse.
         *
         * @param bool $prettyPrint Si true, retourne la chaine JSON embellie (pour l'affichage). false par défaut
         * @return string Chaîne JSON sérialisée.
         */
        public function jsonString(bool $prettyPrint = false) :string
        {
            $jsonData = [
                        'data' => $this->data,
                        'status' => $this->status,
                        'description' => $this->description
                        ];

            if ($prettyPrint)
            {
                return json_encode($jsonData,JSON_PRETTY_PRINT);
            }
            else
            {
                return json_encode($jsonData);
            }
        }

    }

?>