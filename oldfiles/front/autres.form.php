<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Page de configuration des éléments comme la couleur,
//			le ratio, le thème du mail
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include_once (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("profile","w");
Plugin::load("bestmanagement",true);
Html::header($LANG["bestmanagement"]["config"][5], $_SERVER["PHP_SELF"],"config","plugins");

$table = "glpi_plugin_bestmanagement_config";
// Partie traitements
if (isset($_POST["update"]))
{
	unset ($_POST["update"]);
	$priority = (isset($_POST["colorpriority"])) ? 0 : 1;
	$ratio = ($_POST["ratio"] >= 0 && $_POST["ratio"] < 100) ? $_POST["ratio"] : 0;
	$color = $_POST["colormail"];
	
	$query_maj = "UPDATE $table
				  SET color_priority	= $priority,
					  ratiocontrat		= $ratio,
					  colormail			= '$color'";
	$DB->query($query_maj) or die ($query_maj);
}
// Fin traitements

echo "<div class='center'><form method='post' action=\"".$_SERVER["PHP_SELF"]."\">";
echo "<table class='tab_cadre'><tr><th colspan='2'><a href='config.form.php'>";
echo $LANG["bestmanagement"]["config"][0]."</a><br>&nbsp;<br>";
echo $LANG["bestmanagement"]["config"][5] . "</th></tr>\n";

echo "<input type='hidden' name='update'>";

// Couleur fond priorité
$checked_color = (isBgColor()) ? "checked" : "";
echo "<tr class='tab_bg_1'><td>" . $LANG["bestmanagement"]["config"][51]. "</td>";
echo "<td class='center'><input type='checkbox' $checked_color name='colorpriority'></td></tr>";
// Ratio
echo "<tr class='tab_bg_1'><td>" . $LANG["bestmanagement"]["config"][53]. "</td>";
echo "<td class='center'><input type='text' name='ratio'	size='2' maxlength='2' value='".getItem("ratiocontrat", $table)."'></td></tr>";

echo "<tr class='tab_bg_1'><td>" . $LANG["bestmanagement"]["config"][54]. "</td>";
$colors = array("blue", "red", "green");
echo "<td><select name='colormail'>";
foreach ($colors as $item)
	echo "<option value='$item' ".(getItem("colormail", $table) == "$item" ? "selected":"").">"
		. $LANG["bestmanagement"]["config"][$item]."</option>";
echo "</select></td>";

echo "</tr>";


echo "<tr class='tab_bg_1 center'><td colspan='2'><input type='submit' value='".$LANG["buttons"][2]."' class='submit' ></td></tr>";
echo "</table></form></div>";

Html::footer();
?>
