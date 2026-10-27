<?php

    require_once ("../vendor/autoload.php");
    require_once ("../config/config.php");
    require_once ("../include/fonctions.php");

    $response = new ResponseClass();
    $response->status = ResponseStatus::STATUT_OK;

    $casUid = '';
    $token = '';
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    if (key_exists('authorization', $headers))
    {
        $authorizationstring = trim($headers['authorization']);
        if (preg_match('/^bearer /i', $authorizationstring))
        {
            $token = trim(preg_replace('/^bearer /i', '', $authorizationstring,1));
            if (!defined('PRIVATE_AUTHORIZATION_BEARER') or $token != PRIVATE_AUTHORIZATION_BEARER)
            {
                $token = '';
            }
        }
    }
    if ($token == '')
    {
        $clientServiceName = get_CASservicename();
        // Appel aux fonctions CAS pour récupérer l'utilisateur connecté
        \phpCAS::client(CAS_VERSION_2_0, CAS_SERVER, CAS_PORT, CAS_PATH, $clientServiceName, true);
        \phpCAS::setNoCasServerValidation();
        if (! \phpCAS::isAuthenticated()) 
        {
            $response->status = ResponseStatus::STATUT_KO;
            $response->description = 'Vous n\'êtes pas autorisé à accéder à ce service (Pas d\'authentification CAS).';
            $response->data = array();
        }
        else
        {
            $casUid = \phpCAS::getUser();
            if (!cas_user_check_access($casUid, CIAMGROUPACCESS_ADMIN))
            {
                $response->status = ResponseStatus::STATUT_KO;
                $response->description = 'Vous n\'êtes pas autorisé à accéder à ce service.';
                $response->data = array();
            }
        }
    }

    if ($response->status == ResponseStatus::STATUT_KO)
    {
        error_log(basename(__FILE__) . stripAccents(" " . $response->description));
    }
    else
    {
        if (count($_POST) == 0)
        {
            // Autre façon de récupérer les variables $_POST
            $_POST = (array)json_decode(file_get_contents('php://input'), true);
        }

        $methode = '';
        if (isset($_POST['methode']))
        {
            $methode = $_POST['methode'];
        }
        elseif (isset($_GET['methode']))
        {
            $methode = $_GET['methode'];
        }

        $sqlmode = '';
        if (isset($_POST['sqlmode']))
        {
            if (!is_null(SifacSqlMode::tryFrom($_POST['sqlmode'])))
            {
                $sqlmode = SifacSqlMode::tryFrom($_POST['sqlmode']);
            }
        }
        elseif (isset($_GET['sqlmode']))
        {
            if (!is_null(SifacSqlMode::tryFrom($_GET['sqlmode'])))
            {
                $sqlmode = SifacSqlMode::tryFrom($_GET['sqlmode']);
            }           
        }   

        switch ($methode)
        {
            case INTERNALWS_DELCIAMTOKEN :
                $ciamfunctions = new CiamFunctions();
                $response = $ciamfunctions->deleteCiamTokenFile();
                break;
            case INTERNALWS_DELSIFACTOKEN :
                $sifacfunctions = new SifacFunctions();
                $response = $sifacfunctions->deleteSifacTokenFile();
                break;
            case INTERNALWS_DELROLECACHE :
                $sifacfunctions = new SifacFunctions();
                $response = $sifacfunctions->deleteSifacRoleCache();
                break;
            case INTERNALWS_SIFACROLELISTE :
                if ($sqlmode == '')
                {
                    $response->status = ResponseStatus::STATUT_KO;
                    $response->description = "Le mode de requête SQL n'est pas défini ou est invalide.";
                    $response->data = array();        
                    error_log(basename(__FILE__) . stripAccents(" " . $response->description));
                }
                else
                {
                    $sifacfunctions = new SifacFunctions();
                    $response = $sifacfunctions->getSifacRolesList($sqlmode);
                }
                break;
            default :
                $response->status = ResponseStatus::STATUT_KO;
                $response->description = "La méthode est inconnue : " . $methode;
                $response->data = array();        
                error_log(basename(__FILE__) . stripAccents(" " . $response->description));
                break;
        }
    }

    if ($response->status == '')
    {
        $response->status = ResponseStatus::STATUT_KO;
        $response->description = "Une erreur interne au WS " . basename(__FILE__) . " s'est produite. La syntaxe de retour n'est pas correcte";
        $response->data = array();        
    }

    // headers for not caching the results
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    // headers to tell that result is JSON
    header('Content-type: application/json');

    // send the result now
    echo $response->jsonString();
