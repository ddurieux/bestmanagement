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

define('GLPI_ROOT', '../../..');

include (GLPI_ROOT . "/inc/includes.php");

require_once(GLPI_ROOT."/plugins/bestmanagement/lib/dompdf/dompdf_config.inc.php");
require_once(GLPI_ROOT."/plugins/bestmanagement/lib/dompdf/include/canvas.cls.php");
//require_once(GLPI_ROOT."/plugins/bestmanagement/lib/dompdf/include/cpdf_adapter.cls.php");
require_once(GLPI_ROOT."/plugins/bestmanagement/lib/dompdf/include/positioner.cls.php");
require_once(GLPI_ROOT."/plugins/bestmanagement/lib/dompdf/include/frame.cls.php");
require_once(GLPI_ROOT."/plugins/bestmanagement/lib/dompdf/include/frame_decorator.cls.php");
require_once(GLPI_ROOT."/plugins/bestmanagement/lib/dompdf/include/frame_reflower.cls.php");
//require_once(GLPI_ROOT."/plugins/bestmanagement/lib/dompdf/include/cached_pdf_decorator.cls.php");

foreach (glob(GLPI_ROOT.'/plugins/bestmanagement/lib/dompdf/include/*.php') as $file) {
   if (!strstr($file, 'cache')) {
      require_once($file);
   }
}

$html = "coucou";
  $dompdf = new DOMPDF();
  $dompdf->load_html($html);
  $dompdf->set_paper("A4");
  $dompdf->render();

//  $dompdf->stream("dompdf_out.pdf", array("Attachment" => false));

  exit(0);


?>