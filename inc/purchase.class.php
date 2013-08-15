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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginBestmanagementPurchase extends CommonDBTM {
   
   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName() {
      global $LANG;

      return $LANG['bestmanagement']['achat'][9];
   }



   function canCreate() {
      return true;
   }


   function canView() {
      return true;
   }

   
   
   function showForm(PluginBestmanagementContract $pbContract) {
      global $LANG;
      
      $this->check(-1,'w');
      $options = array();
      
      $this->showFormHeader($options);
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      if ($pbContract->fields['definition'] == "TaskCategory") {
         echo TaskCategory::getTypeName()."&nbsp;:";
      } else if ($pbContract->fields['definition'] == "ItilCategory") {
         echo ItilCategory::getTypeName()."&nbsp;:";
      } else if ($pbContract->fields['definition'] == "priority") {
         echo $LANG['joblist'][2]."&nbsp;:";
      }
      echo "</td>";
      echo "<td>";
      if ($pbContract->fields['definition'] == "TaskCategory") {
         Dropdown::show('TaskCategory', array('name' => 'definitions_id'));
      } else if ($pbContract->fields['definition'] == "ItilCategory") {
         Dropdown::show('ItilCategory', array('name' => 'definitions_id'));
      } else if ($pbContract->fields['definition'] == "priority") {
         Ticket::dropdownPriority('definitions_id');
      }
      echo "</td>";
      echo "<td>";
      echo $LANG['bestmanagement']['tabrecap'][16]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      echo Dropdown::showInteger("unit", 1, 1);
      echo "</td>";      
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo $LANG["bestmanagement"]["historical"][2]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('amendment');
      echo "</td>";
      echo "<td>";
      echo $LANG["bestmanagement"]["tabs"][6]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      $a_input = array(
          0 => $LANG["bestmanagement"]["facturation_contrat"][0],
          1 => $LANG["bestmanagement"]["facturation_contrat"][1],
          2 => $LANG["bestmanagement"]["facturation_contrat"][2]
      );
      Dropdown::showFromArray("invoice_state", $a_input, array('value'=>1));
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo $LANG['common'][25]."&nbsp;";
      echo "</td>";
      echo "<td colspan='3'>";
      echo "<input type='text' name='comment' size='40' maxlength='255' value='' />";
      echo "<input type='hidden' name='contracts_id' value='".$pbContract->fields['contracts_id']."' />";
      $contract = new Contract();
      $contract->getFromDB($pbContract->fields['contracts_id']);
      echo "<input type='hidden' name='begin_date' value='".$contract->fields['begin_date']."' />";
      echo "</td>";
      echo "</tr>";
      
      $this->showFormButtons($options);
   }
   
   

   /**
    * Display purchase history
    */
   function showHistory($contracts_id, $a_pbContracts) {
      global $DB, $CFG_GLPI, $LANG;
      
      $onlyone=false;
      $presentcontrat=null;
      
      echo "<table class='tab_cadre_fixe'>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='7'>";
      echo $LANG["bestmanagement"]["tabs"][2];
      echo "</th>";
      echo "</tr>";
      
      $a_purchases = $this->find("`contracts_id`='".$contracts_id."'");
      
      if (count($a_purchases) == 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='7'>";
         echo $LANG["bestmanagement"]["msg"][16];
         echo "</td>";
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<th>";
         echo $LANG['common'][27];
         echo "</th>";
         echo "<th>";
         echo $LANG["bestmanagement"]["historical"][0];
         echo "</th>";
         echo "<th>";         
         if ($a_pbContracts['definition'] == "TaskCategory") {
            echo TaskCategory::getTypeName();
         } else if ($a_pbContracts['definition'] == "ItilCategory") {
            echo ItilCategory::getTypeName();
         } else if ($a_pbContracts['definition'] == "priority") {
            echo $LANG['joblist'][2];
         }
         echo "</th>";
         echo "<th>";
         echo $LANG['bestmanagement']['tabrecap'][16];
         echo "</th>";
         echo "<th>";
         echo $LANG['common'][25];
         echo "</th>";
         echo "<th>";
         echo $LANG["bestmanagement"]["facturation_contrat"][3];         
         echo "</th>";
         echo "<th>";
         echo "</th>";
         echo "</tr>";
         
         foreach ($a_purchases as $a_purchase) {
            echo "<tr class='tab_bg_3'>";
            echo "<td>";
            echo Html::convDate($a_purchase['date_save']);
            echo "</td>";
            echo "<td>";
            if ($a_purchase['amendment'] == 1) {
               echo $LANG["bestmanagement"]["historical"][2]." - ";
            }
            switch ($a_purchase["invoice_state"]) {
               
               case 0:
                  echo $LANG["bestmanagement"]["facturation_contrat"][0];
                  break;
               
               case 1:
                  echo $LANG["bestmanagement"]["facturation_contrat"][1];
                  break;
               
               case 2:
                  echo $LANG["bestmanagement"]["facturation_contrat"][2];
                  break;
            }
            echo "</td>";
            echo "<td>";
            if ($a_pbContracts['definition'] == "priority") {
               echo Ticket::getPriorityName($a_purchase['definitions_id']);
            } else {
               echo Dropdown::getDropdownName(getTableForItemType($a_pbContracts['definition']), 
                                              $a_purchase['definitions_id']);
            }
            echo "</td>";
            echo "<td>";
            if ($a_pbContracts['unit_type'] == "hour") {
               echo PluginBestmanagementToolbox::displayHours($a_purchase['unit']);
            } else {
               echo $a_purchase['unit'];
            }
            echo "</td>"; 
            echo "<td>";
            echo $a_purchase['comment'];
            echo "</td>"; 
            echo "<td>";
            echo $a_purchase['invoice_number'];
            echo "</td>";            
            echo "<td>";
            if (countElementsInTable("glpi_plugin_bestmanagement_tickets_contracts", 
                                     "`contracts_id`='".$contracts_id."'") == 0) {
               echo "<form method='post' action=\"".$CFG_GLPI['root_doc'] . "/plugins/bestmanagement/front/purchase.form.php\">";
               echo "<input type='hidden' name='id' value='".$a_purchase['id']."'/>";
               echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][22]."\"
                         class='submit' ".Html::addConfirmationOnAction($LANG['common'][50]).">";
               Html::closeForm();
            }
            echo "</td>";            
            echo "</tr>";
         }
      }      
      echo "</table>";
   }

}

?>