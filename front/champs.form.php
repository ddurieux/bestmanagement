<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Page de configuration des champs de la configuration
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include_once (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("profile","r");
Plugin::load('bestmanagement',true);
Html::header($LANG["bestmanagement"]["config"][2], $_SERVER["PHP_SELF"],"config","plugins");

$table = "glpi_plugin_bestmanagement_config";
// Partie traitements
if (isset($_POST["update"]))
	updateTable($table, $_POST);
// Fin traitements

// Quels tableaux récapitulatifs envoyer ?
echo "<div class='center'><form method='post' action=\"".$_SERVER["PHP_SELF"]."\">";
echo "<table class='tab_cadre'><tr><th colspan='2'><a href='config.form.php'>";
echo $LANG["bestmanagement"]["config"][0]."</a><br>&nbsp;<br>";
echo $LANG["bestmanagement"]["config"][2] . "</th></tr>";

echo "<input type='hidden' name='update'>";
for ($i = 0 ; $i <= 6 ; $i++)
{
	$name = getItemName($i, $table);
	$checked = (isItemChecked($i, $table)) ? "checked" : "";

	echo "<tr class='tab_bg_1'><td>" . $LANG["bestmanagement"]["config"][$name]. "</td>";
	echo "<td class='center'><input type='checkbox' $checked name='$i'></td></tr>";
}
echo "<tr class='tab_bg_1 center'><td colspan='2'><input type='submit' value='".$LANG["buttons"][2]."' class='submit' ></td></tr>";
echo "</table></form></div>";

Html::footer();
?>
