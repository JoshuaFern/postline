<?php



/*************************************************************************************************

            HSP Postline

            PHP community engine
            version 2010.02.10 (10.2.10)

                                Copyright (C) 2003-2010 by Alessandro Ghignola
                                Copyright (C) 2003-2010 Home Sweet Pixel software

            This program is free software; you can redistribute it and/or modify
            it under the terms of the GNU General Public License as published by
            the Free Software Foundation; either version 2 of the License, or
            (at your option) any later version.

            This program is distributed in the hope that it will be useful,
            but WITHOUT ANY WARRANTY; without even the implied warranty of
            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
            GNU General Public License for more details.

            You should have received a copy of the GNU General Public License
            along with this program; if not, write to the Free Software
            Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

*************************************************************************************************/



define ("going", true);
define ("left_frame", true);

require ("settings.php");
require ("suitcase.php");
require ("sessions.php");
require ("template.php");

/*
 *
 * this script just prints the following warning message:
 * it's linked in place of "postform.php" when a thread or forum is locked
 *
 */

$msg = "LOCKED RECORD!//A moderator or an administrator has "
     . "locked this record; this prevents regular members from posting, editing "
     . "or deleting messages in this record. This is normally done to prevent "
     . "posting in forums or threads which contents concern the community as a whole, "
     . "and which might be kept clean, or to stop flames when appropriate. "
     . "As a further possibility, one entire forum may be locked, typically to park "
     . "there whichever threads are supposed to be archived forever in their actual state. "
     . "Finally, <b>closed</b> forums differ from <b>locked</b> forums in that members and "
     . "moderators may change existing threads, but not create new threads (and this "
     . "includes eventual splitting).";

/*
 *
 * form initialization
 *
 */

list ($frame_title, $frame_contents) = explode ("//", $msg);

$form = makeframe ($frame_title, $frame_contents, true);

/*
 *
 * template initialization
 *
 */

$postpanel = str_replace

  (

    array

      (

        "[FORM]",
        "[MODCP]",
        "[PERMALINK]"

      ),

    array

      (

        $form,
        "",
        $permalink

      ),

    $postpanel

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page upon showing or
 * hiding the chat frame, and for the online members list links (unless no-spy flag checked).
 *
 */

include ("setlfcookie.php");

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($postpanel));



?>
