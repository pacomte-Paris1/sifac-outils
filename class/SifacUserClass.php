<?php

    /**
     * Représente un utilisateur SIFAC.
     *
     * Cette classe contient les informations principales
     * nécessaires pour identifier un utilisateur dans SIFAC
     * ainsi que la période de validité de son compte.
     *
     * @author Pascal COMTE / Université Paris 1 Panthéon-Sorbonne
     */
    class SifacUserClass
    {
        /**
         * Nom de l'utilisateur SIFAC.
         *
         * @var string
         */
        public $name = '';

        /**
         * Prénom de l'utilisateur SIFAC.
         *
         * @var string
         */
        public $firstname = '';

        /**
         * Identifiant de connexion SIFAC.
         *
         * @var string
         */
        public $login = '';

        /**
         * Date de début de validité du compte (format SIFAC YYYYMMDD).
         *
         * @var string
         */
        public $startdate = '';

        /**
         * Date de fin de validité du compte (format SIFAC YYYYMMDD).
         *
         * @var string
         */
        public $enddate = '';

        /**
         * Retourne le nom court de la classe.
         *
         * @return string Nom de la classe sans namespace.
         */
        public static function className() :string
        {
            $arrayClassName = explode('\\',__CLASS__);
            return end($arrayClassName);
        }

        /**
         * Charge les informations d'un utilisateur SIFAC.
         *
         * Les données peuvent provenir :
         * - directement d'une requête SIFAC (BNAME, GLTGV, GLTGB, NAME_FIRST, NAME_LAST)
         * - d'un tableau interne sérialisé (login, startdate, enddate)
         *
         * @param array<string, string> $userData Données provenant de SIFAC.
         * @return bool Toujours true (aucune condition d'échec).
         */
        public function load(array $userData) :bool
        {
            $userData = array_change_key_case($userData, CASE_UPPER);

            if (isset($userData['BNAME'])) $this->login = trim($userData['BNAME'] . "");
            if (isset($userData['GLTGV'])) $this->startdate = trim($userData['GLTGV'] . "");
            if (isset($userData['GLTGB'])) $this->enddate = trim($userData['GLTGB'] . "");

            if (isset($userData['NAME_FIRST'])) $this->firstname = trim($userData['NAME_FIRST']);
            if (isset($userData['NAME_LAST'])) $this->name = trim($userData['NAME_LAST']);

            if (isset($userData['LOGIN'])) $this->login = trim($userData['LOGIN'] . "");
            if (isset($userData['STARTDATE'])) $this->startdate = trim($userData['STARTDATE'] . "");
            if (isset($userData['ENDDATE'])) $this->enddate = trim($userData['ENDDATE'] . "");

            return true;
        }

        /**
         * Retourne la représentation JSON de l'utilisateur SIFAC.
         *
         * Contient uniquement className, login, startdate et enddate ;
         * name et firstname ne sont pas inclus dans la sérialisation.
         *
         * @param bool $prettyPrint Si true, retourne la chaine JSON formaté (pour l'affichage). false par défaut
         * @return string Chaîne JSON sérialisée.
         */
        public function jsonString(bool $prettyPrint = false) :string
        {
            $jsonData = [
                        'className' => $this->className(), 
                        'login' => $this->login,
                        'startdate' => $this->startdate,
                        'enddate' => $this->enddate
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


        /**
         * Génère le tableau HTML des rôles SIFAC de l'utilisateur.
         *
         * Seuls les rôles avec affectation directe sont affichés.
         * Les rôles hérités sont ignorés.
         *
         * Chaque ligne affichée contient :
         * - Nom du rôle
         * - Description
         * - Dates de début et de fin de validité
         *
         * Une colonne "Suppression" est prévue dans l'en-tête (masquée par la
         * classe CSS 'hidden'), mais son contenu (case à cocher) est
         * actuellement en commentaire dans le code et n'est donc pas généré.
         *
         * @param array<int, sifacroleclass> $sifacRoleArray Tableau d'objets rôles SIFAC
         *
         * @return string Code HTML du tableau des rôles, ou chaîne vide si login vide
         */
        public function sifacRolesHtml(array $sifacRoleArray) :string
        {
            $htmlText = '';
            if (trim($this->login . '') != '')
            {
                $htmlText .= "Liste des rôles SIFAC pour " . $this->firstname . " " . $this->name . ' (<label class="bold">' . $this->login . "</label>)<br>" . PHP_EOL;
                $htmlText .= '<table class="tableausimple" id="userroletable" name="userroletable">' . PHP_EOL;
                $htmlText .= '<thead>' . PHP_EOL;
                $htmlText .= "  <tr class='entete'>" . PHP_EOL;
                $htmlText .= "    <th class='titresimple cursorpointer'>Nom du rôle <span class='sortindicator'> </span></th>" . PHP_EOL;
                $htmlText .= "    <th class='titresimple cursorpointer'>Description <span class='sortindicator'> </span></th>" . PHP_EOL;
                $htmlText .= "    <th class='titresimple cursorpointer'>Début validité <span class='sortindicator'> </span></th>" . PHP_EOL;
                $htmlText .= "    <th class='titresimple cursorpointer'>Fin validité <span class='sortindicator'> </span></th>" . PHP_EOL;
                $htmlText .= "    <th class='titresimple cursordefault hidden'>Suppression</th>" . PHP_EOL;
                $htmlText .= "  </tr>" . PHP_EOL;
                $htmlText .= "</thead>" . PHP_EOL;
                $htmlText .= '<tbody>' . PHP_EOL;

                foreach($sifacRoleArray as $index => $sifacRole)
                {
                    if ($sifacRole->affectation != sifacroleclass::SIFAC_ROLE_DIRECT)
                    {
                        // On n'affiche pas le rôle car il est hérité
                        continue;
                    }
                    $extraclass = '';
                    $htmlText .= "<tr class='lignerole $extraclass ' " . ATTR_INDEX . "='$index' >" . PHP_EOL;
                    $roleId = $sifacRole->name . '_' . $sifacRole->startdate . '_' . $sifacRole->enddate;
                    $htmlText .= "  <td class='cellulesimple " . SIFACNOMROLECELLULE . "' id='$roleId'>" . PHP_EOL;
                    $htmlText .= $sifacRole->name;
                    $htmlText .= "  </td>" . PHP_EOL;
                    $htmlText .= "  <td class='cellulesimple " . SIFACDESCRIPTIONROLECELLULE  . "'>" . PHP_EOL;
                    $htmlText .= htmlspecialchars($sifacRole->description);
                    $htmlText .= "  </td>" . PHP_EOL;
                    $htmlText .= "  <td class='cellulesimple " . SIFACDEBUTVALIDITECELLULE ."'>" . PHP_EOL;
                    $htmlText .= formatdate($sifacRole->startdate);
                    $htmlText .= "  </td>" . PHP_EOL;
                    $htmlText .= "  <td class='cellulesimple " . SIFACFINVALIDITECELLULE ."'>" . PHP_EOL;
                    $htmlText .= formatdate($sifacRole->enddate);
                    $htmlText .= "  </td>" . PHP_EOL;
                    // $htmlText .= "  <td class='cellulesimple centeraligntext nopadding hidden " . SIFACSUPPRROLECELLULE . "' >" . PHP_EOL;
                    // $checkboxId = "checksifacrole[$index]";
                    // $disabled = " disabled ";
                    // $htmlText .= "      <input type='checkbox' id='$checkboxId' name='$checkboxId' class='checksifacrole nomargin ' $disabled />" . PHP_EOL;
                    // $htmlText .= "  </td>" . PHP_EOL;
                    $htmlText .= "</tr>" . PHP_EOL;
                }

                $htmlText .= '</tbody>' . PHP_EOL;
                $htmlText .= '</table>' . PHP_EOL;
            }
            return $htmlText;
        }

        /**
         * Génère un bloc HTML présentant les informations SIFAC de l'utilisateur
         * (login, dates de début et fin de validité du compte).
         *
         * Retourne une chaîne vide si le login est vide. Chaque date n'est
         * affichée que si elle est non vide et différente de 0 (intval).
         *
         * @return string Code HTML du bloc d'informations SIFAC, ou chaîne vide
         *                si login vide.
         */
        public function sifacInfosHtml() :string
        {
            $htmlText = '';
            if (trim($this->login . '') != '')
            {
                $htmlText .= "
                <div class='divsifacinfouser' id='divsifacinfouser' name='divsifacinfouser'>
                    <table id='tableuserinfo' class='tableausimple'>
                        <thead>
                            <tr>
                                <th class='titresimple' colspan='3'>Informations SIFAC de l'agent</th>
                            </tr>
                            <tr>
                                <th class='cellulesimple'>Login SIFAC</th>
                                <th class='cellulesimple'>Début validité</th>
                                <th class='cellulesimple'>Fin validité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class='cellulesimple centeraligntext " . SIFACLOGINCELLULE ."'>";
                $htmlText .= $this->login;
                $htmlText .= "  </td>
                                <td class='cellulesimple centeraligntext " . SIFACDEBUTVALIDITECELLULE . "'>";
                if ($this->startdate != '' and intval($this->startdate) != 0) $htmlText .= formatdate($this->startdate);
                $htmlText .= "
                                </td>
                                <td class='cellulesimple centeraligntext " . SIFACFINVALIDITECELLULE . "'>";
                if ($this->enddate != '' and intval($this->enddate) != 0) $htmlText .= formatdate($this->enddate);
                $htmlText .= "
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <br>
                ";
            }
            return $htmlText;
        }
    }

    ?>