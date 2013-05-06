<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Traitements et mise à jour des droits selon les
//			profils. Onglet Administration => Profils
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..'); 
include_once (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("profile","w");

$prof = new PluginBestmanagementProfile();

//Save profile
if (isset ($_POST["update_user_profile"])) {
   $prof->update($_POST);	
}
Html::redirect($_SERVER['HTTP_REFERER']);

?>