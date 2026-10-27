<?php
    require_once('ResponseClass.php');

    /**
     * Classe utilitaire permettant d'effectuer des recherches dans l'annuaire LDAP.
     *
     * Cette classe encapsule les appels aux fonctions LDAP PHP afin de :
     * - se connecter au serveur LDAP
     * - effectuer une recherche avec filtres
     * - récupérer les attributs demandés
     *
     * Les résultats sont retournés sous forme d'objet ResponseClass.
     *
     * @author Pascal COMTE / Université Paris 1 Panthéon-Sorbonne
     */
    class LdapFunctions
    {

        /**
         * Effectue une recherche dans l'annuaire LDAP.
         *
         * Si $filter n'est pas vide, construit un filtre en OU recherchant
         * $searchInfo (échappé) dans chacun des attributs listés. Si $filter est
         * vide, $searchInfo est utilisé tel quel comme filtre LDAP brut (sans
         * échappement dans ce cas). Sauf si l'option LDAP_OPTION_FULL_SEARCH est
         * positionnée, la constante LDAP_EXTRACRITERE est systématiquement ajoutée
         * (ET) au filtre construit.
         *
         * Pour chaque attribut demandé dans $restriction, seule la première valeur
         * est conservée dans le résultat ; les valeurs supplémentaires d'un
         * attribut multivalué sont ignorées.
         *
         * @param string $searchInfo Valeur recherchée dans LDAP, ou filtre LDAP
         *                           brut si $filter est vide.
         * @param string[] $filter Liste des attributs LDAP sur lesquels rechercher
         *                         $searchInfo (recherche en OU). Vide = $searchInfo
         *                         est utilisé directement comme filtre LDAP.
         * @param string[] $restriction Liste des attributs LDAP à retourner (seule
         *                              la première valeur de chaque attribut est
         *                              conservée).
         * @param int $options Options de recherche (bitmask de ldapsearchclass).
         *  Options possibles :
         *      - ldapsearchclass::LDAP_OPTION_ONE_RESPONSE : si plus d'une entrée
         *        est trouvée, la réponse est considérée comme une absence de
         *        correspondance exploitable (STATUT_OK, data vide)
         *      - ldapsearchclass::LDAP_OPTION_FULL_SEARCH : ignore LDAP_EXTRACRITERE
         * 
         * @return ResponseClass Résultat contenant :
         *                       - status : ResponseStatus::STATUT_OK|STATUT_KO
         *                       - description : message associé
         *                       - data : tableau indexé numériquement de résultats
         *                         LDAP (un tableau associatif attribut => valeur
         *                         par entrée) ; vide si aucune correspondance, si
         *                         plusieurs correspondances avec
         *                         LDAP_OPTION_ONE_RESPONSE, ou en cas d'erreur
         *                         (connexion, bind, réponse non conforme).
         */
        public function getLDAPInfos(string $searchInfo, array $filter, array $restriction, int $options) :ResponseClass
        {
            $response = new ResponseClass();
            $response->status = ResponseStatus::STATUT_KO;

            $conLdap = null;
            $info = [];

            try {
                $conLdap = @ldap_connect(LDAPSERVER);
                if (!$conLdap)
                {
                    $response->status = ResponseStatus::STATUT_KO;
                    $response->description = "Une erreur s'est produite lors de la connexion à LDAP.<br>Veuillez vérifier la configuration.";
                    $response->data = [];
                }
                else
                {
                    ldap_set_option($conLdap, LDAP_OPT_PROTOCOL_VERSION, 3);
                    if (!ldap_bind($conLdap, LDAPLOGIN, LDAPPASSWD))
                    {
                        $response->status = ResponseStatus::STATUT_KO;
                        $response->description = "Une erreur s'est produite lors de l'appel à LDAP bind.<br>Veuillez vérifier la configuration.";
                        $response->data = [];
                    }
                    else
                    {
                        $filtre = "";
                        if (count($filter) > 0)
                        {
                            foreach($filter as $nomFiltre)
                            {
                                if ($filtre == '') $filtre = "(|";
                                // $filtre  = $filtre . "(" . strtolower($nomFiltre) . "=$searchInfo)";
                                $filtre  = $filtre . "(" . strtolower($nomFiltre) . "=" . ldap_escape($searchInfo, '', LDAP_ESCAPE_FILTER) . ")";
                            }
                            if ($filtre != '')
                            {
                                $filtre  = $filtre . ')';
                            }
                        }
                        else
                        {
                            $filtre = $searchInfo;
                        }
                        if ($filtre != '') 
                        {
                            if (trim(LDAP_EXTRACRITERE) != '' and !($options&ldapsearchclass::LDAP_OPTION_FULL_SEARCH))
                            {
                                $filtre = '(&' . $filtre . ' ' . LDAP_EXTRACRITERE . ')';
                            }
                        }
                        // error_log(basename(__FILE__) . stripAccents(" LDAP filtre => $filtre"));
                        // error_log(basename(__FILE__) . stripAccents(" LDAP restriction => " . print_r($restriction,true)));

                        // $filtre = ldap_escape($filtre, '', LDAP_ESCAPE_FILTER);
                        $dn = LDAPSEARCHBASE;
                        $sr = @ldap_search($conLdap, $dn, $filtre, $restriction);
                        $info = @ldap_get_entries($conLdap, $sr);
                    }
                }
            } catch (\Exception $error) {
                $response->status = ResponseStatus::STATUT_KO;
                $response->description = "Une erreur s'est produite lors de l'appel à LDAP.<br>" . $error->getMessage();
                $response->data = [];
            }

            // if ($response->status != ResponseStatus::STATUT_KO)
            if ($response->description == '')
            {
                // error_log(basename(__FILE__) . stripAccents(" LDAP Result = " . print_r($info,true)));
                if (!isset($info['count']))
                {
                    $response->status = ResponseStatus::STATUT_KO;
                    $response->description = "Une erreur s'est produite lors de l'appel à LDAP.<br>La réponse n'est pas conforme : count manquant.";
                    $response->data = [];
                    return $response;
                }
                $count = $info['count'];
                if ($count == 0)
                {
                    // error_log(basename(__FILE__) . stripAccents(" Aucune correspondance trouvée avec $searchInfo"));
                    $response->status = ResponseStatus::STATUT_OK;
                    $response->description = 'Aucune correspondance n\'a été trouvée dans LDAP.';
                    $response->data = []; 
                }
                elseif (($count >= 2) and ($options&ldapsearchclass::LDAP_OPTION_ONE_RESPONSE))
                {
                    // error_log(basename(__FILE__) . stripAccents(" Plusieurs correspondances trouvées pour $searchInfo"));
                    $response->status = ResponseStatus::STATUT_OK;
                    $response->description = 'Plusieurs correspondances ont été trouvées dans LDAP.<br>Veuillez préciser votre recherche.';
                    $response->data = []; 
                }
                else
                {
                    $response->status = ResponseStatus::STATUT_OK;
                    $response->description = 'L\'appel à LDAP s\'est bien passé.';
                    // error_log(basename(__FILE__) . stripAccents(" infos après la récup LDAP = " . print_r($info, true)));
                    unset($info['count']);
                    foreach($info as $index => $ldapElement)
                    {
                        foreach ($restriction as $nomRestriction)
                        {            
                            // error_log(basename(__FILE__) . stripAccents(" Construction du LDAP Result nomfiltre = $nomRestriction"));
                            if (isset($ldapElement[$nomRestriction][0]))
                            { 
                                $response->data[$index][$nomRestriction] = $ldapElement[$nomRestriction][0]; 
                            }
                        }
                    }
                }
                // error_log(basename(__FILE__) . stripAccents(" LDAP Result = " . print_r($response->data,true)));
            }

            @ldap_unbind($conLdap);
            return $response;            
        }
    }
?>