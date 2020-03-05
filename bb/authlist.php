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

/*
 *
 * begin with an introduction to Postline's hierarchies
 *
 */

$info =

    makeframe (

         "Who is what?", false, false

      )

      .

    makeframe (

         "explanation of ranks and authorization levels",

         "Postline $plversion, the software controlling this bulletin board system, assigns "
      .  "different levels of authorization to trusted members basing on the member's \"rank\" "
      .  "field. If the rank literally matches a certain set of predefined words, the member is "
      .  "granted the corresponding access rights. Of course, only those members who are already "
      .  "ranked as administrators may change a members' ranks by editing their profile forms. "
      .  "Finally, note that any literal string, placed in someone's rank field, but which does "
      .  "<em>not</em> match any of the following:<br>"
      .  "<br>"
      .  "Admin ranks: <em>" . implode (", ", $admin_ranks) . "</em><br>"
      .  "Mod. ranks: <em>" . implode (", ", $mod_ranks) . "</em><br>"
      .  "<br>"
      .  "has no effect on the member's access rights (which remain those of a normal member), "
      .  "so there's no need for a regular member to be ranked as &#171;member&#187;, although "
      .  "that's the default literal rank for each and every newly registered account.", false

    );

/*
 *
 * manager:
 * - retrieve manager's record from members' database ($r);
 * - retrieve manager's nickname ($n) and make base62-encoded version for links ($e);
 * - check for the existence of an avatar image file corresponding to $e.[gif|jpg|png].
 *
 */

$r = get ("members" . intervalOf (1, $bs_members), "id>1", "");
$n = valueOf ($r, "nick");
$e = base62Encode ($n);
$a = "";

if     (@file_exists ("layout/images/avatars/$e.gif")) $a = "layout/images/avatars/$e.gif";
elseif (@file_exists ("layout/images/avatars/$e.jpg")) $a = "layout/images/avatars/$e.jpg";
elseif (@file_exists ("layout/images/avatars/$e.png")) $a = "layout/images/avatars/$e.png";

/*
 *
 * display informations about the community manager
 *
 */

$info .=

    makeframe (

         "service management",

         "<table width=100%>"
      .   "<td align=center>"
      .    "<span class=tt>The Community Manager...</span><br>"
      .    "Rank: manager, account's logical id: strictly 1<br>"
      .    "<img src=layout/images/quote.png width=24 height=24 style=margin:6px>"
      .   "</td>"
      .  "</table>"
      .  "<p align=justify>"
      .

    ((empty ($a))

      ?  ""

      :  "<a href=members.php?present=$e>"
      .   "<img align=left src=$a width=$avatarpixelsize height=$avatarpixelsize border=0 "
      .   "style=\"margin:0px 16px 8px 0px\">"
      .  "</a>"

      )

      .

    ((empty ($n))

      ?  "<em>WARNING:</em><br>"
      .  "No management account registered yet! This board is awaiting its ruler: "
      .  "the community manager must register an account, using the board's "
      .  "database password to authenticate his/her identity."

      :  "<a href=members.php?present=$e>$n</a>"
      .  ", community's founder and manager, has full administration rights over the boards: "
      .  "the access rights of a manager are exactly the same as those of an administrator, and "
      .  "furthermore, the manager's account is fully protected against deletion and access "
      .  "rights variations: admins can delete other admins, or undub them from their charge; "
      .  "the manager's rank, instead, cannot be changed, and the corresponding account cannot "
      .  "be deleted. There can be only one manager account: it's always the very first member "
      .  "who registers an account in a fresh installation of this community engine, so, this "
      .  "is typically the person who sets up the community in the first place, and often is "
      .  "someone who has direct access to the disk space of the server hosting the community."

      )

      .  "</p>", false

    );

/*
 *
 * administrators and moderators:
 * - create an array of ranks where the "manager" rank is excluded, since the manager is above.
 *
 */

$pure_admin_ranks = wArrayDiff ($admin_ranks, array ("manager"));

if (@filemtime ("widgets/sync/cron_stafflists") < ($epc - 3600)) {

  /*
    once per hour, regenerate lists to update this roster:
    but check again the condition that lead here, after locking, to ensure
    no other instance of this same script updated the lists in the meantime
  */

  lockSession ();

  if (@filemtime ("widgets/sync/cron_stafflists") < ($epc - 3600)) {

    @touch ("widgets/sync/cron_stafflists");

    $admins_list = array ();
    $mods_list = array ();

    /*
      scan every block found in members' database
    */

    $block = getintervals ("members");

    foreach ($block as $b) {

      /*
        retrieve all member records in block
      */

      list ($lo, $hi) = explode ("-", $b);
      $b = "members" . intervalOf (intval ($lo), $bs_members);

      $member = all ($b, makeProper);

      /*
        list every member whose authorization level (auth) matches
        one of those reserved to administrators ($pure_admin_ranks) or moderators
      */

      foreach ($member as $m) {

        $a = valueOf ($m, "auth");

        if (in_array ($a, $pure_admin_ranks)) {

          $admins_list[] = valueOf ($m, "nick");

        }

        if (in_array ($a, $mod_ranks)) {

          $mods_list[] = valueOf ($m, "nick");

        }

      }

    }

    /*
      save lists for later fast lookups
    */

    asm ("members/adm_list", $admins_list, asIs);
    asm ("members/mod_list", $mods_list, asIs);

  }
  else {

    $admins_list = all ("members/adm_list", asIs);
    $mods_list = all ("members/mod_list", asIs);

  }

}
else {

  /*
    for the rest of time, to keep server from scanning all members every time, use current lists
  */

  $admins_list = all ("members/adm_list", asIs);
  $mods_list = all ("members/mod_list", asIs);

}

$no_of_admins = count ($admins_list);
$no_of_mods = count ($mods_list);

/*
 *
 * sort administrators by name, initialize list accumulator
 *
 */

sort ($admins_list);
unset ($part);

/*
 *
 * generate HTML for administrators list
 *
 */

foreach ($admins_list as $i) {

  $part .= "<li class=list>"
        .   "<a href=members.php?present=" .  base62Encode ($i).  ">$i</a>"
        .  "</li>";

}

$info .=

    makeframe (

         "administrators",

         "<table width=100%>"
      .   "<td align=center>"
      .    "<span class=tt>The Administrators...</span><br>"
      .    "Ranks: " . implode (", ", $pure_admin_ranks) . "<br>"
      .    "<img src=layout/images/quote.png width=24 height=24 style=margin:6px>"
      .   "</td>"
      .  "</table>"
      .

    ((isset ($part))

      ?  "Apart from the manager, we have $no_of_admins administrators.<br>"
      .  "In alphabetical order:"
      .  "<ol>"
      .   $part
      .  "</ol>"

      :  "<p align=center>"
      .   "<em>Currently, this board has no administrators, apart from the manager.</em>"
      .  "</p>"

      )

      .  "<p align=justify>Administrators, or admins, are the people who can create, delete, "
      .  "rename, lock and nlock, whole forums. They can also delete members at will, change "
      .  "members' ranks, alter all aspects of their profile informations. They can ban members "
      .  "and entire IP address ranges from logging in and registering new accounts. They also "
      .  "have direct write access to the community database through a special interface.</p>"

      .  "<p class=ts align=right>"
      .   "Admins list last updated " . gmdate ("F d, Y H:i", @filemtime ("widgets/sync/cron_stafflists"))
      .  "</p>", false

    );

/*
 *
 * sort moderators by name, initialize list accumulator
 *
 */

sort ($mods_list);
unset ($part);

/*
 *
 * generate HTML for moderators list
 *
 */

foreach ($mods_list as $i) {

  $part .= "<li class=list>"
        .   "<a href=members.php?present=" .  base62Encode ($i).  ">$i</a>"
        .  "</li>";

}

$info .=

    makeframe (

         "moderators",

         "<table width=100%>"
      .   "<td align=center>"
      .    "<span class=tt>The Moderators...</span><br>"
      .    "Ranks: " . implode (", ", $mod_ranks) . "<br>"
      .    "<img src=layout/images/quote.png width=24 height=24 style=margin:6px>"
      .   "</td>"
      .  "</table>"
      .

    ((isset ($part))

      ?  "Complessively, we have $no_of_mods moderators.<br>"
      .  "In alphabetical order:"
      .  "<ol>"
      .   $part
      .  "</ol>"

      :  "<p align=center>"
      .   "<em>Currently, this board has no moderators.</em>"
      .  "</p>"

      )

      .  "<p align=justify>Moderators are the people who watch over other people for most of the "
      .  "time: their ideal duty is that of monitoring anything, everywhere, in search for "
      .  "broken rules. Should they spot something that's not quite fitting, they may <em>change "
      .  "others' posts</em>, kick out (and keep away) people for some time, delete or move "
      .  "whole threads, split them in more parts to resolve off-topicism. They can also change "
      .  "side-bars and edit locked threads, and inspect classified informations, none of which "
      .  "really spoiling your privacy, in an attempt to track multiple accounts ('clones').</p>"

      .  "<p class=ts align=right>"
      .   "Mods list last updated " . gmdate ("F d, Y H:i", @filemtime ("widgets/sync/cron_stafflists"))
      .  "</p>", false

    );

/*
 *
 * permalink initialization
 *
 */

$permalink = ($enable_permalinks)

  ? str_replace

      (

        "[PERMALINK]", $perma_url . "/" . $staffroster_keyword, $permalink_model

      )

  : "";

/*
 *
 * template initialization
 *
 */

$generic_info_page = str_replace

  (

    array (

      "[INFO]",
      "[PERMALINK]"

    ),

    array (

      $info,
      $permalink

    ),

    $generic_info_page

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page
 * upon showing or hiding the chat frame (via cookie: this also works for guests)
 *
 */

include ("setrfcookie.php");

/*
 *
 * saving navigation tracking informations for the online members list links
 * (unless no-spy flag checked)
 *
 */

if (($login == true) && ($nspy != "yes")) {

  $this_uri = substr ($_SERVER["REQUEST_URI"], 0, 100);

  if (getlsp ($id) != $this_uri) {

    setlsp ($id, $this_uri);

  }

}

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($generic_info_page));



?>
