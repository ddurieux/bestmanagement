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

class PluginSupportcontractContract extends CommonDBTM {
   
   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName($nb=0) {
      return _n('Contract', 'Contracts', $nb);
   }



   static function canCreate() {
      return true;
   }


   static function canView() {
      return true;
   }

   
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      $itemtype = $item->getType();
      if ($itemtype == 'Contract') {
         if ($withtemplate == 0) {
            return __('Support contract', 'supportcontract');
         }
      } else if ($itemtype == 'PluginSupportcontractContract') {
         if (countElementsInTable($this->getTable(), 
                                  "`id`='".$item->getID()."'") > 0) {
            return array(10 => __('Summary' ,'supportcontract'),
                         11 => __('Purchase history' ,'supportcontract'),
                         12 => __('Historical period' ,'supportcontract'),
                         13 => __('Purchase' ,'supportcontract'),
                         14 => __('Renewal' ,'supportcontract'),
                         15 => __('Invoice' ,'supportcontract'),
                         16 => __('Tickets' ,'supportcontract'),
                         17 => __('Sub entities' ,'supportcontract'));
         } else if ($item->fields['id'] == -1) {
            return array(10 => __('Summary' ,'supportcontract'),
                         11 => __('Purchase history' ,'supportcontract'),
                         12 => __('Historical period' ,'supportcontract'),
                         13 => __('Purchase' ,'supportcontract'),
                         14 => __('Renewal' ,'supportcontract'),
                         15 => __('Invoice' ,'supportcontract'),
                         16 => __('Tickets' ,'supportcontract'),
                         17 => __('Sub entities' ,'supportcontract'));
         }
      }
      return '';
   }

   
   
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      
      $psContract = new self();
      if ($item->getID() > 0) {
         $itemtype = $item->getType();
         if ($itemtype == 'Contract') {
           $psContract->showGeneralForm($item->getID());
         } else {
            switch ($tabnum) {
               
               case 10:
                  $psContract->showSummary();
                  break;
               
               case 11:
                  $psPurchase = new PluginSupportcontractPurchase();                  
                  $psPurchase->showHistory($item->fields['contracts_id'], $item->fields);
                  break;
               
               case 12:
                  $psPurchase = new PluginSupportcontractPurchase();                  
                  $psPurchase->showHistoryByPeriod($item->fields['contracts_id'], $item->fields);
                  break;
               
               case 13:
                  $psPurchase = new PluginSupportcontractPurchase();
                  $psPurchase->showform($item);
                  break;
               
               case 14:
                  // reconduction
                  $psReconduction = new PluginSupportcontractContract_Period();
                  $psReconduction->showList($item);
                  $psReconduction->showForm($item);
                  break;
               
               case 15:
                  
                  break;
               
               case 16:
                  $psTicket_Contract = new PluginSupportcontractTicket_Contract();
                  $psTicket_Contract->showTickets($item->fields['contracts_id']);
                  break;
               
               case 17:
                  
                  break;
               
            }
         }
      }

      return true;
   }


   
   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab('PluginSupportcontractContract', $ong, $options);
      return $ong;
   }

   
   
   function showGeneralForm($contracts_id) {
      global $CFG_GLPI;
     
      $a_contracts = $this->find("`contracts_id`='".$contracts_id."'", "", 1);
      if (count($a_contracts) == 1) {
         $a_contract = current($a_contracts);
         $this->getFromDB($a_contract['id']);
      } else {
         $this->showForm(0);
         return true;
      }
      
      $options = array();
      $this->showTabs();
         
      echo "<div id='tabcontent2'>&nbsp;</div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
         
   }
   
   
   
   function showForm($items_id, $options=array()) {
      global $CFG_GLPI;

      if ($items_id > 0) {
         $this->getFromDB($items_id);
      } else {
         $this->getEmpty();
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Unit', 'supportcontract')."&nbsp;:</td>";
      echo "<td>";
      $a_input = array(
          ''          => Dropdown::EMPTY_VALUE,
          'hour'      => __('Hours number', 'supportcontract'),
          'nbtickets' => __('Tickets number', 'supportcontract'),
          'nbhalfday' => __('Half days number', 'supportcontract'),
      );
      $rand = Dropdown::showFromArray('unit_type', $a_input);
      echo '<input type="hidden" name="contracts_id" value="'.$_POST['id'].'" />';
      echo "</td>";
      echo "<td>".__('Defined on', 'supportcontract')."&nbsp;:</td>";
      echo "<td align='center'>";
      
      $params=array('unit_type'=>'__VALUE__',
                    'rand'=>$rand,
                    'myname'=>'unit_type',
                    'name' => 'definition');

      Ajax::updateItemOnEvent(
              'dropdown_unit_type'.$rand,
              'show_value',
              $CFG_GLPI["root_doc"]."/plugins/supportcontract/ajax/dropdowndefinition.php",
              $params);
      echo "<div id='show_value'></div>";

      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }
   
   
   
   function showSummary($id=0) {

      $psContract_Period = new PluginSupportcontractContract_Period();
      
      if ($id > 0) {
         $this->getFromDB($id);
      } else {
         $this->getFromDB($_POST['id']);
      }

      $contract = new Contract();
      $contract->getFromDB($this->fields['contracts_id']);

      $a_period = $psContract_Period->getCurrentPeriod($this->fields['contracts_id']);
      
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      if ($id > 0) { // we display contract name/link
         echo "<th width='250'>";
         echo $contract->getLink(1)." - ".Dropdown::getDropdownName("glpi_entities", $contract->fields['entities_id']);
         echo "</th><th colspan='4' rowspan='2'>";
      } else {
         echo "<th colspan='5'>";
      }
      echo __('Dashboard', 'supportcontract')." - ".
              PluginSupportcontractContract::getUnit_typeNameForContract($this->fields['contracts_id'])."</th>";
      echo "</tr>";
      
      if ($id > 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<th>";
         echo __('From')." ";
         echo Html::convDate($contract->fields['begin_date']);
         echo " ".__('To')." ";
         echo Html::convDate(Infocom::getWarrantyExpir($contract->fields["begin_date"],
                                                       $contract->fields["duration"],
                                                       $contract->fields["notice"]));
         echo "</th>";
         echo "</tr>";
      }
      
      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      if ($this->fields['definition'] == "TaskCategory") {
         echo TaskCategory::getTypeName();
      } else if ($this->fields['definition'] == "ItilCategory") {
         echo ItilCategory::getTypeName();
      } else if ($this->fields['definition'] == "priority") {
         echo __('Priority');
      }
      echo "</th>";
      if ($this->fields['illimite'] == 1) {
         echo "<th colspan='4'>";
         echo __('Used units', 'supportcontract');
         echo "</th>";
      } else {
         echo "<th>";
         echo __('Purchassed units', 'supportcontract');
         echo "</th>";
         echo "<th>";
         echo __('Reported units', 'supportcontract');
         echo "</th>";
         echo "<th>";
         echo __('Used units', 'supportcontract');
         echo "</th>";
         echo "<th>";
         echo __('Remaining units', 'supportcontract');
         echo "</th>";
      }
      echo "</tr>";
      $a_elements = array();
      $a_entities = getSonsOf('glpi_entities', $contract->fields['entities_id']);
      if ($this->fields['definition'] == "priority") {
         for ($priority = 1; $priority <= 6; $priority++) {
            $a_elements = $this->getSummaryDetail("priority", $priority, $a_period);
            $this->showSummaryDetail($a_elements);
         }
      } else if ($this->fields['definition'] == "ItilCategory") {
         $itilCategory = new ITILCategory();
         $a_categories = $itilCategory->find("`entities_id` IN (".implode(',', $a_entities).")");
         foreach ($a_categories as $a_category) {
            $a_elements = $this->getSummaryDetail("ItilCategory", $a_category['id'], $a_period);
            $this->showSummaryDetail($a_elements);
         }
      } else if ($this->fields['definition'] == "TaskCategory") {
         $taskCategory = new TaskCategory();
         $a_categories = $taskCategory->find("`entities_id` IN (".implode(',', $a_entities).")");
         foreach ($a_categories as $a_category) {
            $a_elements = $this->getSummaryDetail("TaskCategory", $a_category['id'], $a_period);
            $this->showSummaryDetail($a_elements);
         }
      }      
      echo "</table>";
   }
   
   
   
   function showSummaryDetail($a_elements) {
      
      if (count($a_elements) > 0) {
         foreach ($a_elements as $a_element) {
            echo "<tr class='tab_bg_3'>";
            echo "<td>";
            echo $a_element[0];
            echo "</td>";
            echo "<td>";
            echo $a_element[1];
            echo "</td>";
            echo "<td>";
            echo $a_element[2];
            echo "</td>";
            echo "<td>";
            echo $a_element[3];
            echo "</td>";
            echo "<td>";
            echo $a_element[4];
            echo "</td>";
            echo "</tr>";
         }
      }
   }
   
   
   
   function getSummaryDetail($type, $id, $a_period) {
      global $DB;
      
      $a_elements = array();
      
      $psPurchase = new PluginSupportcontractPurchase();

      $cnt = countElementsInTable("glpi_plugin_supportcontract_purchases", 
                                  "`contracts_id`='".$this->fields['contracts_id']."'
                                    AND `definitions_id`='".$id."'
                                    AND `plugin_supportcontract_contracts_periods_id`='".$a_period['id']."' ");

      $nb_units_used = 0;
      if ($this->fields['unit_type'] == "hour") {
         $query = "SELECT SUM(`glpi_tickettasks`.`actiontime`) as cnt 
               FROM `glpi_tickettasks`
            LEFT JOIN `glpi_tickets` ON `glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id`
            LEFT JOIN `glpi_plugin_supportcontract_tickets_contracts`
               ON `glpi_tickets`.`id`=`glpi_plugin_supportcontract_tickets_contracts`.`tickets_id`
            LEFT JOIN `glpi_contracts` ON `contracts_id` = `glpi_contracts`.`id` 
            WHERE `glpi_contracts`.`id`='".$this->fields['contracts_id']."' 
               AND `invoice_state`='1'
               AND `plugin_supportcontract_contracts_periods_id`='".$a_period['id']."' ";
         if ($type == "priority") {
            $query .=  "AND `glpi_tickets`.`priority`='".$id."'";
         } else if ($type == "TaskCategory") {
            $query .=  "AND `glpi_tickettasks`.`taskcategories_id`='".$id."'";
         }              
         $result = $DB->query($query);
         if ($DB->numrows($result) != 0) {
            $data = $DB->fetch_assoc($result);
            $nb_units_used = $data['cnt'] / 3600;
         }
      } else {
         $query = "SELECT SUM(`unit_number`) as cnt 
               FROM `glpi_plugin_supportcontract_tickets_contracts`
            LEFT JOIN `glpi_tickets` 
               ON `glpi_tickets`.`id` = `glpi_plugin_supportcontract_tickets_contracts`.`tickets_id` 
            WHERE `contracts_id`='".$this->fields['contracts_id']."'  
               AND `invoice_state`='1' 
               AND `plugin_supportcontract_contracts_periods_id`='".$a_period['id']."' ";
         if ($type == "priority") {
            $query .=  "AND `glpi_tickets`.`priority`='".$id."'";
         } else if ($type == "ItilCategory") {
            $query .=  "AND `glpi_tickets`.`itilcategories_id`='".$id."'";
         }              
         $result = $DB->query($query);
         if ($DB->numrows($result) != 0) {
            $data = $DB->fetch_assoc($result);
            $nb_units_used = $data['cnt'];
         }
      }
      if ($cnt > 0
              || $nb_units_used > 0) {

         $a_purchases = $psPurchase->find("`contracts_id`='".$this->fields['contracts_id']."'
                                          AND `definitions_id`='".$id."'
                                          AND `plugin_supportcontract_contracts_periods_id`='".$a_period['id']."'");
         $total_unit_bought = 0;
         foreach ($a_purchases as $a_purchase) {
            $total_unit_bought += $a_purchase['unit'];
         }        
         $i = 0;
         foreach ($a_purchases as $a_purchase) {
            if ($type == "priority") {
               $a_elements[$i][0] = Ticket::getPriorityName($id);
            } else {
               $a_elements[$i][0] = Dropdown::getDropdownName(getTableForItemType($type), $id);
            }
            $a_elements[$i][1] = $this->displayUnits($a_purchase['unit'], 0);
            $a_elements[$i][2] = $this->displayUnits(0, 0);
            
            $nb_units_not_used = 0;
            if ($nb_units_used >= $a_purchase['unit']) {
               $a_elements[$i][3] = $this->displayUnits($a_purchase['unit'], 0);
               $nb_units_used -= $a_purchase['unit'];
            } else {
               $a_elements[$i][3] = $this->displayUnits($nb_units_used, 0);
               $nb_units_not_used = $a_purchase['unit'] - $nb_units_used;
               $nb_units_used = 0;
            }
            $a_elements[$i][4] = $this->displayUnits($nb_units_not_used, 0).
               " (".round(((100 * $nb_units_not_used)/$a_purchase['unit']), 1)." %)";
            $i++;
         }
         if ($nb_units_used > 0) {
            if ($type == "priority") {
               $a_elements[$i][0] = Ticket::getPriorityName($id);
            } else {
               $a_elements[$i][0] = Dropdown::getDropdownName(getTableForItemType($type), $id);
            }
            $a_elements[$i][1] = $this->displayUnits(0, 0);
            $a_elements[$i][2] = $this->displayUnits(0, 0);
            $a_elements[$i][3] = $this->displayUnits($nb_units_used, 0);
            $a_elements[$i][4] = $this->displayUnits(-$nb_units_used, 0);
         }
      }
      return $a_elements;
   }
   

   
   function showSummaryPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item) {

      $this->getFromDB($_POST['id']);

      $a_contracts = current($this->find("`contracts_id`='".$item->fields['id']."'", "", 1));
      $this->getFromDB($a_contracts['id']);
 
      $pdf->setColumnsSize(100);
      $pdf->displayTitle(__('Dashboard', 'supportcontract')." - ".
              PluginSupportcontractContract::getUnit_typeNameForContract($this->fields['contracts_id']));
      
      $pdf->setColumnsSize(20, 20, 20, 20, 20);
      $col = array();
      if ($this->fields['definition'] == "TaskCategory") {
         $col = TaskCategory::getTypeName();
      } else if ($this->fields['definition'] == "ItilCategory") {
         $col = ItilCategory::getTypeName();
      } else if ($this->fields['definition'] == "priority") {
         $col = __('Priority');
      }
      
      $pdf->displayTitle($col, 
                         __('Purchassed units', 'supportcontract'),
                         __('Reported units', 'supportcontract'),
                         __('Used units', 'supportcontract'),
                         __('Remaining units', 'supportcontract'));
      
      $a_elements = array();
      $a_entities = getSonsOf('glpi_entities', $contract->fields['entities_id']);
      if ($this->fields['definition'] == "priority") {
         for ($priority = 1; $priority <= 6; $priority++) {
            $a_elements = $this->getSummaryDetail("priority", $priority);
            $this->showSummaryDetailPDF($a_elements, $pdf);
         }
      } else if ($this->fields['definition'] == "ItilCategory") {
         $itilCategory = new ITILCategory();
         $a_categories = $itilCategory->find("`entities_id` IN (".implode(',', $a_entities).")");
         foreach ($a_categories as $a_category) {
            $a_elements = $this->getSummaryDetail("ItilCategory", $a_category['id']);
            $this->showSummaryDetailPDF($a_elements, $pdf);
         }
      } else if ($this->fields['definition'] == "TaskCategory") {
         $taskCategory = new TaskCategory();
         $a_categories = $taskCategory->find("`entities_id` IN (".implode(',', $a_entities).")");
         foreach ($a_categories as $a_category) {
            $a_elements = $this->getSummaryDetail("TaskCategory", $a_category['id']);
            $this->showSummaryDetailPDF($a_elements, $pdf);
         }
      } 
      $pdf->displaySpace();
   }
   
   
   
   function showSummaryDetailPDF($a_elements, PluginPdfSimplePDF $pdf) {
      
      if (count($a_elements) > 0) {
         foreach ($a_elements as $a_element) {
            $pdf->displayLine($a_element[0],
                              $a_element[1],
                              $a_element[2],
                              $a_element[3],
                              $a_element[4]);
         }
      }
   }
   
   
   
   /**
    * showTabs function of core modified
    */
   function showTabs($options = array()) {
      global $CFG_GLPI;

      // for objects not in table like central
      if (isset($this->fields['id'])) {
         $ID = $this->fields['id'];
      } else {
        $ID = 0;
      }

      $target         = $_SERVER['PHP_SELF'];
      $extraparamhtml = "";
      $extraparam     = "";
      $withtemplate   = "";

      if (is_array($options) && count($options)) {
         if (isset($options['withtemplate'])) {
            $withtemplate = $options['withtemplate'];
         }
         foreach ($options as $key => $val) {
            if ($key[0] != '_') {
               $extraparamhtml .= "&amp;$key=$val";
               $extraparam     .= "&$key=$val";
            }
         }
      }

      if (empty($withtemplate) && $ID && $this->getType() && $this->displaylist) {
         $glpilistitems =& $_SESSION['glpilistitems'][$this->getType()];
         $glpilisttitle =& $_SESSION['glpilisttitle'][$this->getType()];
         $glpilisturl   =& $_SESSION['glpilisturl'][$this->getType()];

         if (empty($glpilisturl)) {
            $glpilisturl = $this->getSearchURL();
         }

         echo "<div class='sep'></div>";
      }
      echo "<div id='tabspanel2' class='center-h'></div>";

      $active      = 0;
      $onglets = $this->defineAllTabs($options);

      $display_all = true;
      if (isset($onglets['no_all_tab'])) {
         $display_all = false;
         unset($onglets['no_all_tab']);
      }

      $class = $this->getType();
      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE
          && ($ID > 0 || $this->showdebug)
          && (method_exists($class, 'showDebug')
              || in_array($class, $CFG_GLPI["infocom_types"])
              || in_array($class, $CFG_GLPI["reservation_types"]))) {

            $onglets[-2] = __('Debug');
      }

      if (count($onglets)) {
         $tabpage = $this->getTabsURL();
         $tabs    = array();

         foreach ($onglets as $key => $val ) {
            $tabs[$key] = array('title'  => $val,
                                'url'    => $tabpage,
                                'params' => "target=$target&itemtype=".$this->getType().
                                            "&glpi_tab=$key&id=$ID$extraparam");
         }

         // Plugin with plugin_get_headings_xxx
         $plug_tabs = Plugin::getTabs($target,$this, $withtemplate);
         $tabs += $plug_tabs;

         // Not all tab for templates and if only 1 tab
         if ($display_all && empty($withtemplate) && count($tabs)>1) {
            $tabs[-1] = array('title'  => __('All'),
                              'url'    => $tabpage,
                              'params' => "target=$target&itemtype=".$this->getType().
                                          "&glpi_tab=-1&id=$ID$extraparam");
         }

         Ajax::createTabs('tabspanel2', 'tabcontent2', $tabs, $this->getType());
      }
   }

   
   
   static function getDefinitionForContract($contracts_id) {
      
      $psContract = new self();
      $a_contracts = $psContract->find("`contracts_id`='".$contracts_id."'", "", 1);
      if (count($a_contracts) == 1) {
         $a_contract = current($a_contracts);
         return $a_contract['definition'];
      }
      return '';
   }

   
   
   static function getUnit_typeForContract($contracts_id) {
      
      $psContract = new self();
      $a_contracts = $psContract->find("`contracts_id`='".$contracts_id."'", "", 1);
      if (count($a_contracts) == 1) {
         $a_contract = current($a_contracts);
         return $a_contract['unit_type'];
      }
      return '';
   }
   
   
   
   static function getUnit_typeNameForContract($contracts_id) {
      
      $psContract = new self();
      $a_contracts = $psContract->find("`contracts_id`='".$contracts_id."'", "", 1);
      if (count($a_contracts) == 1) {
         $a_contract = current($a_contracts);
         if ($a_contract['unit_type'] == 'hour') {
            return __('Hours number', 'supportcontract');
         } else if ($a_contract['unit_type'] == 'nbtickets') {
            return __('Ticket number', 'supportcontract');
         } else if ($a_contract['unit_type'] == 'nbhalfday') {
            return __('Half days number', 'supportcontract');
         }
      }
      return '';
   }
   
   
   
   function displayUnits($unit, $display=1) {
      $val = '';
      if ($this->fields['unit_type'] == "hour") {
         $val =  PluginSupportcontractToolbox::displayHours($unit);
      } else {
         $val =  $unit;
      }
      if ($display == 1) {
         echo $val;
         return;
      }
      return $val;
   }
   
   
   
   function displayMenu() {
      global $CFG_GLPI;
      
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      if ($_GET['display'] != 'summary') {
         echo "<a href='".$CFG_GLPI['root_doc']."/plugins/supportcontract/front/contract.php?display=summary'>";
      }
      echo __('Contracts summary', 'supportcontract');
      if ($_GET['display'] != 'summary') {
         echo "</a>";
      }
      echo "</th>";
      echo "<th>";
      if ($_GET['display'] != 'unaffectedtickets') {
         echo "<a href='".$CFG_GLPI['root_doc']."/plugins/supportcontract/front/contract.php?display=unaffectedtickets'>";
      }
      echo __('Tickets to affect', 'supportcontract');
      if ($_GET['display'] != 'unaffectedtickets') {
         echo "</a>";
      }
      echo "</th>";
      echo "<th>";
      echo "<a href=''>".__('Tickets for invoices', 'supportcontract')."</a>";
      echo "</th>";
      echo "<th>";
      echo "<a href=''>".__('Contracts for invoices', 'supportcontract')."</a>";
      echo "</th>";
      echo "</tr>";
      echo "</table>";  
      echo "<br/>";
   }
   
   
   
   function showSummaryAllContracts() {
      global $DB;

      $query = "SELECT `".$this->getTable()."`.* FROM `".$this->getTable()."`
         LEFT JOIN `glpi_contracts` ON `glpi_contracts`.`id`=`".$this->getTable()."`.`contracts_id`
         LEFT JOIN `glpi_entities` ON `glpi_contracts`.`entities_id`=`glpi_entities`.`id`
         WHERE `glpi_contracts`.`entities_id` IN (".implode(',', $_SESSION['glpiactiveentities']).")
            AND `is_template`='0'
         ORDER BY `glpi_entities`.`level` ASC, `glpi_entities`.`name` ASC, 
            `glpi_contracts`.`name` ASC";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $this->showSummary($data['id']);
      }
   }
   
   
   
   static function purgeContract($item) {
      
      $psContract = new self();
      
      $a_contracts = getAllDatasFromTable($psContract->getTable(), 
                                "`contracts_id`='".$item->getID()."'");
      foreach ($a_contracts as $data) {
         $psContract->delete($data, 1);
      }
   }
   
   
   
   function post_addItem() {
      global $DB;

      $Contract_Period = new PluginSupportcontractContract_Period();

      $input = array(
          'contracts_id'  => $_POST['contracts_id'],
          'begin'         => date('Y-m-d'),
          'date_save'     => date('Y-m-d'),
          'report_credit' => 0
      );
      $Contract_Period->add($input);

   }
}

?>