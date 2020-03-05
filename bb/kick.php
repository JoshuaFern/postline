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



$em['moderation_rights_required'] = "MODERATION RIGHTS REQUIRED";
$ex['moderation_rights_required'] =

        "The link you have been following leads to a section of this system which is restricted "
      . "to members which have been authenticated as moderators or administrators. If you might "
      . "not be seeing this message, please make sure that your login cookie is intact and that "
      . "you are accessing this site from the same domain you used to perform the login.";

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
 * this command is generally restricted to moderators and administrators
 *
 */

if (($is_admin == false) && ($is_mod == false)) {

  unlockSession ();
  die (because ('moderation_rights_required'));

}

/*
 *
 * get target nickname
 *
 */

$target = ucfirst (strFromTo ($speech, "&quot;", "&quot;"));

/*
 *
 * script duties
 *
 */

if (empty ($target)) {

  /*
    if no double-quoted member nickname was specified, report intended syntax
  */

  $speech = "/kick ($nick): syntax is: /kick \"target's nickname\" [optional duration in minutes]";

}
else {

  /*
    get kick delay $d:
    - convert to seconds;
    - cannot be negative;
    - cannot be greater than $maxkickdelay defined in "settings.php".
  */

  $d = trim (strFromTo (strtolower ($speech) . "<", strtolower ($target) . "&quot;", "<"));

  if ((empty ($d)) || (is_numeric ($d))) {

    $d = (empty ($d)) ? 5 * 60 : abs (intval ($d)) * 60;        // default is 5 minutes
    $d = ($d <= $maxkickdelay) ? $d : $maxkickdelay;            // normalize against maximum

    /*
      obtain exclusive write access: database manipulation ahead
      get member record corresponding to $target, check if it's no administrator
    */

    lockSession ();

    $i = intval (get ("members/bynick", "nick>$target", "id"));
    $db = "members" . intervalOf ($i, $bs_members);
    $record = get ($db, "id>$i", "");

    if (!empty ($record)) {

      /*
        basing on administrative ranks defined in settings.php, no regular moderator may kick
        an aministrator, yet admins may kick other admins, or even kick themselves out... :)
      */

      $is_target_manager = (intval (valueOf ($record, "id")) == 1) ? true : false;
      $is_target_admin = in_array (valueOf ($record, "auth"), $admin_ranks);

      if (($is_target_manager == false) && (($is_target_admin == false) || ($is_admin == true))) {

        /*
          confirmation message
        */

        $speech = "$target kicked off by $nick for " . ((int) ($d / 60)) . " minutes.";

        /*
          update member record to account for the kick, which will be later checked
          to prevent the member from logging in again until the delay expires...
        */

        set ($db, "id>$i", "kicked_on", $epc);  // remember it's being kicked today
        set ($db, "id>$i", "kicked_for", $d);   // remember it's been kicked for this long

        /*
          erase session record from current sessions table,
          if target is logged in (visual logout)
        */

        set ("stats/sessions", "nick>$target", "", "");

        if ($nick == $target) {

          /*
            if someone kicks himself out, he/she would find his/her nickname still logged
            in, despite the above statement to erase the session, because "pquit()" at the
            end of "input.php" (which is the script that includes this script, on request)
            would trigger the session build/update code (see pquit in suitcase.php): to
            avoid this, the constants "disable_session_creation" and "disable_session_update" can be
            defined to signal that piece of code that the session must not be re-built or updated...
          */

          define ("disable_session_creation", true);
          define ("disable_session_update", true);

        }

        /*
          get $target_ip from last login IP in member record,
          add IP to kicks list...
        */

        $target_ip = valueOf ($record, "logip");

        if (!empty ($target_ip)) {

          kick ($target_ip);

        }

      }
      else {

        if ($is_target_manager == false) {

          $speech = "/kick ($nick): not permitted, member \"$target\" is an administrator.";

        }
        else {

          $speech = "/kick ($nick): not permitted, the community manager cannot be kicked out.";

        }

      }

    }
    else {

      $speech = "/kick ($nick): member \"$target\" doesn't appear to exist.";

    }

  }
  else {

    $speech = "/kick ($nick): need numeric argument as logout duration (minutes).";

  }

}



?>
