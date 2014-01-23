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

class PluginSupportcontractToolbox {
   
   /**
    * Display hours
    */
   static function displayHours($val, $return=0) {

      $neg = ($val < 0) ? true : false;
      $val = round($val+0,2);
      $val *= ($neg) ? (-1) : 1;
      $h = floor($val); // heures

      $h += ($val < 0) ? 1 : 0;
      $m = round(($val - $h ) * 60); // minutes
      if ($m >= 0 
              && $m < 10) {
         $m = "0" . $m;
      }
      if ($return == 0) {
         echo (($neg) ? "-" : "") . $h . ":" . $m;
      } else {
         return (($neg) ? "-" : "") . $h . ":" . $m;
      }
   }
}

?>