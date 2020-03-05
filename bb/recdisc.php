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

require ("settings.php");
require ("suitcase.php");
require ("sessions.php");
require ("template.php");

/*
 *
 * get parameters
 *
 */

$page = intval (fget ("p", 10, ""));    // page number
$nfor = fget ("n", -1000, "");          // nickname to filter by

/*
 *
 * default filters
 *
 */

$_nfor = (empty ($nfor)) ? "" : "&amp;n=" . base62Encode ($nfor);
$full = "&amp;full=y";
$bp = "&amp;bp=y";

/*
 *
 * generate output
 *
 */

$rd = all ("forums/recdisc", asIs);

if (count ($rd) == 0) {

  /*
    recent discussions archive is void
  */

  $form =

        makeframe (

          "Recent posts",
          "No posts recorded.", true

        );

}
else {

  /*
    posts in recent discussions archive are given in chronological order, but on top of
    the lists provided by this script, they're more comfortable if given in reverse
    chronological order (managing reverse chronological order and still cutting older
    records directly at write time in function "addrecdisc" is slightly problematic, so
    I prefere to have this script reverse the array each time after reading the archive,
    even though it adds a slight overhead...)
  */

  $rd = array_reverse ($rd);

  /*
    get viewed recent discussions array,
    which will be used for filtering and marking...
  */

  $vrd = ($login == true) ? getvrd ($id) : array ();

  if (empty ($nfor)) {

    /*
      only unread posts? ($partial)
      only one entry per thread ($bythreads)
    */

    $partial = (($login == true) && (fget ("full", 1, "") != "y")) ? true : false;
    $bythreads = (fget ("bp", 1, "") != "y") ? true : false;

    if ($partial == false) {

      /*
        read posts filter disabled:
        copy all recent discussions to $fd
      */

      $fd = $rd;

    }
    else {

      /*
        toggle full history / unread posts switch
      */

      $full = "&amp;full=n";

      /*
        filter recent discussions list ($rd) to hold entries not listed in $vrd
      */

      $fd = array ();

      foreach ($rd as $r) {

        list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);
        if (!in_array ($_m, $vrd)) $fd[] = $r;

      }

    }

    if ($bythreads == false) {

      /*
        one post per thread filter disabled:
        copy $fd to $entry (final array of entries to show)
      */

      $entry = $fd;

    }
    else {

      /*
        toggle all messages / only threads switch
      */

      $bp = "&amp;bp=n";

      /*
        build array of thread IDs for reference ($aux),
        copy records from $fd to $entry so that only one record having a certain thread ID gets in
      */

      $aux = array ();
      $entry = array ();

      foreach ($fd as $r) {

        list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);

        if (!in_array ($_t, $aux)) {

          $aux[] = $_t;
          $entry[] = $r;

        }

      }

    }

  }
  else {

    /*
      egosearch (search for recent posts by a given nickname):
      discard switches...
    */

    $full = "";
    $bp = "";

    /*
      filter recent discussions list ($rd) to hold only entries by member <$nfor>
    */

    $entry = array ();

    foreach ($rd as $r) {

      list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);
      if ($_n == $nfor) $entry[] = $r;

    }

  }

  /*
    count entries and generate paging links
  */

  $c = count ($entry);
  $rpp = ($bigs == "yes") ? $recdiscsonlarge : $recdiscsperpage;

  if ($c == 0) {

    $prevpage = "&lt;&lt;&nbsp;&lt;";
    $currpage = "page 1 of 1";
    $nextpage = "&gt;&nbsp;&gt;&gt;";

  }
  else {

    $pages = (int) (($c - 1) / $rpp);

    $first_page_open = ($page > 0) ? "<a title=\"first page\" href=recdisc.php?p=0$_nfor$full$bp>" : "";
    $first_page_close = ($page > 0) ? "</a>" : "";

    $prev_page_open = ($page > 0) ? "<a title=\"page $page\" href=recdisc.php?p=" . ($page - 1) . "$_nfor$full$bp>" : "";
    $prev_page_close = ($page > 0) ? "</a>" : "";

    $last_page_open = ($page < $pages) ? "<a title=\"last page\" href=recdisc.php?p=$pages$_nfor$full$bp>" : "";
    $last_page_close = ($page < $pages) ? "</a>" : "";

    $next_page_open = ($page < $pages) ? "<a title=\"page" . chr (32) . ($page + 2) . "\" href=recdisc.php?p=" . ($page + 1) . "$_nfor$full$bp>" : "";
    $next_page_close = ($page < $pages) ? "</a>" : "";

    $prevpage = "$first_page_open&lt;&lt;$first_page_close&nbsp;$prev_page_open&lt;$prev_page_close";
    $currpage = "page" . chr (32) . ($page + 1) . chr (32) . "of" . chr (32) . ($pages + 1);
    $nextpage = "$next_page_open&gt;$next_page_close&nbsp;$last_page_open&gt;&gt;$last_page_close";

  }

  /*
    generate header, with links to control filtering of posts (in case on unfiltered request)
    or with link to return to given nickname's profile (in case of egosearch)
  */

  if (empty ($nfor)) {

    $return_button = "";
    $ttype = ($login == true) ? "a" : "span";

    $form =

          makeframe (

            "Recent posts", false, false

          )

          .

          makeframe (

            "navigation",
            "<p align=center>$prevpage $currpage $nextpage</p>", true

          )

          . "<table class=w>"
          .  "<td height=20 class=inv align=center>"
          .   "DISPLAY"
          .  "</td>"
          . "</table>"

          . $inset_bridge
          . $opcart
          . "<tr>"
          .

         (($bythreads == true)

          ? "<td width=50% class=alert align=center>"
          .  "by thread"
          . "</td>"
          . "<td class=ls align=center>"
          .  "<a class=ll href=recdisc.php?bp=y$full>"
          .   "by message"
          .  "</a>"
          . "</td>"

          : "<td width=50% class=ls align=center>"
          .  "<a class=ll href=recdisc.php?bp=n$full>"
          .   "by thread"
          .  "</a>"
          . "</td>"
          . "<td class=alert align=center>"
          .  "by message"
          . "</td>"

          )

          . "</tr>"
          . "<tr>"
          .

         ((($partial == true) || ($login == false))

          ? "<td class=ls align=center>"
          .  "<$ttype class=ll href=recdisc.php?full=y$bp>"
          .   "full history"
          .  "</a>"
          . "</td>"
          . "<td class=alert align=center>"
          .  "unread only"
          . "</td>"

          : "<td class=alert align=center>"
          .  "full history"
          . "</td>"
          . "<td class=ls align=center>"
          .  "<$ttype class=ll href=recdisc.php?full=n$bp>"
          .   "unread only"
          .  "</a>"
          . "</td>"

          )

          . "</tr>"
          . $clcart
          . $inset_bridge;

  }
  else {

    if ($nfor == $nick) {

      /*
        "find your latest posts" is on mstats, so that's where it should return
      */

      $return_to = "return to login panel";
      $return_link = "mstats.php";

    }
    else {

      /*
        "find someone else's posts" is instead on someone else's profile
      */

      $return_to = "back to $nfor's profile";
      $return_link = "profile.php?nick=" . base62Encode ($nfor);

    }

    $form =

          makeframe (

            "$nfor's recent posts", false, false

          )

          .

          makeframe (

            "navigation:",
            "<p align=center>$prevpage $currpage $nextpage</p>", true

          );

    $return_button = "<table width=$pw>"
                   . "<form action=$return_link>"
                   . "<input type=hidden name=nick value=" . base62Encode ($nfor) . ">"
                   .  "<td>"
                   .   "<input type=submit class=ky value=\"$return_to\" style=width:{$pw}px>"
                   .  "</td>"
                   . "</form>"
                   . "</table>"
                   . $inset_shadow;

  }

  /*
    list posts, also considering the requested page number:
    $rpp gives how many records are to be listed on a page, and depends on preferences
  */

  if ($c > 0) {

    $i = $page * $rpp;
    $n = 0;

    $form .= "<table width=$pw>"
          .   "<td height=20 class=inv align=center>"
          .    (($bythreads) ? "THREADS" : "POSTS") . chr (32) . "LIST:"
          .   "</td>"
          .  "</table>";

    /*
      $i is the index of the post to display from the $entry array,
      $n is the counter of posts listed so far on this page:
      the loop stops when $i is at end of array, or $n is at end of page
    */

    while (($i < $c) && ($n < $rpp)) {

      list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $entry[$i]);

      /*
        background coloring of a listed entry is, in the default template, green:
        it changes to a neutral white if the post is marked as read
      */

      $is_read = (($login == false) || (in_array ($_m, $vrd))) ? true : false;
      $coloring = ($is_read) ? chr (32) . "style=background-color:white" : "";

      $form .= $inset_bridge
            .  $opcart
            .   "<tr>"
            .    "<td class=i{$coloring}>"
            .     "<a class=ll target=pan href=posts.php?t=$_t&amp;p=of$_m#$_m>"
            .      "$_F<br>"
            .      "&#9492;&gt;" . chr (32) . cutcuts (substr ($_T, 0, 30))
            .     "</a>"
            .    "</td>"
            .   "</tr>"
            .   "<tr>"
            .    "<td class=hn>"
            .     "&#171;$_h&#187;"
            .    "</td>"
            .   "</tr>"
            .   "<tr>"
            .    "<td class=au align=right>"
            .      $_n . chr (32) . gmdate ("m/d H:i", $_e)
            .    "</td>"
            .   "</tr>"
            .  $clcart;

      $i ++;
      $n ++;

    }

    $form .= $inset_shadow;

  }
  else {

    $form .= "<table width=$pw>"
          .   "<td height=20 class=inv align=center>"
          .    "NO MATCH FOUND"
          .   "</td>"
          .  "</table>"
          .  $inset_shadow;

  }

  $form .= $return_button;

}

/*
 *
 * template initialization
 *
 */

$searchresults = str_replace

  (

    array (

      "[FORM]",
      "[PERMALINK]"

    ),

    array (

      $form,
      $permalink

    ),

    $searchresults

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page upon showing or
 * hiding the chat frame, and for the online members list links (unless no-spy flag checked).
 *
 */

include ("setlfcookie.php");

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($searchresults));



?>
