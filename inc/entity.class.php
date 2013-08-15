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

class PluginBestmanagementEntity extends CommonDBTM {
   
   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName() {
      global $LANG;

      return '';
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
      if ($itemtype == 'Entity') {
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

   

   function showForm($entities_id, $options=array()) {
      global $LANG;

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
         $inpupt_YesNo['-1'] = $LANG['common'][102];
      }
      $inpupt_YesNo[0] = $LANG['choice'][0];
      $inpupt_YesNo[1] = $LANG['choice'][1];
      
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo $LANG["bestmanagement"]["propriete_pdf"][0]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray('entete', $inpupt_YesNo, array('value'=>$this->fields['entete']));
      echo "</td>";
      echo "<td>";
      echo $LANG["bestmanagement"]["propriete_pdf"][1]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      echo "<input type='file' />";
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      if ($this->fields['entete'] == '-1') {
            echo "<td colspan='2' class='green center'>";
            echo $LANG['common'][102]."&nbsp;:&nbsp;";
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
      echo $LANG["bestmanagement"]["propriete_pdf"][3]."&nbsp;:";
      echo "</td>";
      echo "<td>";
      
      echo "</td>";
      echo "<td>";
      echo $LANG["bestmanagement"]["propriete_pdf"][4]."&nbsp;:";
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