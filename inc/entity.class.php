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

class PluginSupportcontractEntity extends CommonDBTM {
   
   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName($nb=0) {
      return _n('Entity', 'Entities', $nb);
   }



   static function canCreate() {
      return true;
   }


   static function canView() {
      return true;
   }

   
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      
      $itemtype = $item->getType();
      if ($itemtype == 'Entity') {
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

   

   function showForm($entities_id, $options=array()) {

      $a_entities = $this->find("`entities_id`='".$entities_id."'", "", 1);
      $id = 0;
		if (count($a_entities) == 1) {
         $a_entity = current($a_entities);
         $id = $a_entity['id'];
      }
      
		if ($id > 0) {
			$this->check($id,'r');
      } else {
			$this->check(-1,'w');
         $this->fields['entete']    = -1;
         $this->fields['logo']      = -1;
         $this->fields['titre']     = -1;
         $this->fields['auteur']    = -1;
         $this->fields['sujet']     = -1;
         $this->fields['adresse']   = -1;
         $this->fields['cp']        = -1;
         $this->fields['ville']     = -1;
         $this->fields['tel']       = -1;
         $this->fields['fax']       = -1;
         $this->fields['web']       = -1;
         $this->fields['mail']      = -1;
         $this->fields['footer']    = -1;
         $this->fields['cgv']       = -1;
      }		

      $this->showFormHeader($options);
      
      $inpupt_YesNo = array();
      
      if ($entities_id != '0') {
         $inpupt_YesNo['-1'] = __('Inheritance of the parent entity');
      }
      $inpupt_YesNo[0] = __('No');
      $inpupt_YesNo[1] = __('Yes');
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('En-tête', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray('entete', $inpupt_YesNo, array('value'=>$this->fields['entete']));
      echo "</td>";
      echo "<td>";
      echo __('Logo', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      echo "<input type='file' />";
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      if ($this->fields['entete'] == '-1') {
            echo "<td colspan='2' class='green center'>";
            echo __('Inheritance of the parent entity')."&nbsp;:&nbsp;";
            echo Dropdown::getYesNo($this->getValueAncestor("entete", $entities_id));
            echo "</td>";
         } else {
            echo "<td colspan='2'>";
            echo "</td>";
         }
      echo "<td>";
      
      echo "</td>";
      echo "<td>";
      
      echo "</td>";
      echo "</tr>";      

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Title', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      
      echo "</td>";
      echo "<td>";
      echo __('Author', 'supportcontract')."&nbsp;:";
      echo "</td>";
      echo "<td>";

      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      if ($this->fields['entete'] == '-1') {
            echo "<td colspan='2' class='green center'>";
            
            echo "</td>";
         } else {
            echo "<td colspan='2'>";
            echo "</td>";
         }
      echo "<td>";
      
      echo "</td>";
      echo "<td>";
      
      echo "</td>";
      echo "</tr>";  
      
      
      
      
      
      
      $this->showFormButtons($options);
      return true;
   }
   
   

   /**
    * Get value of this entity or of ancestor
    * 
    * @global type $DB
    * @param type $fieldname
    * @param type $entities_id
    * @return type
    */
   function getValueAncestor($fieldname, $entities_id) {
      global $DB;
      
      $query = "SELECT * FROM `".$this->getTable()."`
         WHERE `entities_id`='".$entities_id."'
         LIMIT 1";
      
      $result = $DB->query($query);
      if ($DB->numrows($result) == '0') {
         $entities_ancestors = getAncestorsOf("glpi_entities", $entities_id);

         $nbentities = count($entities_ancestors);
         for ($i=0; $i<$nbentities; $i++) {
            $entity = array_pop($entities_ancestors);
            $query = "SELECT * FROM `".$this->getTable()."`
               WHERE `entities_id`='".$entity."'
               LIMIT 1";
            $result = $DB->query($query);
            if ($DB->numrows($result) != '0') {
               $data = $DB->fetch_assoc($result);
               if ($data[$fieldname] != '-1') {
                  return $data[$fieldname];
               }
            }
         }         
      } else {
         $data = $DB->fetch_assoc($result);
         if ($data[$fieldname] != '-1') {
            return $data[$fieldname];
         } else {
            $entities_ancestors = getAncestorsOf("glpi_entities", $entities_id);

            $nbentities = count($entities_ancestors);
            for ($i=0; $i<$nbentities; $i++) {
               $entity = array_pop($entities_ancestors);
               $query = "SELECT * FROM `".$this->getTable()."`
                  WHERE `entities_id`='".$entity."'
                  LIMIT 1";
               $result = $DB->query($query);
               if ($DB->numrows($result) != '0') {
                  $data = $DB->fetch_assoc($result);
                  if ($data[$fieldname] != '-1') {
                     return $data[$fieldname];
                  }
               }
            } 
         }
      }
   }
}

?>