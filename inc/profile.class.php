<?php

/*
   ------------------------------------------------------------------------
   Supportcontract
   Copyright (C) 2014-2014 by the Supportcontract Development Team.

   https://github.com/ddurieux/bestmanagement   
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Supportcontract project.

   Supportcontract is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Supportcontract is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Supportcontract. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Supportcontract
   @author    David Durieux, Nicolas Mercier
   @co-author
   @copyright Copyright (c) 2014-2014 Supportcontract team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://github.com/ddurieux/bestmanagement
   @since     2014

   ------------------------------------------------------------------------
 */

class PluginSupportcontractProfile extends CommonDBTM {
   
	static function canCreate() {
		return Session::haveRight('profile', 'w');
	}

   
   
	static function canView() {
		return Session::haveRight('profile', 'r');
	}

   
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      
      $itemtype = $item->getType();
      if ($itemtype == 'Profile') {
         return __('Support contract', 'supportcontract');
      }
      return '';
   }

   
   
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      
      $psProfile = new self();
      if ($item->getID() > 0) {
         $psProfile->showForm($item->getID());
      }
      return true;
   }


   
	function showForm($profiles_id, $options=array()) {
		global $DB;

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
      echo __('Summary of all contracts', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('recapglobal', $this->fields['recapglobal'], 1, 1, 0);
      echo "</td>";
      echo "<td>";
      echo __('Add a purchase', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('addpurchase', $this->fields['addpurchase'], 1, 0, 1);
      echo "</td>";
      echo "</tr>";      
     
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Contract summary', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('recapcontrat', $this->fields['recapcontrat'], 1, 1, 0);
      echo "</td>";
      echo "<td>";
      echo __('Purchase history', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('historicalpurchase', $this->fields['historicalpurchase'], 1, 1, 0);
      echo "</td>";
      echo "</tr>";     
     
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Contract invoice', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('facturationcontrat', $this->fields['facturationcontrat'], 1, 1, 1);
      echo "</td>";
      echo "<td>";
      echo __('History by period', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('historicalperiode', $this->fields['historicalperiode'], 1, 1, 0);
      echo "</td>";
      echo "</tr>";     
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Ticket invoice', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('facturationticket', $this->fields['facturationticket'], 1, 1, 1);
      echo "</td>";
      echo "<td>";
      echo __('Mailling', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('mailing', $this->fields['mailing'], 1, 1, 1);
      echo "</td>";
      echo "</tr>";     
     
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Affect a ticket to a contract', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('linkticketcontrat', $this->fields['linkticketcontrat'], 1, 1, 1);
      echo "</td>";
      echo "<td>";
      echo __('Reconduction', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Profile::dropdownNoneReadWrite('renewal', $this->fields['renewal'], 1, 1, 1);
      echo "</td>";
      echo "</tr>"; 
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Edit mailing', 'supportcontract')."&nbsp;:";
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
         $psProfile = new self();
         $a_rights = $psProfile->find("`profiles_id` = '".$_SESSION['glpiactiveprofile']['id']."'");
         $i = 0;
         foreach ($a_rights as $data) {
            $i++;
            unset($data['id']);
            unset($data['profiles_id']);
            foreach ($data as $type => $right) {
               $_SESSION["glpi_plugin_supportcontract_profile"][$type] = $right;
            }
         }
         if ($i == '0') {
            unset($_SESSION["glpi_plugin_supportcontract_profile"]);
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
      if (isset($_SESSION["glpi_plugin_supportcontract_profile"][$p_type])
                && in_array($_SESSION["glpi_plugin_supportcontract_profile"][$p_type], 
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

      $psProfile = new PluginSupportcontractProfile();
      if (!$psProfile->haveRight($p_type, $p_right)) {
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