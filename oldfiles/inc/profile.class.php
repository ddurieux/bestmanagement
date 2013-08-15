<?php
// ----------------------------------------------------------------------
// Original Author of file: Nicolas Mercier
// Purpose of file: Classe Profile permettant la gestion des droits
// ----------------------------------------------------------------------

class PluginBestmanagementProfile extends CommonDBTM
{
	function canCreate()
	{
		return Session::haveRight('profile', 'w');
	} // canCreate()

	function canView()
	{
		return Session::haveRight('profile', 'r');
	} // canView()

	static function cleanProfiles(Profile $prof)
	{
		$plugprof = new self();
		$plugprof->delete(array('id'=>$prof->getField("id")));
	} // cleanProfiles()


	function showForm($id, $options=array())
	{
		global $LANG,$DB;

		$target = $this->getFormURL();
		if (isset($options["target"]))
			$target = $options["target"];
		
		if ($id > 0)
			$this->check($id,'r');
		else
			$this->check(-1,'w');
		

		$canedit=$this->can($id,'w');

		echo "<form action='".$target."' method='post'>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='4' class='center b'>";
		echo $LANG["bestmanagement"]["config"][10]." ".$this->fields["profile"]."</th></tr>";
		
		foreach($this->allItems() as $key => $plug)
		{
			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG["bestmanagement"]["config"][$plug]." :</td><td>";
			Dropdown::showYesNo($plug,(isset($this->fields[$plug])?$this->fields[$plug]:''));
			echo "</td></tr>";
		}

		if ($canedit)
		{
			echo "<tr class='tab_bg_1'>";
			echo "<td class='center' colspan='4'>";
			echo "<input type='hidden' name='id' value=$id>";
			echo "<input type='submit' name='update_user_profile' value='".$LANG["buttons"][7]."' class='submit'>";
			echo "</td></tr>";
		}
		echo "</table></form>";
	} // showForm()

	function updateRights()
	{
		global $DB;

		// Add missing profiles
		$query_profiles = "INSERT INTO ".$this->getTable()."
						   (id, profile)
						   (SELECT id, name
						    FROM glpi_profiles
							WHERE id NOT IN (SELECT id
											 FROM ".$this->getTable()."))";
		$DB->query($query_profiles) or die("error $query_profiles");
		
		$query_delete = "DELETE FROM ".$this->getTable()."
						 WHERE id NOT IN (SELECT id
										  FROM glpi_profiles)";
		$DB->query($query_delete) or die("error $query_delete");
		
	} // updateRights()

	static function changeprofile()
	{
		$prof = new self();
		if ($prof->getFromDB($_SESSION["glpiactiveprofile"]["id"]))
			$_SESSION["glpi_plugin_bestmanagement_profile"]=$prof->fields;
		else
			unset($_SESSION["glpi_plugin_bestmanagement_profile"]);

	} // changeprofile()

	/**
	* Create access rights for an user
	* @param id the user id
	*/
	function createaccess($id)
	{
		global $DB;

		$Profile = new Profile();
		$Profile->GetfromDB($id);
		$name = $Profile->fields["profil"];

		$query = "INSERT INTO ".$this-getTable()."
				  (id, profile)
				  VALUES ($id, '$name');";
		$DB->query($query);
	} // createaccess()

	/**
	* Look for all the plugins, and update rights if necessary
	*/
	function updatePluginRights()
	{
		$this->getEmpty();
		$tab = $this->allItems();
		$this->updateRights();

		return $tab;
	} // updatePluginRights()
	
	/**
	* Search for items
	*
	* @return tab : an array which contains all the items found (name => plugin)
	*/
	static function allItems()
	{
		global $LANG, $DB;
		
		$query_plugprofiles = "SELECT recapglobal, recapcontrat, historicalpurchase, historicalperiode,
									  addpurchase, facturationcontrat, mailing, linkticketcontrat,
									  facturationticket, modifcontentmailing, renewal 
							   FROM glpi_plugin_bestmanagement_profiles";
		
		$res=$DB->query($query_plugprofiles) or die ($query_plugprofiles);
		
		$nbcols = $DB->num_fields($res);
		$tab = array();
		
		for ($i = 0 ; $i < $nbcols ; $i++)
			$tab[] = $DB->field_name($res, $i);

		return $tab;
	} // allItems()
	
	function defValues()
	{
		global $DB;	
		
		$query_prof = "SELECT id, profile
					   FROM glpi_plugin_bestmanagement_profiles";

		if($resultat = $DB->query($query_prof))
			if($DB->numrows($resultat) > 0)
				while ($row = $DB->fetch_assoc($resultat))
				{
					$pre_query = "UPDATE glpi_plugin_bestmanagement_profiles SET ";
					
					if (in_array($row["profile"], array("normal", "admin", "super-admin")))
					{
						$query_nasa = "recapglobal = '1',
									   recapcontrat = '1',
									   historicalpurchase = '1',
									   historicalperiode = '1'
									   WHERE profile IN ('normal', 'admin', 'super-admin')";
						$DB->query($pre_query . $query_nasa) or die("erreur de la requete $query_nasa ". $DB->error());
					}
					if (in_array($row["profile"], array("admin", "super-admin")))
					{
						$query_asa = "addpurchase = '1',
									  facturationcontrat = '1',
									  renewal = '1',
									  mailing = '1',
									  linkticketcontrat = '1',
									  modifcontentmailing = '1'
									  WHERE profile IN ('admin', 'super-admin')";
						$DB->query($pre_query . $query_asa) or die("erreur de la requete $query_asa ". $DB->error());
					}
					{
						$query_sa = "facturationticket = '1'
									 WHERE profile = 'super-admin'";
						$DB->query($pre_query . $query_sa) or die("erreur de la requete $query_sa ". $DB->error());
					}
					
				} // while
	} // defValues()
}
?>