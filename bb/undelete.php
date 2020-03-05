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



$em['admin_rights_required'] = "ADMINISTRATION RIGHTS REQUIRED";
$ex['admin_rights_required'] = "Use of this command is generally restricted to administrators.";

/*
 *
 * this is a command-line script, to be executed from the chat input line:
 *
 * from_cli is defined only when the script is launched because its name has been typed into
 * the chat prompt/command line, and ONLY if the member was acknowledged (is logged in, and
 * has a precise identity given by $nick, and a logical id $id). This is very important.
 *
 */

if (!defined ("from_cli")) {

  exit ("NO STANDALONE EXECUTION");

}

/*
 *
 * this command is generally restricted to administrators
 *
 */

if ($is_admin == false) {

  unlockSession ();
  die (because ('admin_rights_required'));

}

/*
 *
 * getting parameters
 *
 */

$name = strFromTo ($speech, "&quot;", "&quot;");

if (empty ($name)) {

  /*
    if no doublequoted forum name provided, show intended syntax
  */

  $speech = "/undelete ($nick): syntax is: /undelete &quot;forum name&quot;.";

}
else {

  /*
    recovering a forum's entry that had been trashed using "delforum":
    this is very easy... it only removes the "istrashed" field from the forum's entry...
  */

  lockSession ();

  $forum_record = get ("forums/index", "name>$name", "");

  if (empty ($forum_record)) {

    $speech = "/undelete ($nick): forum &quot;$name&quot; not found.";

  }
  else {

    if (valueOf ($forum_record, "istrashed") != "yes") {

      $speech = "/undelete ($nick): forum &quot;$name&quot; needs not be recovered.";

    }
    else {

      set ("forums/index", "name>$name", "istrashed", "");

      $speech = "/undelete ($nick): forum &quot;$name&quot; has been recovered.";

    }

  }

}



?>
