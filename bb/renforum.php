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

$old_name = strFromTo ($speech, "old name=&quot;", "&quot;");
$new_name = strFromTo ($speech, "new name=&quot;", "&quot;");
$new_desc = strFromTo ($speech, "new desc=&quot;", "&quot;");

/*
 *
 * script duties
 *
 */

if ((empty ($old_name)) || ( (empty ($new_name)) && (empty ($new_desc)) )) {

  /*
    if no double-quoted existing forum name was specified,
    or if neither new name nor new description are given, report intended syntax
  */

  $speech = "/renforum ($nick): syntax is: /renforum old name=\"actual name\" [new name=\"future name\"] [new desc=\"future description\"]";

}
else {

  if (strlen ($new_name) > 20) {

    /*
      forum names are limited to be relatively short:
      they may cause problems with the layout of links leading to them otherwise,
      especially when forum name is prepended to thread title and the screen is small
    */

    $speech = "/renforum ($nick): new name cannot be formed by more than 20 characters.";

  }
  else {

    /*
      renaming forum
    */

    lockSession ();

    $f = get ("forums/index", "name>$old_name", "id");

    if (empty ($f)) {

      $speech = "/renforum ($nick): forum &quot;$old_name&quot; doesn't exist.";

    }
    else {

      if (empty ($new_name)) {

        /*
          if no rename request, it's certainly a request to change the description only,
          because of former checks...
        */

        set ("forums/index", "id>$f", "desc", $new_desc);
        $speech = "/renforum ($nick): forum &quot;$old_name&quot; now described as &quot;$new_desc&quot;.";

      }
      else {

        /*
          grant rename only if no other forums with that name exist:
          it wouldn't cause malfunctions to have two forums with the same exact name,
          because they're refered by unique IDs, but it would surely cause confusion.
        */

        $existing_name = get ("forums/index", "name>$new_name", "");
        $no_such_forum_name = (empty ($existing_name)) ? true : false;

        if ($no_such_forum_name == true) {

          /*
            change forum name in index
          */

          set ("forums/index", "id>$f", "name", $new_name);

          /*
            if new description also given, also change forum description
          */

          if (!empty ($new_desc)) {

            set ("forums/index", "id>$f", "desc", $new_desc);

          }

          /*
            change forum name in affected recent discussions records:
            - load recent discussions archive building an array ($rd_a) out
              of its newline-delimited records, initialize filtered array ($rd_f)
              to hold the new version of the archive, scan $rd_a looking for all
              records where the forum ID ($_f) corresponds to this forum ID ($f),
              and finally write back the archive only if at least one record was
              effectively changed ($rd_m).
          */

          $rd_a = all ("forums/recdisc", asIs);
          $rd_f = array ();
          $rd_m = false;

          foreach ($rd_a as $r) {

            list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);

            if ($_f == $f) {

              $rd_m = true;
              $_F = $new_name;

            }

            $rd_f[] = implode (">", array ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t));

          }

          if ($rd_m == true) {

            asm ("forums/recdisc", $rd_f, asIs);

          }

          /*
            confirm that all went well...
          */

          $speech = "/renforum ($nick): forum &quot;$old_name&quot; renamed to &quot;$new_name&quot;.";

        }
        else {

          $error = true;
          $speech = "/renforum ($nick): there is already a forum with that name.";

        }

      }

    }

  }

}



?>
