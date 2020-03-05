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

require ("settings.php");
require ("suitcase.php");
require ("sessions.php");
require ("template.php");

$em['no_valid_target_name'] = "NO VALID TARGET NAME";
$ex['no_valid_target_name'] =

        "You did not specify a valid, i.e. non-empty, target member name.";

$em['no_account_for_target'] = "NO ACCOUNT FOUND FOR TARGET NAME";
$ex['no_account_for_target'] =

        "There seems to be no such account. Perhaps you mistyped the name.";

$em['not_allowed_for_staff'] = "NOT ALLOWED FOR STAFF MEMBERS' ACCOUNTS";
$ex['not_allowed_for_staff'] =

        "You cannot request a temporary password for the account of a staff member. "
      . "If you're part of the staff and you've lost your password, you have to find "
      . "another way to recover it. For example you may write a feedback form and "
      . "care to prove your identity by including undisclosed staff-level informations.";

$em['no_email_supplied'] = "THERE IS NO VALID EMAIL ON RECORD FOR THE TARGET ACCOUNT";
$ex['no_email_supplied'] =

        "The member referred by the given nickname did not specify a valid e-mail address "
      . "for this script to send a temporary password to.";

$em['spam_protection'] = "SPAM PROTECTION: NOT TWICE IN SAME WEEK";
$ex['spam_protection'] =

        "Sorry, you may try again after seven days from now.";

/*
 *
 * get parameters
 *
 */

$target = fget ("nick", 1000, "");      // target nickname

if (empty ($target)) {

  unlockSession ();
  die (because ('no_valid_target_name'));

}

/*
 *
 * locate and check validity of account record
 *
 */

$target = ucfirst (strtolower ($target));
$target_id = intval (get ("members/bynick", "nick>$target", "id"));
$db = "members" . intervalOf ($target_id, $bs_members);
$target_record = get ($db, "id>$target_id", "");

if (empty ($target_record)) {

  unlockSession ();
  die (because ('no_account_for_target'));

}

/*
 *
 * forbid use of this script when targeting staff members
 *
 */

$target_auth = valueOf ($target_record, "auth");
$is_target_admin = in_array ($target_auth, $admin_ranks);
$is_target_mod = in_array ($target_auth, $mod_ranks);

if (($is_target_admin) || ($is_target_mod)) {

  unlockSession ();
  die (because ('not_allowed_for_staff'));

}

/*
 *
 * check presence and validity of email field
 *
 */

$address = valueOf ($target_record, "email");

$c1 = (empty ($address)) ? true : false;
$c2 = (strpos ($address, "@") === false) ? true : false;
$c3 = (strpos ($address, ".") === false) ? true : false;
$c4 = (strlen ($address) < 5) ? true : false;

if (($c1) || ($c2) || ($c3) || ($c4)) {

  unlockSession ();
  die (because ('no_email_supplied'));

}

/*
 *
 * anti-spam check to protect target email
 *
 */

$last_sent = intval (valueOf ($target_record, "last_sent"));

if (($last_sent + 7*86400) > $epc) {

  unlockSession ();
  die (because ('spam_protection'));

}

/*
 *
 * generate temp. password, send email,
 * update temp. password and datetime of last delivery in member's record
 *
 */

$c = 0;
$d = "0123456789ABCDEFGHJKLMNPRSTXYZ";

$temp_pass = "";

while ($c < 20) {

  $i = mt_rand (0, 29);
  $temp_pass .= $d[$i];

  $c ++;

}

@mail (

    $address,

    "{$sitename} - forgotten password",

    "Your temporary password for account \"$target\" on $sitename is:\r\n"
  . "\r\n$temp_pass\r\n"
  . "\r\nThis message was sent to you because someone (ideally you) filled"
  . "\r\nyour nickname in the form at {$sitedomain}faq.php and clicked the"
  . "\r\n'send password' button."
  . "\r\nIt was only sent to you, so if you received this message by mistake,"
  . "\r\nyou may simply ignore it. To protect your mailbox and your account,"
  . "\r\nit is not possible to send more than one of these messages per week.",

    ""

);

lockSession ();

set ($db, "id>$target_id", "last_sent", strval ($epc));
set ($db, "id>$target_id", "temp_pass", MD5 (strtolower ($temp_pass)));

/*
 *
 * template initialization
 *
 */

$tp_expire = $epc + 3600;

$info =

  makeframe (

    "Password sent!", false, false

  )

  .

  makeframe (

    "temporary password is now in effect",

    "A temporary password has been sent to the email address given among the profile "
  . "informations for account &quot;$target&quot;. The said password will be a valid "
  . "alternative to the original password, in order to login using the given account "
  . "for all the next hour. The password will expire at " . gmdate ("H:i", $tp_expire)
  . ", server time. You are so pleased to retrieve the said email message as soon as "
  . "possible, login to your account, and change the original login password, taking "
  . "note of the new password. Use the &quot;chpass&quot; command, typed in the chat "
  . "input line, to set the new password. Refer to <a href=faq.php#q11>this page</a> "
  . "for detailed informations on how to change the login password.", false

  );

$generic_info_page = str_replace

  (

    array

      (

        "[INFO]",
        "[PERMALINK]"

      ),

    array

      (

        $info,
        $permalink

      ),

    $generic_info_page

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page upon showing or
 * hiding the chat frame, and for the online members list links (unless no-spy flag checked).
 *
 */

include ("setrfcookie.php");

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($generic_info_page));



?>
