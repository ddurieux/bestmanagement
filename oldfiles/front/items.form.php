<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Page de configuration des droits par élément
// ----------------------------------------------------------------------

define('GLPI_ROOT', '../../..');
include_once (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("profile","r");
Plugin::load("bestmanagement",true);

Html::header($LANG["bestmanagement"]["config"][1], $_SERVER["PHP_SELF"],"config","plugins");

$prof = new PluginBestmanagementProfile();
$tab = $prof->updatePluginRights();

$element='';
// Partie traitements
if (isset($_POST["element"]))
   $element=$_POST["element"];

if (isset($_POST["delete"]))
{
	Session::checkRight("profile","w");

	$query_update = "UPDATE glpi_plugin_bestmanagement_profiles
					 SET $element = NULL";
	$DB->query($query_update);

}
else  if (isset($_POST["update"]))
{
	Session::checkRight("profile","w");

	foreach ($_POST as $key => $value)
		if (is_numeric($key))
			$prof->update(array("id"	=>$key,
								$element=>$value));
}
else if (isset($_GET["default"]))
	$prof->defValues();

// Fin traitements

echo "<div class='center'><form method='post' action=\"".$_SERVER["PHP_SELF"]."\">";
echo "<table class='tab_cadre'><tr><th colspan='2'><a href='config.form.php'>";
echo $LANG["bestmanagement"]["config"][0]."</a><br>&nbsp;<br>";
echo $LANG["bestmanagement"]["config"][1] . "</th></tr>\n";

echo "<tr class='tab_bg_1'><td>" . $LANG["bestmanagement"]["config"][11] . "&nbsp;: ";


echo "<select name='element'>";

foreach($tab as $item)
{
   echo "<option value='$item' ".($element=="$item"?"selected":"").">"
		.$LANG["bestmanagement"]["config"][$item]."</option>";
}
echo "</select>";
echo "<td><input type='submit' value='".$LANG["buttons"][2]."' class='submit' ></td></tr>";
echo "</table></form></div>";

$query_profiles = "SELECT id, profile
				   FROM glpi_profiles
				   ORDER BY name";
$result=$DB->query($query_profiles);

if ($element)
{
	echo "<div class='center'>";
	echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>";
	echo "<table class='tab_cadre'>\n";
	echo "<tr><th colspan='2'>".$LANG["bestmanagement"]["config"][10]." : </th></tr>\n";
			
	$query_plugprofiles = "SELECT id, profile, $element
						   FROM glpi_plugin_bestmanagement_profiles";

	$result=$DB->query($query_plugprofiles);
	while ($row=$DB->fetch_assoc($result))
	{
		echo "<tr class='tab_bg_1'><td>" . $row["profile"] . "&nbsp: </td><td>";
		Dropdown::showYesNo($row["id"],$row[$element]);
		echo "</td></tr>";
	}


	if (Session::haveRight("profile","w"))
	{
		echo "<tr class='tab_bg_1'><td colspan='2' class='center'>";
		echo "<input type='hidden' name='element' value='$element'>";
		echo "<input type='submit' name='update' value='".$LANG["buttons"][7]."' class='submit'>&nbsp;";
		echo "<input type='submit' name='delete' value='".$LANG["buttons"][6]."' class='submit'>";
		echo "</td></tr>";
	}
	
	echo "</table></form></div>";	
}

echo "<br><a href='items.form.php?default=0'>" . $LANG["bestmanagement"]["config"][12] . "</a>";
Html::footer();
?>
