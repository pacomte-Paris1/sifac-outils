<?php
    /**
     * Webservice retournant les informations de structures financières SIFAC
     * pour une liste de CFI.
     *
     * Méthode HTTP : GET
     *
     * Paramètres :
     * - listecfi (string) : liste de codes CFI séparés par des virgules
     *
     * Authentification :
     * - soit via un header HTTP Authorization : Bearer <token>
     * - soit via authentification CAS avec vérification des droits utilisateur
     *
     * Exemple d'appel :
     * GET /ws/sifacFinancialStructure.php?listecfi=CFI001,CFI002
     *
     * Réponse JSON :
     * {
     *   "status": "OK|KO",
     *   "description": "message",
     *   "data": [...]
     * }
     *
     * La propriété data contient les informations SIFAC associées
     * aux structures financières demandées.
     *
     * @return void
     */
    
    require_once ("../vendor/autoload.php");
    require_once ("../config/config.php");
    require_once ("../include/fonctions.php");

    if (!defined('SQLMODE'))
    {
        define('SQLMODE',SifacSqlMode::QUERY_DIRECT);
    }

    $sqlmode = SQLMODE;
    if (isset($_GET['sqlmode']))
    {
        if (!is_null(SifacSqlMode::tryFrom($_GET['sqlmode'])))
        {
            $sqlmode = SifacSqlMode::tryFrom($_GET['sqlmode']);
        }           
    }

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

    if ($response->status == ResponseStatus::STATUT_KO )
    {
        // Ne rien faire => Sortir du WS en retournant l'erreur
    }
    else
    {
        $cfilist = [];
        if (isset($_GET['listecfi']))
        {
            $cfilist = explode(',',$_GET['listecfi']);
        }

        $sifacfunctions = new SifacFunctions();
        $response = $sifacfunctions->getSifacFinancialStructures($cfilist, $sqlmode);
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
