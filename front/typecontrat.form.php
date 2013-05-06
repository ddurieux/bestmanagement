<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Page de configuration des éléments comme la couleur,
//			le ratio, le thème du mail
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include_once (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("profile","r");
Plugin::load('bestmanagement',true);
Html::header($LANG["bestmanagement"]["config"][2], $_SERVER["PHP_SELF"],"config","plugins");

$table = "glpi_plugin_bestmanagement_typecontrat";
// Partie traitements
if (isset($_POST["update"]))
	updateTypeIllim($table, $_POST);
// Fin traitements

// Quels tableaux récapitulatifs envoyer ?
echo "<div class='center'><form method='post' action=\"".$_SERVER["PHP_SELF"]."\">";
echo "<table class='tab_cadre'><tr><th colspan='2'><a href='config.form.php'>";
echo $LANG["bestmanagement"]["config"][0]."</a><br>&nbsp;<br>";
echo $LANG["bestmanagement"]["config"][6] . "</th></tr>";

echo "<input type='hidden' name='update'>";

$all_types = "SELECT CT.id, name, illimite
			  FROM glpi_contracttypes CT
				LEFT JOIN $table plug_CT
					ON CT.ID = plug_CT.id";

if($resultat = $DB->query($all_types))
	if($DB->numrows($resultat) > 0)
		while ($row = $DB->fetch_assoc($resultat))
		{	
			$checked = (isTypeIllim($row["id"])) ? "checked" : "";

			echo "<tr class='tab_bg_1'><td>" . $row["name"]. "</td>";
			echo "<td class='center'><input type='checkbox' $checked name='" . $row["id"] . "'></td></tr>";
		} // while

echo "<tr class='tab_bg_1 center'><td colspan='2'><input type='submit' value='".$LANG["buttons"][2]."' class='submit' ></td></tr>";
echo "</table></form></div>";

Html::footer();
?>
