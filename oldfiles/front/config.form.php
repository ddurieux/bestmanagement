<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Page de configuration du plugin
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT'))
	define('GLPI_ROOT', '../../..');


include_once (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("config","w");
// To be available when plugin in not activated
Plugin::load("bestmanagement");
Html::header($LANG['common'][12],$_SERVER['PHP_SELF'],"config","plugins");

echo "<div class='center'>";
echo "<table class='tab_cadre'>";
echo "<tr><th>".$LANG['bestmanagement']['config'][0]."</th></tr>";

$page = array("", "items", "champs", "mailing", "pdf", "autres", "typecontrat");

for($i = 1 ; $i <= 6 ; $i++)
{
	echo "<tr class='tab_bg_1 center'><td>";
	echo "<a href='" . $page[$i] . ".form.php'>" . $LANG["bestmanagement"]["config"][$i] . "</a>";
	echo "</td/></tr>";
}
echo "<tr class='tab_bg_1 center'><td>";
echo "<a href='http://www.glpi-project.org/wiki/doku.php?id=fr:plugins:bestmanagement_use'>";
echo $LANG["bestmanagement"]["config"][61] . "</a></td></tr>";

echo "</table></div>";

Html::footer();
?>
