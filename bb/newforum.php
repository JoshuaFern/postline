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
 * get new forum name and description
 * ($speech preserves letters case)
 *
 */

$name = strFromTo ($speech, "name=&quot;", "&quot;");
$desc = strFromTo ($speech, "desc=&quot;", "&quot;");

/*
 *
 * get optional type
 * ($cli forces letters lowercase)
 *
 */

$type = strFromTo ($cli, "type=&quot;", "&quot;");

if ((empty ($name)) || (empty ($desc))) {

  /*
    if no forum name or description was specified, report intended syntax
  */

  $speech = "/newforum ($nick): syntax is: /newforum name=\"forum name\" desc=\"description\" [type=\"public|hidden\"].";

}
else {

  if (strlen ($name) > 20) {

    /*
      forum names are limited to be relatively short:
      they may cause problems with the layout of links leading to them otherwise,
      especially when forum name is prepended to thread title and the screen is small
    */

    $speech = "/newforum ($nick): forum's name cannot be formed by more than 20 characters.";

  }
  else {

    /*
      forum type may be "public", "hidden" or unspecified (an empty string):
      setting the type to "hidden" on creation is the same as creating a public forum
      and then hiding it immediately with a /delforum command, only difference is that
      this way, it all gets done in one step and the new forum doesn't get a chance
      to be seen by non-moderators (which is the intended use of the type argument).
    */

    $valid_types = array ("public", "hidden", "");

    if (!in_array ($type, $valid_types)) {

      $speech = "/newforum ($nick): type, when specified, may only be \"public\" or \"hidden\".";

    }
    else {

      /*
        creating new forum:
        the name must be unique because it will be used to refer to the forum
        with other commands (such as /moveup, /movedown, /delforum, /lose, etc...)
      */

      lockSession ();

      $existing_name = get ("forums/index", "name>$name", "");
      $no_such_forum_name = (empty ($existing_name)) ? true : false;

      if ($no_such_forum_name) {

        /*
          calculate new forum ID starting from last generated ID
        */

        $fid = intval (get ("stats/counters", "counter>newforums", "count")) + 1;
        $existing_record = get ("forums/index", "id>$fid", "");

        if (empty ($existing_record)) {

          /*
            assemble new forum's record to be saved in "forums/index"
          */

          $r = "<id>$fid<name>$name<desc>$desc";
          $r .= ($type == "hidden") ? "<istrashed>yes" : "";

          /*
            save record, update forum ID counter, confirm successful creation
          */

          set ("forums/index", "", "", $r);
          set ("stats/counters", "counter>newforums", "", "<counter>newforums<count>$fid");

          $speech = "/newforum ($nick): new forum creation succeeded for &quot;$name&quot;.";

        }
        else {

          $speech = "/newforum ($nick): forum ID conflict, possible problem with counters.";

        }

      }
      else {

        $speech = "/newforum ($nick): a forum called &quot;$name&quot; already exists.";

      }

    }

  }

}



?>
