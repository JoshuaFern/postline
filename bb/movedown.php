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
 * get target forum name:
 * beware that it's case-sensitive
 *
 */

$name = strFromTo ($speech, "&quot;", "&quot;");

if (empty ($name)) {

  /*
    if no double-quoted forum name was specified, report intended syntax
  */

  $speech = "/movedown ($nick): syntax is: /movedown \"forum name\".";

}
else {

  /*
    obtain exclusive write access: database manipulation ahead
    get forum ID corresponding to $name, check if a forum with that name is present
  */

  lockSession ();

  $fid = intval (get ("forums/index", "name>$name", "id"));

  if ($fid > 0) {

    /*
      forum exists with a non-null ID:
      retrieve forums' list ("forums/index"), move forum #ID down one place by exchanging
      its place with that of the entry that follows it (if no entry follows, output an error)
    */

    $forums = all ("forums/index", makeProper);

    $n = 0;
    $c = count ($forums);

    $changed = false;

    while ($n < $c) {

      /*
        numerically scan forums list array,
        until record's ID matches target ID...
      */

      if (intval (valueOf ($forums[$n], "id")) == $fid) {

        if ($n < ($c - 1)) {

          /*
            exchange
          */

          $x = $forums[$n + 1];
          $forums[$n + 1] = $forums[$n];
          $forums[$n] = $x;

          /*
            set flag to remember you've got to write back "forums/index"
          */

          $changed = true;

        }
        else {

          $speech = "/movedown ($nick): forum &quot;$name&quot; is already at bottom of list.";

        }

        break;

      }

      $n ++;

    }

    if ($changed) {

      /*
        write back forums' list, confirm that all went well
      */

      asm ("forums/index", $forums, makeProper);
      $speech = "/movedown ($nick): forum &quot;$name&quot; moved one place down in list.";

    }

  }
  else {

    $speech = "/movedown ($nick): forum &quot;$name&quot; doesn't exist.";

  }

}



?>
