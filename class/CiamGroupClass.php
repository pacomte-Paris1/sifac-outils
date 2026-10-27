<?php
    /**
     * Classe représentant un groupe CIAM (Customer Identity and Access Management).
     *
     * Cette classe encapsule les données et comportements associés à un groupe SCIM :
     * - gestion des informations du groupe (id, nom, dates, etc.)
     * - gestion des membres (ajout / suppression)
     * - suivi des modifications pour éviter les sauvegardes inutiles
     * - génération de la représentation JSON conforme à l’API SCIM
     *
     * Elle est utilisée pour interagir avec les API CIAM (lecture et mise à jour).
     *
     * @author Pascal COMTE / Université Paris 1 Panthéon-Sorbonne
     */
    class CiamGroupClass    
    {

        /**
         * Identifiant unique de la ressource groupe dans le CIAM.
         * 
         * @var string
         */
        public string $id = '';

        /**
         * Identifiant externe du groupe CIAM.
         * 
         * @var string
         */
        public string $externalid = '';

        /**
         * Date de création du groupe CIAM.
         * 
         * @var string
         */
        public string $creationdate = '';

        /**
         * Date de dernière modification du groupe CIAM.
         * 
         * @var string
         */
        public string $modifdate = '';

        /**
         *  Nom du groupe CIAM.
         * 
         * @var string
         */
        public string $displayname = '';

        /**
         * Liste des membres du groupe (format SCIM).
         *
         * Exemple :
         * [
         *   ['value' => 'userid1'],
         *   ['value' => 'userid2']
         * ]
         *
         * @var array<int, array<string, string>>
         */
        public array $ciammembers = [];

        /**
         * Indique si le groupe a été modifié depuis son chargement.
         *
         * @var bool
         */
        private bool $groupmodified = false;

        /**
         * Retourne le nom de la classe sans namespace.
         *
         * @return string Nom de la classe
         */
        static function className() :string        
        {
            $arrayClassName = explode('\\',__CLASS__);
            return end($arrayClassName);
        }

        /**
         * Ajoute un utilisateur au groupe.
         *
         * Si l'utilisateur est déjà présent, aucune action n'est effectuée.
         *
         * @param string $userId Identifiant de l'utilisateur à ajouter
         *
         * @return void
         */
        public function addMember(string $userId)
        {
            $userIndex = array_search($userId, array_column($this->ciammembers, 'value'));
            if ($userIndex === false)
            {
                // var_dump(__FUNCTION__ . " le userid $userId est ajouté au groupe " . $this->displayname);
                $this->ciammembers[] = ['value' => trim($userId)];
                $this->groupmodified = true;
            }
            else
            {
                // var_dump(__FUNCTION__ . " le userid $userId est déjà dans le groupe " . $this->displayname);
            }
        }

        /**
         * Supprime un utilisateur du groupe.
         *
         * Si l'utilisateur n'est pas présent, aucune action n'est effectuée.
         *
         * @param string $userId Identifiant de l'utilisateur à supprimer
         *
         * @return void
         */
        public function removeMember(string $userId)
        {
            $userIndex = array_search($userId, array_column($this->ciammembers, 'value'));
            if ($userIndex !== false)
            {
                // var_dump(__FUNCTION__ . " le userid $userId va être supprimé du groupe " . $this->displayname);
                unset($this->ciammembers[$userIndex]);
                // On réindex les membres du groupes car le premier index doit commencer à 0
                $this->ciammembers = array_values($this->ciammembers);
                $this->groupmodified = true;
            }
            else
            {
                // var_dump(__FUNCTION__ . " le userid $userId n'est pas dans le groupe " . $this->displayname);
            }
        }

        /**
         * Indique si le groupe a été modifié.
         *
         * @return bool True si des modifications ont été effectuées, sinon false
         */
        public function isModified() :bool
        {
            return $this->groupmodified;
        }

        /**
         * Charge les données d’un groupe depuis un tableau SCIM.
         *
         * Réinitialise l'indicateur de modification (isModified() retournera false
         * juste après l'appel), le chargement étant considéré comme l'état de
         * référence du groupe.
         *
         * @param array<string, mixed> $groupData Données du groupe issues de l’API CIAM
         *
         * @return bool Toujours true si chargement effectué
         */
        public function load(array $groupData) :bool        
        {
            if (isset($groupData['id'])) $this->id = trim($groupData['id']. '');
            if (isset($groupData['externalId'])) $this->externalid = trim($groupData['externalId'] . '');
            if (isset($groupData['meta']['created'])) $this->creationdate = trim($groupData['meta']['created'] . '');
            if (isset($groupData['meta']['lastModified'])) $this->modifdate = trim($groupData['meta']['lastModified'] . '');
            if (isset($groupData['displayName'])) $this->displayname = trim($groupData['displayName'] . '');
            if (isset($groupData['members']) and is_array($groupData['members']))
            {
                $this->ciammembers = $groupData['members'];
            }

            $this->groupmodified = false;
            return true;
        }

        /**
         * Génère une représentation JSON complète du groupe (format SCIM).
         *
         * @param bool $prettyPrint Active un affichage formaté (lisible)
         *
         * @return string JSON du groupe
         */
        public function jsonString(bool $prettyPrint = false) :string
        {
            $ciamGroup = [];
            $ciamGroup['schemas'] = ["urn:ietf:params:scim:schemas:core:2.0:Group"];
            $ciamGroup['id'] = $this->id;
            $ciamGroup['externalId'] = $this->externalid;
            $ciamGroup['meta'] = ["resourceType" => "Group" ,"created" => $this->creationdate, 'lastModified' => $this->modifdate];
            $ciamGroup['displayName'] = $this->displayname;
            $ciamGroup['members'] = $this->ciammembers;

            if ($prettyPrint)
            {
                return json_encode($ciamGroup,JSON_UNESCAPED_SLASHES+JSON_PRETTY_PRINT);
            }
            else
            {
                return json_encode($ciamGroup,JSON_UNESCAPED_SLASHES);
            }
        }

        /**
         * Génère une représentation JSON minimale pour la modification du groupe.
         *
         * Cette version est utilisée pour les requêtes PUT (mise à jour).
         *
         * @param bool $prettyPrint Active un affichage formaté (lisible)
         *
         * @return string JSON minimal du groupe
         */
        public function modifyJsonString(bool $prettyPrint = false) :string
        {
            $memberArray = [];
            foreach($this->ciammembers as $member)
            {
                $memberArray[] = ['value' => $member['value']];
            }

            $jsonArray = [
                'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:Group'],
                'id' => $this->id,
                'meta' => ['resourceType' =>'Group'],
                'displayName' => $this->displayname,
                'members' => $memberArray
            ];
            if ($prettyPrint)
            {
                return json_encode($jsonArray,JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT);
            }
            else
            {
                return json_encode($jsonArray,JSON_UNESCAPED_SLASHES);
            }
        }
    }

?>