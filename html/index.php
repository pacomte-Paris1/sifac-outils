<?php

    require_once ("../vendor/autoload.php");
    require_once ("../config/config.php");
    require_once ("../include/fonctions.php");
    // Initialise la variable $casUid à partir du ticket CAS
    require_once ("../include/casconnexion.php");

    global $casUid;

    if (!cas_user_check_access($casUid, CIAMGROUPACCESS_CONSULT,true))
    {
        echo "Vous n'êtes pas autorisé à accéder à ce service.";
        exit();
    }
    // Pour mémoire : querySelector(.XXX) => class XXX / querySelector(#XXX) => id XXX 

    if (!defined('SQLMODE'))
    {
        define('SQLMODE',SifacSqlMode::QUERY_DIRECT);
    }

    $response = new ResponseClass();
    $sifacFunctions = new SifacFunctions();
    $sifacquery = new SifacQueryClass();
    $ciamfunctions = new CiamFunctions();

?>
<!doctype html>
<html>
<head>
<?php
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache"); // HTTP/1.0
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
?>
    <title>Accueil</title>
    <link rel="stylesheet" href="jquery-ui/jquery-ui.css?<?php echo filemtime('jquery-ui/jquery-ui.css') ?>" type="text/css" media="all" />
    <script src="jquery-ui/jquery.js"></script>
    <script src="jquery-ui/jquery-ui.js"></script>
<?php
    if (LDAP_MODE == LdapSearchClass::LDAP_MODE_WSGROUPS)
    {
?>
    <script src="<?php echo WSGROUPURL;?>/web-widget/kraaden.github.io-autocomplete.js"></script>
    <link rel="stylesheet" href="<?php echo WSGROUPURL;?>/web-widget/autocompleteUser.css" type="text/css" media="all" />
    <script src="<?php echo WSGROUPURL;?>/web-widget/autocompleteUser.js"></script>
<?php
    }
?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link rel="stylesheet" href="./css/application.css?dummy=<?php echo rand(1,9999999999); ?>" type="text/css" media="all" />
</head>
<body class="bodyhtml">
    <script src="javascript/functions.js?dummy=<?php echo rand(1,9999999999); ?>"></script>

<?php
    if (file_exists('../include/menu.php')) include('../include/menu.php');

    $casversion = phpCAS::getVersion();
    $errlog = "Version de CAS.php utilisée  : " . $casversion;
    error_log(basename(__FILE__) . " " . stripAccents($errlog));

    // var_dump($_POST);
?>
    <label class='fontsize18'>Page d'accueil de l'application SIFAC-OUTILS - <label class='bold'>Version <?php echo SIFAC_OUTILS_VERSION; ?></label><br>
    <br>
    <br>
    <br>
    En fonction de vos habilitations vous accédez à des fonctionnalités permettant de gérer/visualiser les informations des identités contenues dans le CIAM de l'AMUE, dans le cadre du projet SIFAC+<br>
    <br>
    Pour toutes informations complémentaires, veuillez vous rapprocher du service informatique de votre établissement.<br></label>
</body>
</html>
