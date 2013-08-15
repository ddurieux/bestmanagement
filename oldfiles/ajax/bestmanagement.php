<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file:
// ----------------------------------------------------------------------

foreach (glob(GLPI_ROOT . "/plugins/bestmanagement/ajax/*.php") as $file)
	include_once ($file);

function SelectTab($id_contrat)
{
	$options="";
	// créé un nouvel objet permettant d'envoyer une réponse au côté client
	$objResponse = new xajaxResponse();
	// on selectionne le récapitulatif du contrat en fonction de son id
	$query="SELECT * FROM glpi_contracts WHERE id = $id_contrat";
	
	$result=$DB->query($query);
	while ($souscat = mysql_fetch_array($req))
		// on place toutes les sous-catégories dans des options valables pour la liste SELECT
		$options .= "<option value='" . $souscat['id'] . "'>" . $souscat["name"] . "</option>";

	// l'Ajax remplacera le innerHTML (html intérieur) de la liste_souscat pour y mettre $options
	$objResponse->addAssign("liste_souscat","innerHTML",$options);
	// envoie la réponse en XML
	return $objResponse->getXML();

} // SelectTab()


?>
