<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Affichage du tableau récapitulatif du contrat, dans
//			le formulaire de création de ticket
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include_once(GLPI_ROOT . "/inc/includes.php");
include_once(GLPI_ROOT . "/plugins/bestmanagement/inc/contrat.class.php");

if(isset($_POST["Fact"]) && $_POST["Fact"] == 0 && isset($_POST["idContratFactureContrat"]))
{
	$mon_contrat = new PluginBestmanagementContrat($_POST["idContratFactureContrat"]);
	$mon_contrat->inputFacturation();
}
else if (isset($_POST["idContrat"]) && $_POST["idContrat"] != 0)
{
	echo "<td colspan='5'>";
	$mon_contrat = new PluginBestmanagementContrat($_POST["idContrat"]);
	if (count($mon_contrat->infoCompteur()) == 2)
		echo $mon_contrat->showTabRecap();
	echo "</td>";	
}
else if (isset($_POST["idContrat"]))	// Hors contrat
{
	echo "<td></td><td></td>";
	echo "<td id='api'>";
	echo "</td>";
	echo "<td></td>";
}
?>