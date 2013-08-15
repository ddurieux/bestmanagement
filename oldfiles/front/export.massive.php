<?php
// ----------------------------------------------------------------------
// Original Author of file: 
// Purpose of file: Génération du PDF
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include_once (GLPI_ROOT . "/inc/includes.php");
include_once ("../inc/pdf.class.php");

if (isset($_SESSION["bestmanagement"]["TabID"]))
{
	$TabID = arrangeForHC($_SESSION["bestmanagement"]["TabID"]);
	unset($_SESSION["bestmanagement"]["TabID"]);
	
	$pdf = new PluginBestmanagementPDF();
	if ($pdf->generatePDF($TabID))
		echo "";
}
?>