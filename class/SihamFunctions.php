<?php
    require_once('ResponseClass.php');

    /**
     * Classe utilitaire d'accès à l'API SIHAM.
     *
     * Cette classe permet :
     * - la gestion et le rafraîchissement du token d’authentification SIHAM
     * - la recherche d’agents via l’API SIHAM
     * - la récupération des informations personnelles et des identifiants
     *
     * Toutes les opérations réseau retournent un objet {@see ResponseClass}.
     *
     */
    class SihamFunctions
    {
        /**
         * Retourne un token d'authentification valide pour l'API SIHAM.
         *
         * Le token est mis en cache localement dans un fichier avec une date
         * d'expiration. Un verrouillage exclusif est utilisé pour garantir
         * la cohérence lors d'accès concurrents.
         *
         * En cas d'erreur, une chaîne vide est retournée.
         *
         * @return string Token SIHAM valide ou chaîne vide en cas d’échec.
         */
        public function getSihamToken() :string
        {
            $token = '';
            $expirationDate = '';
            $expireDate = '1900-01-01 01:00:00';
            $tokenAccess = 0600; // rw- --- ---

            $paramsString = array();

            $newTokenNeeded = false;
            $sihamTokenFilename = TEMP_DIR . '/siham_token.lck';
            $path = dirname("$sihamTokenFilename");
            // Si le dossier n'existe pas, on le crée et on positionne les droits dessus
            if (!file_exists($path))
            {
                mkdir("$path",0700,true);
                chmod("$path", 0700);
            }
            // Si le fichier contenant le token existe, on positionne les droits dessus 
            if (file_exists($sihamTokenFilename))
            {
                chmod("$sihamTokenFilename", $tokenAccess);
            }
            // En C+, si le fichier n'existe pas il est créé => Donc pas d'erreur
            $fileDesc = fopen($sihamTokenFilename,'c+');
            // On demande un accès exclusif au fichier
            $lockFlag = flock($fileDesc,LOCK_EX);
            if ($lockFlag)
            {
                // $json = fread($fileDesc, filesize($sihamTokenFilename)+1);
                $json = stream_get_contents($fileDesc); 
                if (!$json)
                {
                    // Si la lecture retourne false => On regénère un token
                    error_log(basename(__FILE__) . stripAccents(" Impossible de lire le contenu du fichier token => On regénère le token"));
                    $newTokenNeeded = true;
                }
                else
                {
                    $arrayContent = json_decode($json,true);
                    if (!isset($arrayContent['token']) or !isset($arrayContent['expirationdate']))
                    {
                        // Mauvaises données lues => On regénère un token
                        error_log(basename(__FILE__) . stripAccents(" Mauvaise données lues dans le fichier token => On regénère le token"));
                        $newTokenNeeded = true;
                    }
                    else
                    {
                        $token = $arrayContent['token'];
                        $expireDate = $arrayContent['expirationdate'];
                        $currentDate = date('Y-m-d H:i:s', time());
                        if ($expireDate < $currentDate)
                        {
                            // Le token est expiré => On regénère le token
                            error_log(basename(__FILE__) . stripAccents(" Le token a expiré => On regénère le token"));
                            $newTokenNeeded = true;
                        }
                        else
                        {
                            $newTokenNeeded = false;
                        }
                    }
                }

                if ($newTokenNeeded)
                {
                    $paramsString["username"] = SIHAM_API_USER;
                    $paramsString["password"] = SIHAM_API_PASSWD;

                    $curl = curl_init();
                    $opts = [
                        CURLOPT_URL => SIHAMAPIURL . '/login', 
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => json_encode($paramsString), 
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYPEER => false,
                        // CURLOPT_PROXY => ETAB_PROXY_URL,
                        // CURLOPT_VERBOSE => true
                    ];
                    curl_setopt_array($curl, $opts);
                    curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

                    $header = array();
                    $header[] = 'accept: application/json';
                    $header[] = 'Content-Type: application/json';

                    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

                    $json = curl_exec($curl);
                    $response = (array)json_decode($json, true);

                    $error = curl_error ($curl);
                    $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    if ($httpCode !== 200 and $error=="")
                    {
                        $error = __FUNCTION__ . " : Code retour HTTP => $httpCode : " . HTTP_ERROR_MSG[$httpCode];
                    }
                    if ($error != "")
                    {
                        $error = __FUNCTION__ . " : Erreur Curl => " . $error;
                        error_log(basename(__FILE__) . stripAccents(" " . $error));
                    }
                    elseif (isset($response['data']))
                    {
                        $token = $response['data']['api_key'];
                        $validity = $response['data']['max_lifetime'];
                        $timestamp = time() + $validity - 600; // On prend une marge de 600 sec => 10min
                        // $timestamp = time() + 3600; // Problème de durée de vie du jeton => On le fixe à 1h <=> 3600sec
                        $expirationDate = date('Y-m-d H:i:s', $timestamp);
                    }
                    else
                    {
                        $error = __FUNCTION__ . " : Erreur => Pas de token !" . $json;
                        error_log(basename(__FILE__) . stripAccents(" " . $error));
                    }
                    if ($token != '' and $expirationDate != '')
                    {
                        // On remet le pointeur du fichier au début du fichier et on ecrit le nouveau token dans le fichier
                        if (rewind($fileDesc) === false
                         or fwrite($fileDesc, json_encode(array('expirationdate' => $expirationDate, 'token' => $token),JSON_PRETTY_PRINT)) === false
                         or fflush($fileDesc) === false
                        )
                        {
                            error_log(basename(__FILE__) . stripAccents(" Le token a été généré mais impossible de l'enregistrer dans le fichier."));
                            fclose($fileDesc);
                            return $token;
                        }
                        error_log(basename(__FILE__) . stripAccents(" Le token a été généré et correctement enregistré dans le fichier."));
                        // // On remet le pointeur du fichier au début du fichier
                        // fseek($fileDesc, 0);
                        // // On ecrit le nouveau token dans le fichier
                        // fwrite($fileDesc, json_encode(array('expirationdate' => $expirationDate, 'token' => $token)));
                        // fflush($fileDesc);   
                    }
                }
                fclose($fileDesc);
                // On repositionne les droits sur le fichier au cas où il ne serait pas bon
                chmod("$sihamTokenFilename", $tokenAccess);
            }
            else // On n'a pas réussi à avoir le lock
            {
                error_log(basename(__FILE__) . stripAccents(" Impossible de récupérer le lock sur le fichier."));
                fclose($fileDesc);       
            }
            //var_dump($token);
            return $token;

        }

        /**
         * Recherche des agents SIHAM à partir d'un filtre JSON.
         *
         * Le filtre est transmis tel quel à l'API SIHAM.
         * Chaque agent trouvé est enrichi par un appel complémentaire
         * afin de récupérer l'ensemble de ses informations personnelles.
         *
         * @param string $jsonFilter Filtre JSON encodé pour la recherche.
         * @return ResponseClass Données des agents indexées par matricule.
         */
        public function agentSearch(string $jsonFilter) :ResponseClass
        {
            $response = new ResponseClass();

            $token = $this->getSihamToken();
            if ($token == '')
            {
                $response->status = ResponseStatus::STATUT_KO;
                $response->description = 'Impossible de récupérer le token de SIHAM';
                return $response;
            }

            $curl = curl_init();
            // var_dump($jsonFilter);
            // var_dump(urlencode($jsonFilter));
            // $url = SIHAMAPIURL . '/1.0/agent-recherche?filter=' . urlencode($jsonFilter);
            // var_dump($url);
            $opts = [
                CURLOPT_URL => SIHAMAPIURL . '/1.0/agent-recherche?filter=' . urlencode($jsonFilter),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                // CURLOPT_PROXY => ETAB_PROXY_URL,
                // CURLOPT_VERBOSE => true
            ];
            curl_setopt_array($curl, $opts);
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            $header = array();
            $header[] = 'accept: application/json';
            $header[] = 'Authorization: '. $token;
            $header[] = "Cache-Control: no-cache, no-store, must-revalidate";
            $header[] = "Pragma: no-cache";
            $header[] = "Expires: 0";

            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

            $json = curl_exec($curl);
            $sihamResponse = (array)json_decode($json, true);

            $error = curl_error ($curl);
            $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($httpCode === 404)
            {
                $response->data = [];
                $response->status = ResponseStatus::STATUT_OK;
                $response->description = 'Aucune correspondance trouvée.';
                return $response;
            }
            elseif ((int) $httpCode !== 200 and $error=="")
            {
                $error = __FUNCTION__ . " : Code retour HTTP => $httpCode : " . HTTP_ERROR_MSG[$httpCode];
                // var_dump($json);
            }
            if ($error != "")
            {
                $error = __FUNCTION__ . " : Erreur Curl => " . $error;
                error_log(basename(__FILE__) . stripAccents(" " . $error));
            }
            elseif (isset($sihamResponse['data']))
            {
                foreach($sihamResponse['data'] as $sihamInfos)
                {
                    // var_dump($sihamInfos);
                    if (!isset($sihamInfos['identificationdudossier']['matricule']))
                    {
                        $response->status = ResponseStatus::STATUT_KO;
                        $response->description = 'La réponse de l\'API SIHAM agent-recherche n\'est pas correcte';
                        return $response;
                    }
                    $matricule = trim($sihamInfos['identificationdudossier']['matricule']);
                    // var_dump($matricule);
                    $responsePersonnalInfos = $this->getSihamPersonnalInfos($matricule);
                    if ($responsePersonnalInfos->status == ResponseStatus::STATUT_KO)
                    {
                        $response->status = ResponseStatus::STATUT_KO;
                        $response->description = $responsePersonnalInfos->description;
                        return $response;
                    }
                    // var_dump($responsePersonnalInfos->data);
                    $response->data[$matricule] = $responsePersonnalInfos->data;
                }
                $response->status = ResponseStatus::STATUT_OK;
                $response->description = '';
            }
            else
            {
                $error = __FUNCTION__ . " : Erreur => Réponse SIHAM mal formée.";
                error_log(basename(__FILE__) . stripAccents(" " . $error));
            }

            if ($error != '')
            {
                $response->data = [];
                $response->status = ResponseStatus::STATUT_KO;
                $response->description = $error;
            }
            // var_dump($response);
            return $response;
        }

        /**
         * Récupère les informations personnelles d'un agent SIHAM.
         *
         * Cette méthode interroge :
         * - le dossier personnel de l'agent
         * - les informations de compte utilisateur associées
         *
         * Les données retournées incluent, lorsque possible,
         * l'uid de l'agent.
         *
         * @param string $matricule Matricule SIHAM de l'agent.
         * @return ResponseClass Informations complètes de l'agent.
         */
        public function getSihamPersonnalInfos(string $matricule) :ResponseClass
        {
            $response = new ResponseClass();
            $matricule = trim($matricule);

            $token = $this->getSihamToken();
            if ($token == '')
            {
                $response->status = ResponseStatus::STATUT_KO;
                $response->description = 'Impossible de récupérer le token de SIHAM';
                return $response;
            }

            $curl = curl_init();
            $opts = [
                CURLOPT_URL => SIHAMAPIURL . '/1.0/dossierpersonnel/' . $matricule,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                // CURLOPT_PROXY => ETAB_PROXY_URL,
                // CURLOPT_VERBOSE => true
            ];
            curl_setopt_array($curl, $opts);
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            $header = array();
            $header[] = 'accept: application/json';
            $header[] = 'Authorization: '. $token;
            $header[] = "Cache-Control: no-cache, no-store, must-revalidate";
            $header[] = "Pragma: no-cache";
            $header[] = "Expires: 0";

            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

            $json = curl_exec($curl);
            $sihamResponse = (array)json_decode($json, true);

            $error = curl_error ($curl);
            $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($httpCode !== 200 and $error=="")
            {
                $error = __FUNCTION__ . " : Code retour HTTP => $httpCode : " . HTTP_ERROR_MSG[$httpCode];
            }
            if ($error != "")
            {
                $error = __FUNCTION__ . " : Erreur Curl => " . $error;
                error_log(basename(__FILE__) . stripAccents(" " . $error));
            }
            elseif (isset($sihamResponse['data'][0]) and count($sihamResponse['data'])==1)
            {
                $response->data = $sihamResponse['data'][0];
                $response->status = ResponseStatus::STATUT_OK;
                $response->description = '';
            }
            else
            {
                $error = __FUNCTION__ . " : Erreur => Réponse SIHAM mal formée ou plusieurs correspondances trouvées.";
                error_log(basename(__FILE__) . stripAccents(" " . $error));
            }

            if ($error == "")
            {
                $curl = curl_init();
                $opts = [
                    CURLOPT_URL => SIHAMAPIURL . '/1.0/agent/' . $response->data['id'] . '/utilisateurs',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    // CURLOPT_PROXY => ETAB_PROXY_URL,
                    // CURLOPT_VERBOSE => true
                ];
                curl_setopt_array($curl, $opts);
                curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

                $header = array();
                $header[] = 'accept: application/json';
                $header[] = 'Authorization: '. $token;
                $header[] = "Cache-Control: no-cache, no-store, must-revalidate";
                $header[] = "Pragma: no-cache";
                $header[] = "Expires: 0";

                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

                $json = curl_exec($curl);
                $sihamResponse = (array)json_decode($json, true);

                $error = curl_error ($curl);
                $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($httpCode !== 200 and $error=="")
                {
                    $error = __FUNCTION__ . " : Code retour HTTP => $httpCode : " . HTTP_ERROR_MSG[$httpCode];
                }
                if ($error != "")
                {
                    $error = __FUNCTION__ . " : Erreur Curl => " . $error;
                    error_log(basename(__FILE__) . stripAccents(" " . $error));
                }
                elseif (isset($sihamResponse['data'][0]['utilisateur']['login_id']) and count($sihamResponse['data'])==1)
                {
                    $response->data['uid'] = $sihamResponse['data'][0]['utilisateur']['login_id'];
                }
                else
                {
                    $error = __FUNCTION__ . " : Erreur => Réponse SIHAM - login - mal formée ou plusieurs correspondances trouvées.";
                    error_log(basename(__FILE__) . stripAccents(" " . $error));
                }
            }

            if ($error != '')
            {
                $response->data = [];
                $response->status = ResponseStatus::STATUT_KO;
                $response->description = $error;
            }
            return $response;
        }
    }
?>
