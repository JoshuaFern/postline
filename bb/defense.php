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

include ("settings.php");
include ("suitcase.php");
include ("sessions.php");
include ("template.php");

$em['moderation_rights_required'] = "MODERATION RIGHTS REQUIRED";
$ex['moderation_rights_required'] =

      "The link you have been following leads to a section of this system which is restricted "
    . "to members which have been authenticated as moderators or administrators. If you might "
    . "not be seeing this message, please make sure that your login cookie is intact and that "
    . "you are accessing this site from the same domain you used to perform the login.";

$em['no_security_code_match'] = "NO SECURITY CODE MATCH";
$ex['no_security_code_match'] =

	"All operations performed through Postline scripts must be authenticated by a security "
      . "code submitted with the rest of the arguments of each page requests. This code varies "
      . "for each request. The reason why this error message is being presented to you is that "
      . "a request has been just made from your computer to perform significant operations via "
      . "one of these scripts, but no security code, or an outdated security code, was sent in "
      . "its arguments. It may result from different causes, among which, hitting the 'back' "
      . "button of your browser after having submitted a form, as well as interacting with two "
      . "or more instances of these scripts together (e.g. from two or more distinct browsers, "
      . "tabs, or computers). In the worst case, if you did not at all expect this message, it "
      . "may have been an attempt to trick you into executing commands in this script by means "
      . "of remote links, eventually disguising as something else: this is the main reason why "
      . "the security codes are implemented, and in such cases, this message means the attempt "
      . "has failed.";

/*
 *
 * this script is generally restricted to administrators and moderators
 *
 */

if (($is_admin == false) && ($is_mod == false)) {

  unlockSession ();
  die (because ('moderation_rights_required'));

}

/*
 *
 * process duties
 *
 */

if ($_POST["submit"] == "fire") {

  /*
    security confirmation code, please!
    it's really important here...
  */

  $code = intval (fget ("code", 1000, ""));

  if ($code != getcode ("pst")) {

    unlockSession ();
    die (because ('no_security_code_match'));

  }

  /*
    submitting changes to the CDP fields:
    - requires administration access rights.
  */

  if ($is_admin == false) {

    $report = "ACCESS DENIED!<br>"
	    . "Administrative rights required.";

  }
  else {

    /*
      obtain exclusive write access to database
    */

    lockSession ();

    /*
      Notepad/BML/BSL/KGL/WFL management:
      - each field can be upto 100 Kb.
    */

    $anp = fget ("anp", 102400, "\n");	// Administration Note Pad
    $bml = fget ("bml", 102400, "\n");	// Banned Members List
    $bsl = fget ("bsl", 102400, "\n");	// Banned Subnets List
    $kgl = fget ("kgl", 102400, "\n");	// Known Guests List
    $wfl = fget ("wfl", 102400, "\n");	// Word Filters List

    /*
      in BML, BSL, KGL and WFL, remove any adjacent carriage returns:
      no void lines are allowed there...
    */

    $bml = preg_replace ("/(\n)+/", "\n", $bml);
    $bsl = preg_replace ("/(\n)+/", "\n", $bsl);
    $wfl = preg_replace ("/(\n)+/", "\n", $wfl);

    /*
      but still, append a terminal carriage return if there isn't one at the end of the
      text, to facilitate addition of fields at the end of each list (in text areas)...
    */

    if (substr ($anp, -1, 1) != "\n") $anp .= "\n";
    if (substr ($bml, -1, 1) != "\n") $bml .= "\n";
    if (substr ($bsl, -1, 1) != "\n") $bsl .= "\n";
    if (substr ($kgl, -1, 1) != "\n") $kgl .= "\n";
    if (substr ($wfl, -1, 1) != "\n") $wfl .= "\n";

    /*
      write back modified ANP, BML, BSL, KGL and WFL
    */

    writeTo ("stats/anp", $anp);
    writeTo ("stats/bml", $bml);
    writeTo ("stats/bsl", $bsl);
    writeTo ("stats/kgl", $kgl);
    writeTo ("stats/wfl", $wfl);

    /*
      kicking banned members while they may still be online:
      - prepare $bml as the array of nicknames written in the BML field, converted to lowercase;
      - prepare $oml as the array of records listed as all actual online sessions.
    */

    $bml = explode ("\n", strtolower ($bml));
    $oml = all ("stats/sessions", makeProper);

    foreach ($oml as $m) {

      $n = valueOf ($m, "nick");
      $r = valueOf ($m, "auth");

      /*
	- is $n (session nickname) within $bml (banlist)?
	- is $r (session rank) not an administrative rank? (admins can't be banned)
      */

      if (in_array (strtolower ($n), $bml)) {

	if (in_array ($r, $admin_ranks) == false) {

	  /*
	    erase session record
	  */

	  set ("stats/sessions", "nick>$n", "", "");

	  /*
	    set kick on target session IP address,
	    preventing sudden re-registration to circumvent the ban
	  */

	  kick (valueOf ($m, "ip"));

	}

      }

    }

    /*
      clearing ignore lists when banning major members (if not already done):
      don't worry if it's made unconditionally for all members in the banlist,
      function "clear_mm_ignorelist" will take care of checking if it's the case.
    */

    foreach ($bml as $m) {

      if (!empty ($m)) {

	$i = intval (get ("members/bynick", "nick>" . ucfirst ($m), "id"));

	clear_mm_ignorelist ($i);

      }

    }

    /*
      managing ban override password
    */

    $bop = fget ("bop", 20, "");

    set ("stats/counters", "counter>bop", "", "<counter>bop<value>$bop");

    /*
      Global Community Locker and Majority Ban state controls.
    */

    if (isset ($_POST["mb"])) {

      set ("stats/counters", "counter>majorityban", "", "<counter>majorityban<state>on");

    }
    else {

      set ("stats/counters", "counter>majorityban", "", "<counter>majorityban<state>off");

    }

    if (isset ($_POST["mn"])) {

      set ("stats/counters", "counter>mbnotify", "", "<counter>mbnotify<state>on");

    }
    else {

      set ("stats/counters", "counter>mbnotify", "", "<counter>mbnotify<state>off");

    }

    if (isset ($_POST["wd"])) {

      set ("stats/counters", "counter>community_locker", "", "<counter>community_locker<state>on");

    }
    else {

      set ("stats/counters", "counter>community_locker", "", "<counter>community_locker<state>off");

    }

    /*
      serverbot activation
    */

    if (isset ($_POST["sb"])) {

      set ("stats/counters", "counter>serverbot", "", "<counter>serverbot<state>on");
      set ("stats/sessions", "ip>7f000000", "",
	"<id>0<ip>7f000000<nick>$servername<auth>manager<beg>2000000000<chatflag>on<");

    }
    else {

      set ("stats/counters", "counter>serverbot", "", "<counter>serverbot<state>off");
      set ("stats/sessions", "ip>7f000000", "", "");

    }

    /*
      Global System Mainteinance Lock control:
      - requires service management access rights (account ID must be 1);
      - when triggered, it will also logout everyone instantly, removing all sessions.
    */

    if ($id == 1) {

      if (isset ($_POST["ml"])) {

	@touch ('widgets/sync/system_locker');

	writeTo ("stats/sessions", voidString);
	writeTo ("stats/guests", voidString);

      }
      else {

	@unlink ('widgets/sync/system_locker');

      }

    }

    /*
      Clear-All-Ignore-Lists (CAIL) control:
      - parse members' database, clear "ignorelist" and "ignorecount" in all records.
    */

    $cail_1 = (isset ($_POST["cail_1"])) ? true : false;
    $cail_2 = (isset ($_POST["cail_2"])) ? true : false;
    $cail_3 = (isset ($_POST["cail_3"])) ? true : false;

    if (($cail_1) && ($cail_2) && ($cail_3)) {

      /*
	this scansion may take a while, but it's the only way...
      */

      $blocks = getIntervals ("members");

      foreach ($blocks as $mdb_file) {

	list ($lo, $hi) = explode ("-", $mdb_file);
	$mdb_file = "members" . intervalOf (intval ($lo), $bs_members);

	$mdb_record = all ($mdb_file, makeProper);
	$mdb_records = array ();

	foreach ($mdb_record as $r) {

	  $r = fieldSet ($r, "ignorelist", "");
	  $r = fieldSet ($r, "ignorecount", "");

	  $mdb_records[] = $r;

	}

	asm ($mdb_file, $mdb_records);

	write_to ("stats/ignorers", voidString);	// clear optimization list, see profile.php

      }

      /*
	this event is worth logging
      */

      logwr ("Warning: all ignore lists have been cleared by administrative request.", lw_persistent);

    }

    /*
      confirm that changes have been applied...
    */

    $report = "Ok, all fields updated!";

  }

}
else {

  /*
    if it's not a submission, provide links for viewing and leaving an example form...
  */

  if (isset ($_GET["sample"])) {

    $report = "<a href=defense.php>quit sample configuration</a>";

  }
  else {

    $report = "<a href=defense.php?sample=1>show sample configuration</a>";

  }

  $report .= ",<br><a target=pan href=faq.php#q{$cdp_faq_number}>see F.A.Q. for details...</a>";

}

/*
 *
 * get defense status or compile example form, depending on request
 *
 */

if (isset ($_GET["sample"])) {

  /*
    fill example form
  */

  $anp = "Notes on the banned members list: nicks are case insensitive, "
       . "there cannot be comments there, and administrators can't really "
       . "be banned: it is possible to place their nicks there, "
       . "but it has no effect...";

  $bml = "Alex\n"
       . "Raptorjedi\n"
       . "Stargazer\n"
       . "Ginja Ninja\n"
       . "Etc\n\n";

  $bsl = "3e5e (first 4 digits of IP)\n"
       . "123ab (first 5 digits of IP)\n"
       . "7 (very first digit of IP)\n"
       . "7f123456 (full address given)\n"
       . "d5 (this is &quot;Trollname&quot;)\n\n"
       . "NOTES: to see a member's IP, go to "
       . "the member's profile and select the "
       . "magnifier to inspect his fingerprints, "
       . "among which the IP is given: now, what "
       . "you write here are sort of &quot;prefixes&quot; "
       . "to the range of IP addresses you're going to ban: "
       . "thus, the less digits you write, the more potential "
       . "members you will ban, so be careful. Comments must "
       . "strictly FOLLOW the subnet's digits, and they must "
       . "be placed between parenthesis.";

  $kgl = "7f00 (Localhost)\n"
       . "50a (InktomiSearch)\n"
       . "7bc0 (MSNBot)\n"
       . "44f1 (GoogleBot)\n"
       . "40f2 (Looksmart)\n"
       . "etc...\n\n";

  $wfl = "someword = someotherword\n";

  $bop = "alakazam";

  $wd_checked = chr (32) . "checked";
  $mb_checked = chr (32) . "checked";
  $mn_checked = chr (32) . "checked";
  $sb_checked = chr (32) . "checked";
  $ml_checked = chr (32) . "checked";

  $cail_sample = chr (32) . "checked";

}
else {

  /*
    get all CDP fields for showing them:
    - only, mask the "ban override password" to moderators
  */

  $anp = readFrom ("stats/anp");
  $bml = readFrom ("stats/bml");
  $bsl = readFrom ("stats/bsl");
  $kgl = readFrom ("stats/kgl");
  $wfl = readFrom ("stats/wfl");

  $bop = get ("stats/counters", "counter>bop", "value");

  $wd_checked = (get ("stats/counters", "counter>community_locker", "state") == "on") ? chr (32) . "checked" : "";
  $mb_checked = (get ("stats/counters", "counter>majorityban", "state") != "off") ? chr (32) . "checked" : "";
  $mn_checked = (get ("stats/counters", "counter>mbnotify", "state") == "on") ? chr (32) . "checked" : "";
  $sb_checked = (get ("stats/counters", "counter>serverbot", "state") == "on") ? chr (32) . "checked" : "";
  $ml_checked = (@file_exists ('widgets/sync/system_locker')) ? chr (32) . "checked" : "";

  $cail_sample = "";

}

/*
 *
 * form initialization
 *
 */

$form =

      makeframe (

	"Defense And Control Panel", false, false

      )

      .

      makeframe (

	"information", $report, true

      )

      . "<table width=$pw>"
      . "<form action=defense.php enctype=multipart/form-data method=post>"
      .  "<td height=40 class=inv align=center>"
      .   "administration notepad:<br>"
      .   "<small>you may keep short notes here</small>"
      .  "</td>"
      . "</table>"

      . $inset_bridge

      . $opcart
      .  "<td>"
      .   "<textarea name=anp style=width:{$iw}px;height:320px>$anp</textarea>"
      .  "</td>"
      . $clcart

      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=40 class=inv align=center>"
      .   "banned members list:<br>"
      .   "<small>write one nickname per line</small>"
      .  "</td>"
      . "</table>"

      . $inset_bridge

      . $opcart
      .  "<td>"
      .   "<textarea name=bml style=width:{$iw}px;height:120px>$bml</textarea>"
      .  "</td>"
      . $clcart

      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=60 class=inv align=center>"
      .   "banned ip/subnets list:<br>"
      .   "<small>one hexadecimal value per line<br>"
      .   "(comments between parenthesis)</small>"
      .  "</td>"
      . "</table>"

      . $inset_bridge

      . $opcart
      .  "<td>"
      .   "<textarea name=bsl style=width:{$iw}px;height:120px>$bsl</textarea>"
      .  "</td>"
      . $clcart

      . $inset_shadow

      . "<a name=knownguests>"
      . "</a>"

      . "<table width=$pw>"
      .  "<td height=60 class=inv align=center>"
      .   "known guest ip/subnets:<br>"
      .   "<small>one hexadecimal value per line<br>"
      .   "(guest names between parenthesis)</small>"
      .  "</td>"
      . "</table>"

      . $inset_bridge

      . $opcart
      .  "<td>"
      .   "<textarea name=kgl style=width:{$iw}px;height:120px>$kgl</textarea>"
      .  "</td>"
      . $clcart

      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=40 class=inv align=center>"
      .   "simple word replacement<br>"
      .   "<small>(applied to chat frame only)</small>"
      .  "</td>"
      . "</table>"

      . $inset_bridge

      . $opcart
      .  "<td>"
      .   "<textarea name=wfl style=width:{$iw}px;height:120px>$wfl</textarea>"
      .  "</td>"
      . $clcart

      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=20 class=inv align=center>"
      .   "ban override password"
      .  "</td>"
      . "</table>"

      . $inset_bridge

      . $opcart
      .  "<td class=in>"
      .   "<input class=sf style=width:{$iw}px type=text name=bop maxlength=20 value=\"$bop\">"
      .  "</td>"
      . $clcart

      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=20 class=inv align=center>"
      .   "clear all ignore lists"
      .  "</td>"
      . "</table>"

      . $inset_bridge

      . $opcart
      .  "<td class=in align=center style=padding:4px>"
      .   "<span title=\"warn the community and check ALL THE THREE BOXES to do this\">"
      .   "<input type=checkbox name=cail_1$cail_sample>&nbsp;Yes&nbsp;&nbsp;"
      .   "<input type=checkbox name=cail_2$cail_sample>&nbsp;Yes&nbsp;&nbsp;"
      .   "<input type=checkbox name=cail_3$cail_sample>&nbsp;Yes</span>"
      .  "</td>"
      . $clcart

      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=20 class=inv align=center>"
      .   "miscellaneous flags"
      .  "</td>"
      . "</table>"

      . $inset_bridge

      . $opcart
      .  "<td class=in style=padding:4px>"
      .   "<span title=\"enables the server to chat, simulating an online member\">"
      .   "&nbsp;<input type=checkbox name=sb$sb_checked>&nbsp;serverbot ($servername) on</span><br>"
      .   "<span title=\"enables or disables the &quot;majority ban&quot; feature\">"
      .   "&nbsp;<input type=checkbox name=mb$mb_checked>&nbsp;enable majority ban</span><br>"
      .   "<span title=\"if majority ban is enabled, warn the staff but do not ban\">"
      .   "&nbsp;<input type=checkbox name=mn$mn_checked>&nbsp;only warn staff, don't ban</span><br>"
      .   "<span title=\"CLOSES the community to any new members' registration until new order\">"
      .   "&nbsp;<input type=checkbox name=wd$wd_checked>&nbsp;disable new registrations</span>"
      .  "</td>"
      . $clcart

      . $inset_shadow

      . "<table width=$pw>"
      .  "<td height=40 class=alert align=center>"
      .   "management only<br>"
      .   "<small>(enables all maintenance tasks)</small>"
      .  "</td>"
      . "</table>"

      . $inset_bridge

      . $opcart
      .  "<td class=in style=padding:4px>"
      .   "<span title=\"globally locks access to the system: only the community manager can alter this flag\">"
      .   "&nbsp;<input type=checkbox name=ml$ml_checked>&nbsp;system maintenance lock</span>"
      .  "</td>"
      . $clcart

      . $inset_bridge

      . "<table width=$pw>"
      .  "<td height=20 class=inv align=center>"
      .   "general maintenance"
      .  "</td>"
      . "</table>"

      . $inset_bridge

      . $opcart
      .  "<td class=ls>"
      .       "<a class=ll target=pan href=0_patch_logs.php>Chat Log Style Patcher</a>"
      .   "<br><a class=ll target=pan href=0_defrag_members.php>Members Defragmentator</a>"
      .  "</td>"
      . $clcart

      . $inset_bridge

      . "<table width=$pw>"
      .  "<td height=20 class=inv align=center>"
      .   "rollback synchronization"
      .  "</td>"
      . "</table>"

      . $inset_bridge

      . $opcart
      .  "<td class=ls>"
      .       "<a class=ll target=pan href=0_rebuild_cd_folders.php>C-Disk Folders Builder</a>"
      .   "<br><a class=ll target=pan href=0_cleanup_mailboxes.php>PM Mailbox Clean-up</a>"
      .   "<br><a class=ll target=pan href=0_recover_mailboxes.php>PM Mailbox Recovery</a>"
      .   "<br><a class=ll target=pan href=0_rebuild_recdisc.php>Recent Posts Collector</a>"
      .   "<br><a class=ll target=pan href=0_recover_orphan_posts.php>Orphan Posts Recovery</a>"
      .   "<br><a class=ll target=pan href=0_sync_threads.php>Missing Posts Removal</a>"
      .   "<br><a class=ll target=pan href=0_make_thread_lists.php>Thread Lists Generator</a>"
      .   "<br><a class=ll target=pan href=0_sync_forum_indices.php>Forum Indices Patcher</a>"
      .   "<br><a class=ll target=pan href=0_sync_thread_counts.php>Thread Counters Patcher</a>"
      .   "<br><a class=ll target=pan href=0_create_ignorerslist.php>Ignorers List Generator</a>"
      .  "</td>"
      . $clcart

      . $inset_shadow

      . "<table width=$pw>"
      .  "<td>"
      .   "<table width=$pw>"
      .    "<tr>"
      .

    ((isset ($_GET["sample"]))

      ?     "<td>"
      .      "<span title=\"DISCARD CHANGES AND RELOAD THE C.D.P.\">"
      .       "<input class=ky type=submit name=submit style=width:" . (intval ($pw/3*2) + 1) . "px value=cancel>"
      .      "</span>"
      .     "</td>"

      :     "<td>"
      .      "<span title=\"APPLY CHANGES - REQUIRES ADMINISTRATION RIGHTS\">"
      .       "<input class=su type=submit name=submit style=width:" . (intval ($pw/3) - 2) . "px value=fire>"
      .

    (($is_admin)

      ?       "<input type=hidden name=code value=" . setcode ("pst") . ">"
      :       ""

      )

      .      "</span>"
      .     "</td>"
      .     "<td width=" . (intval ($pw/3)) . " align=right>"
      .      "<span title=\"DISCARD CHANGES AND RELOAD THE C.D.P.\">"
      .       "<input class=ky type=submit name=submit style=width:" . (intval ($pw/3)) . "px value=cancel>"
      .      "</span>"
      .     "</td>"

    )

      . "</form>"
      . "<form action=mstats.php>"

      .     "<td align=right>"
      .      "<span title=\"GO BACK TO LOGIN PANEL\">"
      .       "<input class=ky type=submit name=submit style=width:" . (intval ($pw/3) - 4) . "px value=exit>"
      .      "</span>"
      .     "</td>"

      . "</form>"

      .    "</tr>"
      .   "</table>"
      .  "</td>"
      . "</table>"

      . $inset_shadow;

/*
 *
 * template initialization
 *
 */

$kprefs = str_replace

  (

    array (

      "[FORM]",
      "[PERMALINK]"

    ),

    array (

      $form,
      $permalink

    ),

    $kprefs

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page upon showing or
 * hiding the chat frame, and ONLY for that: this URL is confidential and it's not tracked.
 *
 */

include ("setlfcookie.php");

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($kprefs));



?>
