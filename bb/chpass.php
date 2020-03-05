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



$em['request_from_management_account'] = "THIS OPERATION IS NOT POSSIBLE";
$ex['request_from_management_account'] =

        "The management account cannot switch to a different password by using this script. "
      . "The management account's password is the same used for the SQL database, and will "
      . "not be stored in the database itself in the form of an MD5 hash.";

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
 * management account password can't be changed from here
 *
 */

if ($id == 1) {

  unlockSession ();
  die (because ('request_from_management_account'));

}

/*
 *
 * getting simple password change parameters
 *
 */

$old_pass = strFromTo ($cli, "old=&quot;", "&quot;");
$new_pass = strFromTo ($cli, "new=&quot;", "&quot;");

if ((empty ($old_pass)) || (empty ($new_pass))) {

  $speech = "/chpass ($nick): syntax is: /chpass old=\"old password\" new=\"new password\".";

}
else {

  /*
    one last check:
    passwords must be between 6 and 20 characters long
  */

  $l = strlen ($new_pass);

  if (($l < 6) || ($l > 20)) {

    $speech = "/chpass ($nick): new password must be between 6 and 20 characters long.";

  }
  else {

    /*
      alright, changing password...
      obtain exclusive write access: database manipulation ahead
    */

    lockSession ();

    /*
      encrypt passwords as 128-bit RSA MD5 hashes
    */

    $old_pass = MD5 ($old_pass);
    $new_pass = MD5 ($new_pass);

    $db = "members" . intervalOf ($id, $bs_members);
    $mr = get ($db, "id>$id", "");

    $md5_pass = ($old_pass == valueOf ($mr, "pass")) ? true : false;

    $t_expire = intval (valueOf ($mr, "last_sent")) + 3600;
    $tmp_pass = (($t_expire > $epc) && ($old_pass == valueOf ($mr, "temp_pass"))) ? true : false;

    if (($md5_pass) || ($tmp_pass)) {

      /*
        save new password hash to member's record, replacing the former password hash
      */

      set ($db, "id>$id", "pass", $new_pass);

      /*
        confirm that the operation was successful
      */

      $speech = "/chpass ($nick): ok, remember to use the new password to login from now on.";

    }
    else {

      /*
        old password doesn't match!
      */

      $speech = "/chpass ($nick): OLD PASSWORD DOES NOT MATCH!";

    }

  }

}



?>
