<?php
    require_once ("../config/config.php");
    require_once ("../include/fonctions.php");

    $clientServiceName = get_CASservicename();
    // Appel aux fonctions CAS pour récupérer l'utilisateur connecté
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    \phpCAS::client(CAS_VERSION_2_0, CAS_SERVER, CAS_PORT, CAS_PATH, $clientServiceName, true);
    \phpCAS::setNoCasServerValidation();
    \phpCAS::handleLogoutRequests(false);
    \phpCAS::forceAuthentication();

    $casUid = \phpCAS::getUser();
    // echo "uid= $uid <br>";


?>