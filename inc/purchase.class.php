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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginSupportcontractPurchase extends CommonDBTM {
   
   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName($nb=0) {
      return __('Purchase', 'supportcontract');
   }



   static function canCreate() {
      return true;
   }

   

   static function canView() {
      return true;
   }

   
   
   function showForm(PluginSupportcontractContract $psContract) {
      
      $this->check(-1,'w');
      $options = array();
      
      $this->showFormHeader($options);
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      if ($psContract->fields['definition'] == "TaskCategory") {
         echo TaskCategory::getTypeName()."&nbsp;:";
      } else if ($psContract->fields['definition'] == "ItilCategory") {
         echo ItilCategory::getTypeName()."&nbsp;:";
      } else if ($psContract->fields['definition'] == "priority") {
         echo __('Priority')."&nbsp;:";
      }
      echo "</td>";
      echo "<td>";
      if ($psContract->fields['definition'] == "TaskCategory") {
         Dropdown::show('TaskCategory', array('name' => 'definitions_id'));
      } else if ($psContract->fields['definition'] == "ItilCategory") {
         Dropdown::show('ItilCategory', array('name' => 'definitions_id'));
      } else if ($psContract->fields['definition'] == "priority") {
         Ticket::dropdownPriority('definitions_id');
      }
      echo "</td>";
      echo "<td>";
      echo __('Unit bought', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      echo Dropdown::showInteger("unit", 1, 1);
      echo "</td>";      
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Avenant', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo('amendment');
      echo "</td>";
      echo "<td>";
      echo __('Purchase log', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      $a_input = array(
          0 => __('Invoiced', 'supportcontract'),
          1 => __('To be invoiced', 'supportcontract'),
          2 => __('Not be invoiced', 'supportcontract')
      );
      Dropdown::showFromArray("invoice_state", $a_input, array('value'=>1));
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Comment')."&nbsp;";
      echo "</td>";
      echo "<td colspan='3'>";
      echo "<input type='text' name='comment' size='40' maxlength='255' value='' />";
      echo "<input type='hidden' name='contracts_id' value='".$psContract->fields['contracts_id']."' />";
      $contract = new Contract();
      $contract->getFromDB($psContract->fields['contracts_id']);
      echo "<input type='hidden' name='begin_date' value='".$contract->fields['begin_date']."' />";
      
      $psContract_Period = new PluginSupportcontractContract_Period();
      $a_period = $psContract_Period->getCurrentPeriod($psContract->fields['contracts_id']);
      echo "<input type='hidden' name='plugin_supportcontract_contracts_periods_id' 
         value='".$a_period['id']."' />";
      
      echo "</td>";
      echo "</tr>";
      
      $this->showFormButtons($options);
   }
   
   

   /**
    * Display purchase history
    */
   function showHistory($contracts_id, $a_psContracts, $periods_id=0) {
      global $DB, $CFG_GLPI;

      $psContract_Period = new PluginSupportcontractContract_Period();
      if ($periods_id > 0) {
         $psContract_Period->getFromDB($periods_id);
      }
      
      $onlyone=false;
      $presentcontrat=null;
      
      echo "<table class='tab_cadre_fixe'>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='7'>";
      echo __('purchase log', 'supportcontract');
      if ($periods_id > 0) {
         echo " ".Html::convDate($psContract_Period->fields['begin'])." - ".
              Html::convDate($psContract_Period->fields['end']);
      }
      echo "</th>";
      echo "</tr>";
      
      if ($periods_id > 0) {
         $a_period = $psContract_Period->fields;
      } else {
         $a_period = $psContract_Period->getCurrentPeriod($contracts_id);
      }
      
      $a_purchases = $this->find("`contracts_id`='".$contracts_id."'
         AND `plugin_supportcontract_contracts_periods_id`='".$a_period['id']."'");
     
      if (count($a_purchases) == 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='7'>";
         echo __('Not have yet purchase', 'supportcontract');
         echo "</td>";
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<th>";
         echo __('Date');
         echo "</th>";
         echo "<th>";
         echo __('Details', 'supportcontract');
         echo "</th>";
         echo "<th>";         
         if ($a_psContracts['definition'] == "TaskCategory") {
            echo TaskCategory::getTypeName();
         } else if ($a_psContracts['definition'] == "ItilCategory") {
            echo ItilCategory::getTypeName();
         } else if ($a_psContracts['definition'] == "priority") {
            echo __('Priority', 'supportcontract');
         }
         echo "</th>";
         echo "<th>";
         echo __('Purchased units', 'supportcontract');
         echo "</th>";
         echo "<th>";
         echo __('Comment');
         echo "</th>";
         echo "<th>";
         echo __('Invoice number', 'supportcontract');         
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
               echo __('Avenant', 'supportcontract')." - ";
            }
            switch ($a_purchase["invoice_state"]) {
               
               case 0:
                  echo __('Have invoice', 'supportcontract');
                  break;
               
               case 1:
                  echo __('To be invoiced', 'supportcontract');
                  break;
               
               case 2:
                  echo __('Cannot be invoiced', 'supportcontract');
                  break;
            }
            echo "</td>";
            echo "<td>";
            if ($a_psContracts['definition'] == "priority") {
               echo Ticket::getPriorityName($a_purchase['definitions_id']);
            } else {
               echo Dropdown::getDropdownName(getTableForItemType($a_psContracts['definition']), 
                                              $a_purchase['definitions_id']);
            }
            echo "</td>";
            echo "<td>";
            if ($a_psContracts['unit_type'] == "hour") {
               echo PluginSupportcontractToolbox::displayHours($a_purchase['unit']);
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
            if (countElementsInTable("glpi_plugin_supportcontract_tickets_contracts", 
                                     "`contracts_id`='".$contracts_id."'
                                     AND `plugin_supportcontract_contracts_periods_id`='".$a_period['id']."'") == 0) {
               echo "<form method='post' action=\"".$CFG_GLPI['root_doc'] . "/plugins/supportcontract/front/purchase.form.php\">";
               echo "<input type='hidden' name='id' value='".$a_purchase['id']."'/>";
               echo "<input type='submit' name='delete' value=\"".__('Permanently delete')."\"
                         class='submit' ".Html::addConfirmationOnAction(__('Confirm the final deletion ?')).">";
               Html::closeForm();
            }
            echo "</td>";            
            echo "</tr>";
         }
      }      
      echo "</table>";
   }

   
   
   /**
    * Display purchase history by period
    */
   function showHistoryByPeriod($contracts_id, $a_psContracts) {

      $psContract_Period = new PluginSupportcontractContract_Period();
      
      $onlyone=false;
      $presentcontrat=null;
      
      $a_periods = $psContract_Period->find("`contracts_id`='".$contracts_id."'
         AND `end` IS NOT NULL");
      
      foreach ($a_periods as $a_period) {
         $this->showHistory($contracts_id, $a_psContracts, $a_period['id']);
         echo "<br/>";
      }
      
   }
   
   
   
   static function getUnusedUnits($pscontract_periods_id) {
      global $DB;
      
      $query = "SELECT SUM(`unit_number`) FROM `glpi_plugin_supportcontract_tickets_contracts`
         WHERE `plugin_supportcontract_contracts_periods_id`='".$pscontract_periods_id."'
            AND `invoice_state`='1'";
      
      if ($result = $DB->query($query)) {
         $nb = $DB->result($result,0,0);
         if (!is_null($nb)) {
            return $nb;
         }
      }
      return '0';
   }

   
   
   static function getUninvoicedUnits($pscontract_periods_id) {
      global $DB;
      
      $query = "SELECT SUM(`unit_number`) FROM `glpi_plugin_supportcontract_tickets_contracts`
         WHERE `plugin_supportcontract_contracts_periods_id`='".$pscontract_periods_id."'
            AND `invoice_state`='0'";
      
      if ($result = $DB->query($query)) {
         $nb = $DB->result($result,0,0);
         if (!is_null($nb)) {
            return $nb;
         }
      }
      return '0';
   }
}

?>