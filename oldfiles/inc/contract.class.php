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

class PluginBestmanagementContract extends CommonDBTM {
   
   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName() {
      global $LANG;

      return $LANG['Menu'][25];
   }



   function canCreate() {
      return true;
   }


   function canView() {
      return true;
   }

   
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;
      
      $itemtype = $item->getType();
      if ($itemtype == 'Contract') {
         return $LANG["bestmanagement"]["title"][0];
      } else if ($itemtype == 'PluginBestmanagementContract') {
         if (countElementsInTable($this->getTable(), 
                                  "`id`='".$item->getID()."'") > 0) {
            return array(10 => $LANG["bestmanagement"]["tabs"][1],
                         11 => $LANG["bestmanagement"]["tabs"][2],
                         12 => $LANG["bestmanagement"]["tabs"][3],
                         13 => $LANG["bestmanagement"]["tabs"][4],
                         14 => $LANG["bestmanagement"]["tabs"][5],
                         15 => $LANG["bestmanagement"]["tabs"][6],
                         16 => $LANG["bestmanagement"]["tabs"][7],
                         17 => $LANG["bestmanagement"]["tabs"][8]);
         }
      }
      return '';
   }

   
   
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      
      $pbContract = new self();
      if ($item->getID() > 0) {
         $itemtype = $item->getType();
         if ($itemtype == 'Contract') {
           $pbContract->showGeneralForm($item->getID());
         } else {
            switch ($tabnum) {
               
               case 10:
                  $pbContract->showSummary();
                  break;
               
               case 11:
                  $pbPurchase = new PluginBestmanagementPurchase();                  
                  $pbPurchase->showHistory($item->fields['contracts_id'], $item->fields);
                  break;
               
               case 12:
                  
                  break;
               
               case 13:
                  $pbPurchase = new PluginBestmanagementPurchase();
                  $pbPurchase->showform($item);
                  break;
               
               case 14:
                  
                  break;
               
               case 15:
                  
                  break;
               
               case 16:
                  $pbTicket_Contract = new PluginBestmanagementTicket_Contract();
                  $pbTicket_Contract->showTickets($item->fields['contracts_id']);
                  break;
               
               case 17:
                  
                  break;
               
            }
         }
      }

      return true;
   }


   
   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI;
      
      $ong = array();
      $this->addStandardTab('PluginBestmanagementContract', $ong, $options); // All devices : use one to define tab
      return $ong;
   }

   
   
   function showGeneralForm($contracts_id) {
      global $LANG, $CFG_GLPI;
     
      $a_contracts = $this->find("`contracts_id`='".$contracts_id."'", "", 1);
      if (count($a_contracts) == 1) {
         $a_contract = current($a_contracts);
         $this->getFromDB($a_contract['id']);
      } else {
         $this->showForm(0);
         return true;
      }
      
      $options = array();
      /*
       * showTabs function of core modified
       */
      $this->showTabs();
      /*
       * End showTabs function of core modified
       */      
      
         
      echo "<div id='tabcontent2'>&nbsp;</div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
         
   }
   
   
   
   function showForm($items_id, $options=array()) {
      global $LANG, $CFG_GLPI;

      if ($items_id > 0) {
         $this->getFromDB($items_id);
      } else {
         $this->getEmpty();
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG["bestmanagement"]["sort"][9]."&nbsp;:</td>";
      echo "<td>";
      $a_input = array(
          ''          => Dropdown::EMPTY_VALUE,
          'hour'      => $LANG["bestmanagement"]["sort"][6],
          'nbtickets' => $LANG["bestmanagement"]["sort"][7],
          'nbhalfday' => $LANG["bestmanagement"]["sort"][8],
      );
      $rand = Dropdown::showFromArray('unit_type', $a_input);
      echo '<input type="hidden" name="contracts_id" value="'.$_POST['id'].'" />';
      echo "</td>";
      echo "<td>".$LANG["bestmanagement"]["sort"][10]."&nbsp;:</td>";
      echo "<td align='center'>";
      
      $params=array('unit_type'=>'__VALUE__',
                    'rand'=>$rand,
                    'myname'=>'unit_type',
                    'name' => 'definition');

      Ajax::updateItemOnEvent(
              'dropdown_unit_type'.$rand,
              'show_value',
              $CFG_GLPI["root_doc"]."/plugins/bestmanagement/ajax/dropdowndefinition.php",
              $params);
      echo "<div id='show_value'></div>";

      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }
   
   
   
   function showSummary() {
      global $LANG;
      
      $this->getFromDB($_POST['id']);

      $contract = new Contract();
      $contract->getFromDB($this->fields['contracts_id']);
      
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='5'>".$LANG["bestmanagement"]["tabrecap"][0]." - ".
              PluginBestmanagementContract::getUnit_typeNameForContract($this->fields['contracts_id'])."</th>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      if ($this->fields['definition'] == "TaskCategory") {
         echo TaskCategory::getTypeName();
      } else if ($this->fields['definition'] == "ItilCategory") {
         echo ItilCategory::getTypeName();
      } else if ($this->fields['definition'] == "priority") {
         echo $LANG['joblist'][2];
      }
      echo "</th>";
      if ($this->fields['illimite'] == 1) {
         echo "<th colspan='4'>";
         echo $LANG['bestmanagement']['tabrecap'][15];
         echo "</th>";
      } else {
         echo "<th>";
         echo $LANG['bestmanagement']['tabrecap'][16];
         echo "</th>";
         echo "<th>";
         echo $LANG['bestmanagement']['tabrecap'][17];
         echo "</th>";
         echo "<th>";
         echo $LANG['bestmanagement']['tabrecap'][15];
         echo "</th>";
         echo "<th>";
         echo $LANG['bestmanagement']['tabrecap'][18];
         echo "</th>";
      }
      echo "</tr>";
      $a_entities = getSonsOf('glpi_entities', $contract->fields['entities_id']);
      if ($this->fields['definition'] == "priority") {
         for ($priority = 1; $priority <= 6; $priority++) {
            $this->showSummaryDetail("priority", $priority);
         }
      } else if ($this->fields['definition'] == "ItilCategory") {
         $itilCategory = new ITILCategory();
         $a_categories = $itilCategory->find("`entities_id` IN (".implode(',', $a_entities).")");
         foreach ($a_categories as $a_category) {
            $this->showSummaryDetail("ItilCategory", $a_category['id']);
         }
      } else if ($this->fields['definition'] == "TaskCategory") {
         $taskCategory = new TaskCategory();
         $a_categories = $taskCategory->find("`entities_id` IN (".implode(',', $a_entities).")");
         foreach ($a_categories as $a_category) {
            $this->showSummaryDetail("TaskCategory", $a_category['id']);
         }
      }
      echo "</table>";
   }
   
   
   
   function showSummaryDetail($type, $id) {
      global $DB;
      
      $pbPurchase = new PluginBestmanagementPurchase();

      $cnt = countElementsInTable("glpi_plugin_bestmanagement_purchases", 
                                  "`contracts_id`='".$this->fields['contracts_id']."'
                                    AND `definitions_id`='".$id."'");
      $nb_units_used = 0;
      if ($this->fields['unit_type'] == "hour") {
         $query = "SELECT SUM(`glpi_tickettasks`.`actiontime`) as cnt 
               FROM `glpi_tickettasks`
            LEFT JOIN `glpi_tickets` ON `glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id`
            LEFT JOIN `glpi_plugin_bestmanagement_tickets_contracts`
               ON `glpi_tickets`.`id`=`glpi_plugin_bestmanagement_tickets_contracts`.`tickets_id`
            LEFT JOIN `glpi_contracts` ON `contracts_id` = `glpi_contracts`.`id` 
            WHERE `glpi_contracts`.`id`='".$this->fields['contracts_id']."' ";
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
               FROM `glpi_plugin_bestmanagement_tickets_contracts`
            LEFT JOIN `glpi_tickets` 
               ON `glpi_tickets`.`id` = `glpi_plugin_bestmanagement_tickets_contracts`.`tickets_id` 
            WHERE `contracts_id`='".$this->fields['contracts_id']."' ";
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

         $a_purchases = $pbPurchase->find("`contracts_id`='".$this->fields['contracts_id']."'
                                          AND `definitions_id`='".$id."'");
         $total_unit_bought = 0;
         foreach ($a_purchases as $a_purchase) {
            $total_unit_bought += $a_purchase['unit'];
         }               
         echo "<tr class='tab_bg_3'>";
         echo "<td rowspan='";
         if ($nb_units_used > $total_unit_bought) {
            echo ($cnt + 1);
         } else {
            echo $cnt;
         }
         echo "'>";
         if ($type == "priority") {
            echo Ticket::getPriorityName($id);
         } else {
            echo Dropdown::getDropdownName(getTableForItemType($type), $id);
         }
         echo "</td>";

         $tr = 0;
         foreach ($a_purchases as $a_purchase) {
            if ($tr == 1) {
               echo "<tr class='tab_bg_3'>";
            } else {
               $tr = 1;
            }
            echo "<td>";
            $this->displayUnits($a_purchase['unit']);
            echo "</td>";
            echo "<td>";
            $this->displayUnits(0);
            echo "</td>";
            echo "<td>";
            $nb_units_not_used = 0;
            if ($nb_units_used >= $a_purchase['unit']) {
               $this->displayUnits($a_purchase['unit']);
               $nb_units_used -= $a_purchase['unit'];
            } else {
               $this->displayUnits($nb_units_used);
               $nb_units_not_used = $a_purchase['unit'] - $nb_units_used;
               $nb_units_used = 0;
            }
            echo "</td>";
            echo "<td>";
            $this->displayUnits($nb_units_not_used);
            echo " (".round(((100 * $nb_units_not_used)/$a_purchase['unit']), 1)." %)";
            echo "</td>";
            echo "</tr>";
         }
         if ($nb_units_used > 0) {
            if ($tr == 1) {
               echo "<tr class='tab_bg_3'>";
            }
            echo "<td class='tab_bg_1_2'>";
            $this->displayUnits(0);
            echo "</td>";
            echo "<td class='tab_bg_1_2'>";
            $this->displayUnits(0);
            echo "</td>";
            echo "<td class='tab_bg_1_2'>";
            $this->displayUnits($nb_units_used);
            echo "</td>";
            echo "<td class='tab_bg_1_2'>";
            $this->displayUnits(-$nb_units_used);
            echo "</td>";
            echo "</tr>";
         }
      }
   }
   
   
   function showSummaryPDF($pdf) {
      global $DB, $LANG;
      
      $pbPurchase = new PluginBestmanagementPurchase();
      
      $this->getFromDB($_POST['id']);
      
      $pdf->setColumnsSize(100);
      $pdf->displayTitle($LANG["bestmanagement"]["tabrecap"][0]." - ".
              PluginBestmanagementContract::getUnit_typeNameForContract($this->fields['contracts_id']));
      $pdf->setColumnsSize(25, 25, 25, 25);
      
      $col1 = '';
      if ($this->fields['definition'] == "TaskCategory") {
         $col1 = TaskCategory::getTypeName();
      } else if ($this->fields['definition'] == "ItilCategory") {
         $col1 = ItilCategory::getTypeName();
      } else if ($this->fields['definition'] == "priority") {
         $col1 = $LANG['joblist'][2];
      }
      
      $pdf->displayTitle(
         $col1,
         $LANG['bestmanagement']['tabrecap'][16],
         $LANG['bestmanagement']['tabrecap'][15],
         $LANG['bestmanagement']['tabrecap'][18]
      );
      
      
      
      $pdf->displaySpace();
   }
   
   
   
   /**
    * showTabs function of core modified
    */
   function showTabs($options = array()) {
      global $LANG, $CFG_GLPI;

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

/* Modification for plugin Best Management
         echo "<div id='menu_navigate'>";

         $next = $prev = $first = $last = -1;
         $current = false;
         if (is_array($glpilistitems)) {
            $current = array_search($ID,$glpilistitems);
            if ($current !== false) {

               if (isset($glpilistitems[$current+1])) {
                  $next = $glpilistitems[$current+1];
               }

               if (isset($glpilistitems[$current-1])) {
                  $prev = $glpilistitems[$current-1];
               }

               $first = $glpilistitems[0];
               if ($first == $ID) {
                  $first = -1;
               }

               $last = $glpilistitems[count($glpilistitems)-1];
               if ($last == $ID) {
                  $last = -1;
               }

            }
         }
         $cleantarget = HTML::cleanParametersURL($target);
         echo "<ul>";
         echo "<li><a href=\"javascript:showHideDiv('tabsbody','tabsbodyimg','".$CFG_GLPI["root_doc"].
                    "/pics/deplier_down.png','".$CFG_GLPI["root_doc"]."/pics/deplier_up.png')\">";
         echo "<img alt='' name='tabsbodyimg' src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\">";
         echo "</a></li>";

         echo "<li><a href=\"".$glpilisturl."\">";

         if ($glpilisttitle) {
            if (Toolbox::strlen($glpilisttitle) > $_SESSION['glpidropdown_chars_limit']) {
               $glpilisttitle = Toolbox::substr($glpilisttitle, 0,
                                                $_SESSION['glpidropdown_chars_limit'])
                                . "&hellip;";
            }
            echo $glpilisttitle;

         } else {
            echo $LANG['common'][53];
         }
         echo "</a>&nbsp;:&nbsp;</li>";

         if ($first > 0) {
            echo "<li><a href='$cleantarget?id=$first$extraparamhtml'><img src='".
                       $CFG_GLPI["root_doc"]."/pics/first.png' alt=\"".$LANG['buttons'][55].
                       "\" title=\"".$LANG['buttons'][55]."\"></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/first_off.png' alt=\"".
                       $LANG['buttons'][55]."\" title=\"".$LANG['buttons'][55]."\"></li>";
         }

         if ($prev > 0) {
            echo "<li><a href='$cleantarget?id=$prev$extraparamhtml'><img src='".
                       $CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".$LANG['buttons'][12].
                       "\" title=\"".$LANG['buttons'][12]."\"></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/left_off.png' alt=\"".
                       $LANG['buttons'][12]."\" title=\"".$LANG['buttons'][12]."\"></li>";
         }

         if ($current !== false) {
            echo "<li>".($current+1) . "/" . count($glpilistitems)."</li>";
         }

         if ($next > 0) {
            echo "<li><a href='$cleantarget?id=$next$extraparamhtml'><img src='".
                       $CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".$LANG['buttons'][11].
                       "\" title=\"".$LANG['buttons'][11]."\"></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/right_off.png' alt=\"".
                       $LANG['buttons'][11]."\" title=\"".$LANG['buttons'][11]."\"></li>";
         }

         if ($last > 0) {
            echo "<li><a href='$cleantarget?id=$last$extraparamhtml'><img src=\"".
                       $CFG_GLPI["root_doc"]."/pics/last.png\" alt=\"".$LANG['buttons'][56].
                       "\" title=\"".$LANG['buttons'][56]."\"></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/last_off.png' alt=\"".
                       $LANG['buttons'][56]."\" title=\"".$LANG['buttons'][56]."\"></li>";
         }
         echo "</ul></div>";
*/
// End of modification         

         echo "<div class='sep'></div>";
      }
/* Modification for plugin Best Management
      echo "<div id='tabspanel' class='center-h'></div>";
*/
      echo "<div id='tabspanel2' class='center-h'></div>";
// End of modification         

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

            $onglets[-2] = $LANG['setup'][137];
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
            $tabs[-1] = array('title'  => $LANG['common'][66],
                              'url'    => $tabpage,
                              'params' => "target=$target&itemtype=".$this->getType().
                                          "&glpi_tab=-1&id=$ID$extraparam");
         }

/* Modification for plugin Best Management
         Ajax::createTabs('tabspanel', 'tabcontent', $tabs, $this->getType());
*/
         Ajax::createTabs('tabspanel2', 'tabcontent2', $tabs, $this->getType());
// End of modification         
      }
   }

   
   
   static function getUnit_typeForContract($contracts_id) {
      
      $pbContract = new self();
      $a_contracts = $pbContract->find("`contracts_id`='".$contracts_id."'", "", 1);
      if (count($a_contracts) == 1) {
         $a_contract = current($a_contracts);
         return $a_contract['unit_type'];
      }
      return '';
   }
   
   
   
   static function getUnit_typeNameForContract($contracts_id) {
      global $LANG;
      
      $pbContract = new self();
      $a_contracts = $pbContract->find("`contracts_id`='".$contracts_id."'", "", 1);
      if (count($a_contracts) == 1) {
         $a_contract = current($a_contracts);
         if ($a_contract['unit_type'] == 'hour') {
            return $LANG["bestmanagement"]["sort"][6];
         } else if ($a_contract['unit_type'] == 'nbtickets') {
            return $LANG["bestmanagement"]["sort"][7];
         } else if ($a_contract['unit_type'] == 'nbhalfday') {
            return $LANG["bestmanagement"]["sort"][8];
         }
      }
      return '';
   }
   
   
   function displayUnits($unit) {
      if ($this->fields['unit_type'] == "hour") {
         echo PluginBestmanagementToolbox::displayHours($unit);
      } else {
         echo $unit;
      }
   }
}

?>