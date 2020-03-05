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
 * respond to "mtr" and "mtu" requests for logged-in members
 *
 */

if ($login == true) {

  /*
    on request, marks all threads holding recent discussions as read,
    by copying all recdisc's message IDs to the member's entry in VRD table.
  */

  if (fget ("mtr", 1, "") == "y") {

    /*
      building array of message IDs representing all recent posts:
      - load recent discussions archive building an array ($rd_a) out of its
        newline-delimited records, initialize filtered array ($rd_f) to hold
        only the message ID fields ($_m) of each record in $rd_a.
    */

    $rd_a = all ("forums/recdisc", asIs);
    $rd_f = array ();

    foreach ($rd_a as $r) {

      list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);
      $rd_f[] = $_m;

    }

    /*
      set VRD table entry for this member ID,
      forget the arguments to this URL so it won't repeat on reloads.
    */

    setvrd ($id, $rd_f);
    $_SERVER["REQUEST_URI"] = strFromTo ("/" . $_SERVER["REQUEST_URI"], "/", "?");

  }

  /*
    on request, marks all threads holding recent discussions as unread,
    by entirely clearing the "vrd" field of the member's entry in the VRD table.
  */

  if (fget ("mtu", 1, "") == "y") {

    setvrd ($id, array ());
    $_SERVER["REQUEST_URI"] = strFromTo ("/" . $_SERVER["REQUEST_URI"], "/", "?");

  }

  /*
    checking which forums hold recent, and unread, posts:
    - load recent discussions archive building an array ($rd_a) out of its
      newline-delimited records, initialize filtered array ($rd_u) to hold flags
      set to a boolean true for every forum IDs ($_f, which will be $rd_u's key)
      of every message ID appearing in the recent discussions' array, but not in
      this member's VRD table entries ($rd_v).
  */

  $rd_a = all ("forums/recdisc", asIs);
  $rd_v = getvrd ($id);
  $rd_u = array ();

  foreach ($rd_a as $r) {

    list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);

    if (!in_array ($_m, $rd_v)) {

      $rd_u[$_f] = true;

    }

  }

}
else {

  /*
    if not viewed by a logged in member, speaking of unread messages
    would be nonsensical...
  */

  $rd_u = array ();

}

/*
 *
 * generate forums' index page
 *
 */

$forums = all ("forums/index", makeProper);

if (count ($forums) == 0)

  $list = "No forums have been created yet.";

else {

  /*
    open forums table
  */

  $list = "<table width=100%>";

  /*
    set absolute Y coordinate for extra icon graphics, ie. signs to be partly superimposed
    to forums' icons, typically represented by the "unread posts here" signals (little stars)
    note: $f_base_offset is defined in "template.php"
  */

  $list_y = $f_base_yoffset;

  foreach ($forums as $f) {

    if (may_see ($f)) {

      /*
        get name and description of this forum,
        start with the basic version of a forum's icon...
      */

      $icon = "layout/images/forum";
      $name = valueOf ($f, "name");
      $desc = valueOf ($f, "desc");

      /*
        account for locked state (append "l" to icon name)
      */

      if (valueOf ($f, "islocked") == "yes") $icon .= "l";

      /*
        account for hidden state (append "h" to icon name)
      */

      if (valueOf ($f, "istrashed") == "yes") $icon .= "h";

      /*
        account for closed state: set name to "forumc", as closed forums are typically
        visible and not locked, but still, are kept with an unique icon to keep the set
        of possible forum icons smaller; if they're locked or hidden, they'll be showen
        as non-closed forums, although they may still be...
      */

      if ($icon == "layout/images/forum") {

        if (valueOf ($f, "isclosed") == "yes") $icon = "layout/images/forumc";

      }

      /*
        get forum ID for the link to its threads listing,
        get threads count for display ("tc" may be void: eventually evaluate as integer zero)
      */

      $fid = valueOf ($f, "id");
      $tc = intval (valueOf ($f, "tc"));

      /*
        generate "unread posts" signal's HTML, as $news, if it's the case
      */

      $news =

           ($rd_u[$fid])

            ? "<a" . chr (32) . link_to_threads ("", $fid, "") . ">"
            .  "<img src=layout/images/star.png width=24 height=24 border=0 style="
            .  "position:absolute;left:{$f_news_xoffset}px;top:" . ($list_y + $f_news_yoffset) . "px>"
            . "</a>"
            : "";

      $npHighlight =

           (($rd_u[$fid]) && ($ofie != "yes"))

            ? blank . "style=\"background-image:url(layout/images/slot{$graphicsvariant}n.png)\""
            : "";

      /*
        append entry to forums' table, update $list_y:
        - if you strongly suspect that I'm keeping the entry as simple as possible, without
          caring for alternate image texts, titles and suchs, because I want the list to take
          as little bandwidth as possible, and neverminding accessibility, YOU'RE DAMN RIGHT :p
      */

      $list .= "<tr>"
            .   "<td class=fc{$npHighlight}>"
            .    "<a" . chr (32) . link_to_threads ("", $fid, "") . "><img src=$icon.png width=48 height=48 border=0></a>{$news}"
            .   "</td>"
            .   "<td class=fe{$npHighlight}>"
            .    "<div class=fr>"
            .    "<a class=fk" . chr (32) . link_to_threads ("", $fid, "") . ">"
            .     $name
            .    "</a>"
            .    "</div>"
            .    "<span class=fd>"
            .     $desc
            .    "</span>"
            .   "</td>"
            .   "<td class=tc{$npHighlight} colspan=2>"
            .    $tc
            .    "&nbsp;"
            .   "</td>"
            .  "</tr>";

      /*
        append spacer, update $list_y
      */

      $list .= "<tr>"
            .   "<td class=sp>"
            .   "</td>"
            .  "</tr>"; // 27 chars total

      $list_y += 48 + $f_icon_spacing;  // $f_icon_spacing comes from "template.php"

    }

  }

  /*
    close forums table, remember to react to a particular condition where all existing forums
    are hidden: that would be very weird, but possible, and Postline would shamelessly claim,
    lying to regular members, that there's no forums...
  */

  if ($list_y > $f_base_offset) {

    $list = substr ($list, 0, -27) . "</table>";

  }
  else {

    $list = "No forums have been created yet.";

  }

}

/*
 *
 * determine permalink presence (present if no arguments given)
 *
 */

$args_not_given = ((count ($_GET)) || (count ($_POST))) ? false : true;

/*
 *
 * permalink initialization
 *
 */

$permalink = (($enable_permalinks) && ($args_not_given))

  ? str_replace

      (

        "[PERMALINK]", $perma_url . "/" . $forums_keyword, $permalink_model

      )

  : "";

/*
 *
 * template initialization
 *
 */

$forums_index = str_replace

  (

    array (

      "[LIST]",
      "[PERMALINK]"

    ),

    array (

      $list,
      $permalink

    ),

    $forums_index

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

echo (pquit ($forums_index));



?>
