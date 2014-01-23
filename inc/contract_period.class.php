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

class PluginSupportcontractContract_Period extends CommonDBTM {
   
   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName($nb=0) {
      return __('Contracts period', 'supportcontract');
   }



   static function canCreate() {
      return true;
   }


   static function canView() {
      return true;
   }

   
   
   function showForm(PluginSupportcontractContract $psContract) {
      global $DB;
      
      $this->check(-1,'w');
      $options = array();
      
      $this->showFormHeader($options);

      $a_period = $this->getCurrentPeriod($psContract->fields['contracts_id']);
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Contract begin date', 'supportcontract')." :";
      echo "</td>";
      echo "<td>";
      echo Html::convDate($a_period['begin']);
      echo "</td>";
      echo "<td>";
      echo __('Unit', 'supportcontract')." :";
      echo "</td>";
      echo "<td>";
      echo PluginSupportcontractContract::getUnit_typeNameForContract($psContract->fields['contracts_id']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2'>";
      echo "</td>";
      echo "<td>";
      echo __('Reported units from previous period', 'supportcontract')." :";
      echo "</td>";
      echo "<td>";
      echo $a_period['report_credit'];
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Purchased units', 'supportcontract')." :";
      echo "</td>";
      echo "<td>";
      $query = "SELECT SUM(`unit`) FROM `glpi_plugin_supportcontract_purchases`
         WHERE `plugin_supportcontract_contracts_periods_id`='".$a_period['id']."'";
      if ($result = $DB->query($query)) {
         $nb = $DB->result($result,0,0);
         if (is_null($nb)) {
            echo '0';
         } else {
            echo $nb;
         }
      }
      echo "</td>";
      echo "<td>";
      echo __('Not used units', 'supportcontract')." :";
      echo "</td>";
      echo "<td>";
      echo PluginSupportcontractPurchase::getUnusedUnits($a_period['id']);
      echo "";
      echo "</td>";
      echo "</tr>";

      // Verify not have tickets not invoiced
      $nb_tickets_not_inv = PluginSupportcontractPurchase::getUninvoicedUnits($a_period['id']);
      if ($nb_tickets_not_inv > 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4' align='center'>";
         echo "<strong>".__('Unable to close this period because not all tickets closed', 'supportcontract')."</strong>";
         echo "</td>";
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' align='center'>";
         echo "<input type='hidden' name='contracts_id' value='".$psContract->fields['contracts_id']."' />";
         echo "<input type='hidden' name='pscontracts_id' value='".$psContract->fields['id']."' />";
         echo "<input type='hidden' name='id' value='".$a_period['id']."' />";
         echo "<input type='submit' name='reconduction' value='".__('Closed + reconduction without report units', 'supportcontract')."' class='submit'>";
         echo "</td>";
         echo "<td colspan='2' align='center'>";
         echo "<input type='submit' name='reconduction_report' value='".__('Closed + reconduction with report units', 'supportcontract')."' class='submit'>";
         echo "</td>";
         echo "</tr>";
         
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4' align='center'>";
         echo "<input type='submit' name='no_reconduction' value='".__('Closed without reconduction', 'supportcontract')."' class='submit'>";
         echo "</td>";
         echo "</tr>";

      }
      
      echo "</table>";
      Html::closeForm();
   }
   
   
   
   function closePurchases($contracts_id, $report=FALSE) {
      global $DB;
      
      // If have some units used but not bought, may display a message
      
      $psPurchase = new PluginSupportcontractPurchase();
      
      $report_units = 0;
      
      $a_purchases = $psPurchase->find("`contracts_id`='".$contracts_id."'
                           AND `close_date` IS NULL");
      
      // get total units used
      
      foreach ($a_purchases as $data) {
         $input = array();
         $input['id'] = $data['id'];
         $input['close_date'] = $_SESSION['glpi_currenttime'];
         
         if ($report) {
            // Get number of unit bought
            
         }
         $psPurchase->update($input);
      }
   }
   
   
   
   function showList(PluginSupportcontractContract $psContract) {
      
      $a_periods = $this->find("`contracts_id`='".$psContract->fields['contracts_id']."'
         AND `end` IS NOT NULL");
      
      if (count($a_periods)) {
         echo "<table class='tab_cadre_fixe'>";
         
         echo "<tr class='tab_bg_1'>";
         echo '<th>';
         echo __('Start', 'supportcontract');
         echo '</th>';
         echo '<th>';
         echo __('End', 'supportcontract');
         echo '</th>';
         echo '<th>';
         echo __('Reported units fron previous period', 'supportcontract');
         echo '</th>';
         echo '</tr>';
      }
      
      foreach ($a_periods as $data) {
         echo "<tr class='tab_bg_1'>";
         echo '<td>';
         echo Html::convDate($data['begin']);
         echo '</td>';
         echo '<td>';
         echo Html::convDate($data['end']);
         echo '</td>';
         echo '<td>';
         echo $data['report_credit'];
         echo '</td>';
         echo '</tr>';
      }
      
      if (count($a_periods)) {
         echo '</table>';
      }
   }
   
   
   
   function getCurrentPeriod($contracts_id) {
      $a_periods = $this->find("`contracts_id`='".$contracts_id."'
         AND `end` IS NULL", "", 1);
      if (count($a_periods) == 0) {
         return FALSE;
      }
      return current($a_periods);
   }
}

?>