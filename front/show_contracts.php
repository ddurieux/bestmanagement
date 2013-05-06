<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Affichage du listing des contrats. Varie selon la 
//			valeur du <select>
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include_once (GLPI_ROOT . "/inc/includes.php");

echo "<div name='tabcontrat'>";

switch($_POST["allContrats"])
{
  case "2" :	// terminés
	$AND_CONDITION = "AND CURDATE() > DATE_ADD(begin_date, INTERVAL duration MONTH)";
	break;
  case "3" :	// en cours
	$AND_CONDITION = "AND CURDATE() <= DATE_ADD(begin_date, INTERVAL duration MONTH)";
	break;
  case "4" :	// semaine
	$AND_CONDITION = "AND CURDATE() <= DATE_ADD(begin_date, INTERVAL duration MONTH) AND
					DATE_ADD(CURDATE(), INTERVAL 7 DAY) >= DATE_ADD(begin_date, INTERVAL duration MONTH)";
	break;
  case "5" :	// mois
		$AND_CONDITION = "AND CURDATE() <= DATE_ADD(begin_date, INTERVAL duration MONTH) AND
					DATE_ADD(CURDATE(), INTERVAL 1 MONTH) >= DATE_ADD(begin_date, INTERVAL duration MONTH)";
	break;
  default :	// tous
	$AND_CONDITION = "";
}

$query = "";	// aucun contrat
if ($_POST["allContrats"]) // il y a un contrat
	$query = "SELECT id
			  FROM glpi_contracts
			  WHERE begin_date IS NOT NULL AND duration IS NOT NULL AND is_deleted = 0
				$AND_CONDITION " . getEntitiesRestrictRequest("AND","glpi_contracts","entities_id","",false) . "
			  ORDER BY DATE_ADD(begin_date, INTERVAL duration MONTH)";	

echo "</div>";

global $DB;

$all_contrats = array();
		  
if($res = $DB->query($query))
{
	if($DB->numrows($res) > 0)
		while ($row = $DB->fetch_assoc($res))
			$all_contrats[] = $row["id"];
	else
		$emptytab = true;	// pas de tableau
}
else
	$emptytab = true;	// pas de tableau

if (isset($emptytab))
	echo "<div class='x-tab-panel-header'>" . $LANG["bestmanagement"]["msg"][26] . "</div>";	// pas de contrat, pas de tableau
else
{
	showAllContracts($all_contrats);
} // fin du tableau
?>
