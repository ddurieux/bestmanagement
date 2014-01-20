<?php

/*
   ------------------------------------------------------------------------
   Best Management
   Copyright (C) 2011-2013 by the Best Management Development Team.

   https://forge.indepnet.net/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Best Management project.

   Best Management is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Best Management is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Best Management. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Best Management
   @author    David Durieux
   @co-author 
   @copyright Copyright (c) 2011-2013 Best Management team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://forge.indepnet,net
   @since     2013
 
   ------------------------------------------------------------------------
 */

class PluginBestmanagementProfile extends CommonDBTM {
   
	function canCreate() {
		return Session::haveRight('profile', 'w');
	}

	function canView() {
		return Session::haveRight('profile', 'r');
	}

   
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;
      
      $itemtype = $item->getType();
      if ($itemtype == 'Profile') {
         return $LANG["bestmanagement"]["title"][0];
      }
      return '';
   }

   
   
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      
      $pbProfile = new self();
      if ($item->getID() > 0) {
         $pbProfile->showForm($item->getID());
      }

      return true;
   }


   
	function showForm($profiles_id, $options=array()) {
		global $LANG,$DB;

      $a_profiles = $this->find("`profiles_id`='".$profiles_id."'", "", 1);
      $id = 0;
		if (count($a_profiles) == 1) {
         $a_profile = current($a_profiles);
         $id = $a_profile['id'];
      }
      
		if ($id > 0) {
			$this->check($id,'r');
      } else {
			$this->check(-1,'w');
      }		

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo $LANG["bestmanagement"]["config"][20]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('recapglobal', $this->fields['recapglobal'], 1, 1, 0);
      echo "</td>";
      echo "<td>";
      echo $LANG["bestmanagement"]["config"][24]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('addpurchase', $this->fields['addpurchase'], 1, 0, 1);
      echo "</td>";
      echo "</tr>";      
     
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo $LANG["bestmanagement"]["config"][21]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('recapcontrat', $this->fields['recapcontrat'], 1, 1, 0);
      echo "</td>";
      echo "<td>";
      echo $LANG["bestmanagement"]["config"][22]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('historicalpurchase', $this->fields['historicalpurchase'], 1, 1, 0);
      echo "</td>";
      echo "</tr>";     
     
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo $LANG["bestmanagement"]["config"][25]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('facturationcontrat', $this->fields['facturationcontrat'], 1, 1, 1);
      echo "</td>";
      echo "<td>";
      echo $LANG["bestmanagement"]["config"][23]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('historicalperiode', $this->fields['historicalperiode'], 1, 1, 0);
      echo "</td>";
      echo "</tr>";     
     
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo $LANG["bestmanagement"]["config"][29]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('facturationticket', $this->fields['facturationticket'], 1, 1, 1);
      echo "</td>";
      echo "<td>";
      echo $LANG["bestmanagement"]["config"][27]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('mailing', $this->fields['mailing'], 1, 1, 1);
      echo "</td>";
      echo "</tr>";     
     
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo $LANG["bestmanagement"]["config"][28]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('linkticketcontrat', $this->fields['linkticketcontrat'], 1, 1, 1);
      echo "</td>";
      echo "<td>";
      echo $LANG["bestmanagement"]["config"][26]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('renewal', $this->fields['renewal'], 1, 1, 1);
      echo "</td>";
      echo "</tr>"; 
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo $LANG["bestmanagement"]["config"][30]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('modifcontentmailing', $this->fields['modifcontentmailing'], 1, 1, 1);
      echo "</td>";
      echo "<td>";
      
      echo "</td>";
      echo "<td>";
      
      echo "<input type='hidden' name='profiles_id' value='".$profiles_id."' />";
      echo "</td>";
      echo "</tr>"; 
      
      $this->showFormButtons($options);

      return true;
	}
   
   
   
   /**
    * Change profile (for used connected)
    *
    **/
   static function changeprofile() {
      if (isset($_SESSION['glpiactiveprofile']['id'])) {
         $pbProfile = new self();
         $a_rights = $pbProfile->find("`profiles_id` = '".$_SESSION['glpiactiveprofile']['id']."'");
         $i = 0;
         foreach ($a_rights as $data) {
            $i++;
            unset($data['id']);
            unset($data['profiles_id']);
            foreach ($data as $type => $right) {
               $_SESSION["glpi_plugin_bestmanagement_profile"][$type] = $right;
            }
         }
         if ($i == '0') {
            unset($_SESSION["glpi_plugin_bestmanagement_profile"]);
         }
      }
   }
   
   
   
   /**
    * test if user have right
    *
    * @param $p_moduleName Module name (directory)
    * @param $p_type Right type ('wol', 'agents'...)
    * @param $p_right Right (NULL, r, w)
    * 
    * @return boolean : true if right is ok
    **/
   static function haveRight($p_type, $p_right) {
      $matches=array(
            ""  => array("","r","w"), // ne doit pas arriver normalement
            "r" => array("r","w"),
            "w" => array("w"),
               );
      if (isset($_SESSION["glpi_plugin_bestmanagement_profile"][$p_type])
                && in_array($_SESSION["glpi_plugin_bestmanagement_profile"][$p_type], 
                            $matches[$p_right])) {
         return true;
      } else {
         return false;
      }
   }



   /**
    * Check right and display error if right not ok
    *
    * @param $p_moduleName Module name (directory)
    * @param $p_type Right type ('wol', 'agents'...)
    * @param $p_right Right (NULL, r, w)
    **/
   static function checkRight($p_type, $p_right) {
      global $CFG_GLPI;

      $pbProfile = new PluginBestmanagementProfile();
      if (!$pbProfile->haveRight($p_type, $p_right)) {
         // Gestion timeout session
         if (!isset ($_SESSION["glpiID"])) {
            Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
            exit ();
         }
         Html::displayRightError();
      }
   }
}
?>