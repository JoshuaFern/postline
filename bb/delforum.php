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
 * get parameters
 *
 */

$name = strFromTo ($speech, "&quot;", "&quot;");

if (empty ($name)) {

  /*
    if no double-quoted forum name was specified, report intended syntax
  */

  $speech = "/delforum ($nick): syntax is: /delforum \"forum name\".";

}
else {

  /*
    deleting a forum's entry:
    - requires user to be ranked as an administrator;
    - and, it's not really deleted. It's "trashed": put into a hidden state where regular
      members don't see the forum anymore. This makes it possible to recover the forum if
      deleted by mistake or if the admin changed idea, using the "undelete" command.
      Effectively deleting all posts and threads in a forum can be very slow if the target
      forum's contributions to the database are significant, but it can be attempted using
      another command, "lose", working only on forums that HAVE BEEN ALREADY trashed.
      Such trashed, hidden forums, may be used for moderator-only discussions.
  */

  /*
    obtain exclusive write access: database manipulation ahead
    get forum ID corresponding to $name, check if a forum with that name is present
  */

  lockSession ();

  $f = intval (get ("forums/index", "name>$name", "id"));

  if ($f > 0) {

    /*
      forum exists: set "istrashed" flag in its record
    */

    set ("forums/index", "id>$f", "istrashed", "yes");

    /*
      removing forum's recorded hints in recent discussions' archive:
      - load recent discussions archive ($rd_o), build an array ($rd_a) out
        of its newline-delimited records, initialize filtered string ($rd_f)
        to hold the new version of the archive, scan $rd_a looking for all
        records where the forum ID ($_f) corresponds to this forum ID ($f),
        and finally write back the archive only if at least one record was
        effectively filtered out ($rd_m).
    */

    $rd_a = all ("forums/recdisc", asIs);
    $rd_f = array ();
    $rd_m = false;

    foreach ($rd_a as $r) {

      list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);

      if ($_f == $f) {

        $rd_m = true;

      }
      else {

        $rd_f[] = implode (">", array ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t));

      }

    }

    if ($rd_m) {

      asm ("forums/recdisc", $rd_f, asIs);

    }

    /*
      confirm that all went well...
    */

    $speech = "/delforum ($nick): forum \"$name\" has been trashed.";

  }
  else {

    $speech = "/delforum ($nick): forum \"$name\" doesn't exist.";

  }

}



?>
