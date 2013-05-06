<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file:
// ----------------------------------------------------------------------

//On sort en cas de paramètre manquant ou invalide
if(empty($_GET["id"]) or empty($_GET["type"]) or empty($_GET["champ"]) or empty($_GET["valeur"])
   or !is_numeric($_GET["id"])
   or !in_array(
   		$_GET["champ"],
        array('nom', 'prenom', 'adresse', 'code_postal', 'ville', 'enfants', 'email')
        ))
{
    echo "no get";
}
else
{
	global $DB;
	$DB_TABLE_NAME = "inlinemod";
	//Construction de la requête en fonction du type de valeur
	switch($_GET["type"])
	{
		case 'texte':
		case 'texte-multi':
			$sql  = 'UPDATE `'.$DB_TABLE_NAME;
			$sql .= '` SET ' . mysql_real_escape_string($_GET["champ"]) . '="';
			$sql .= mysql_real_escape_string($_GET["valeur"]) . '" WHERE id=' . intval($_GET["id"]);
			break;

		case 'nombre':
			$sql  = 'UPDATE `'.$DB_TABLE_NAME;
			$sql .= '` SET ' . mysql_real_escape_string($_GET["champ"]) . '=' . intval($_GET["valeur"]);
			$sql .= ' WHERE id=' . intval($_GET["id"]);
			break;

		default:
			$sql="";
	}
		//Exécution de la requête
		$DB->query($sql)or die(mysql_error());
}
?>