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

class PluginBestmanagementToolbox {
   
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