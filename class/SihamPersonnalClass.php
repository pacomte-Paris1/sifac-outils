<?php

    /**
     * Représente les informations personnelles d’un agent SIHAM.
     *
     * Cette classe encapsule les données d'identité et de contact d’un agent :
     * - matricule SIHAM
     * - noms et prénom
     * - date de naissance
     * - identifiant utilisateur (uid)
     * - adresse email professionnelle
     *
     * Les données sont généralement chargées depuis une réponse
     * de l’API SIHAM et peuvent être sérialisées en JSON.
     *
     * @package SIHAM
     */
    class SihamPersonnalClass
    {
        /**
         * Matricule SIHAM de l'agent.
         *
         * @var string
         */
        public string $matricule = '';

        /**
         * Nom patronymique de l'agent.
         *
         * @var string
         */
        public string $nompatronymique = '';

        /**
         * Nom usuel de l'agent.
         *
         * @var string
         */
        public string $nomusuel = '';

        /**
         * Prénom de l'agent.
         *
         * @var string
         */
        public string $prenom = '';

        /**
         * Adresse email professionnelle de l'agent.
         *
         * @var string
         */
        public string $mail = '';

        /**
         * Date de naissance de l'agent (format lisible).
         *
         * @var string
         */
        public string $datenaissance = '';

        /**
         * Identifiant utilisateur (login SIHAM).
         *
         * @var string
         */
        public string $uid = '';

        /**
         * Identifiant interne du dossier SIHAM.
         *
         * @var string
         */
        public string $id = '';


        /**
         * Retourne le nom court de la classe.
         *
         * @return string Nom de la classe sans namespace.
         */
        static function className() :string
        {
            $arrayClassName = explode('\\',__CLASS__);
            return end($arrayClassName);
        }

        /**
         * Charge les informations personnelles d’un agent depuis une réponse SIHAM.
         *
         * Les données sont extraites depuis la structure retournée par l’API SIHAM,
         * incluant l'identité, la naissance, les méthodes de contact et les identifiants.
         *
         * @param array<string,mixed> $sihamData Données issues de l’API SIHAM.
         * @return bool Toujours true.
         */
        public function load(array $sihamData) :bool
        {
            if (isset($sihamData['id'])) $this->id = trim($sihamData['id'] . "");
            if (isset($sihamData['uid'])) $this->uid = trim($sihamData['uid'] . "");
            if (isset($sihamData['identificationdudossier']['matricule'])) $this->matricule = trim($sihamData['identificationdudossier']['matricule'] . "");
            if (isset($sihamData['nompatronymique']['nompatronymique'])) $this->nompatronymique = trim($sihamData['nompatronymique']['nompatronymique'] . "");
            if (isset($sihamData['nomusuel']['nomusuel'])) $this->nomusuel = trim($sihamData['nomusuel']['nomusuel'] . "");
            if (isset($sihamData['prenom']['prenom'])) $this->prenom = trim($sihamData['prenom']['prenom'] . "");
            if (isset($sihamData['naissance']['date_naissance'])) $this->datenaissance = formatdate(trim($sihamData['naissance']['date_naissance']));

            if (isset($sihamData['methodescontacts']))
            {
                foreach($sihamData['methodescontacts'] as $contact)
                {
                    if (!isset($contact['type_code']) or !isset($contact['temoin_telephone']))
                    {
                        continue;
                    }
                    if ($contact['type_code'] == 'MPR' and $contact['temoin_telephone'] == 0)
                    {
                        if (!isset($contact['numero_mail']))
                        {
                            continue;
                        }
                        $this->mail = trim($contact['numero_mail'] . "");
                    }
                }
            }
            

            return true;
        }

        /**
         * Retourne la représentation JSON des informations personnelles de l’agent.
         *
         * @param bool $prettyPrint Si true, retourne la chaine JSON embellie (pour l'affichage). false par défaut
         * @return string Chaîne JSON sérialisée.
         */
        public function jsonString(bool $prettyPrint = false) :string
        {
            $jsonData = [
                        'className' => $this->className(), 
                        'id' => $this->id,
                        'matricule' => $this->matricule,
                        'nompatronymique' => $this->nompatronymique,
                        'nomusuel' => $this->nomusuel,
                        'prenom' => $this->prenom,
                        'mail' => $this->mail,
                        'uid' => $this->uid
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