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

class PluginSupportcontractContractPDF extends PluginPdfCommon {


   function __construct(CommonGLPI $obj=NULL) {

      $this->obj = ($obj ? $obj : new PluginSupportcontractContract());
   }


   function defineAllTabs($options=array()) {

      $a_tabs = parent::defineAllTabs($options);
      unset($a_tabs['_main_']);
      unset($a_tabs['PluginSupportcontractContract$13']);
      unset($a_tabs['PluginSupportcontractContract$14']);
      unset($a_tabs['PluginSupportcontractContract$15']);
      
      
      unset($a_tabs['PluginSupportcontractContract$11']);
      unset($a_tabs['PluginSupportcontractContract$12']);
      unset($a_tabs['PluginSupportcontractContract$16']);
      unset($a_tabs['PluginSupportcontractContract$17']);
      return $a_tabs;
   }


   static function displayTabContentForPDF(PluginPdfSimplePDF $pdf, CommonGLPI $item, $tab) {

      switch ($tab) {
         case 'PluginSupportcontractContract$10' :
            $item->showSummaryPDF($pdf);
            break;

         default :
            return false;
      }
      return true;
   }
}

?>