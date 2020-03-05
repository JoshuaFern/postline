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
 * generate output
 *
 */

$mi = intval (get ("members/bynick", "nick>$nfor", "id"));
$db = "members" . intervalOf ($mi, $bs_members);
$tl = get ($db, "id>$mi", "threads_list");
$tl = wExplode (";", $tl);

if (count ($tl) == 0) {

  /*
    threads list is void
  */

  $form =

        makeframe (

          "EgoSearch", false, false

        )

        .

        makeframe (

          "information", "No threads were ever started by '$nfor'.", true

        );

}
else {

  /*
    posts in threads lists by member are given in chronological order, but on top of
    the lists provided by this script, they're more comfortable if given in reverse
    chronological order...
  */

  $tl = array_reverse ($tl);

  /*
    count entries and generate paging links:
    it uses the same layout as "recdisc.php", and thus, also the same settings,
    although since it doesn't give hints about a message's contents, there will
    probably enough room for an extra record...
  */

  $c = count ($tl);
  $rpp = ($bigs == "yes") ? $recdiscsonlarge + 1 : $recdiscsperpage + 1;

  $_nfor = "&amp;n=" . base62Encode ($nfor);
  $pages = (int) (($c - 1) / $rpp);

  $first_page_open = ($page > 0) ? "<a title=\"first page\" href=egosearch.php?p=0$_nfor>" : "";
  $first_page_close = ($page > 0) ? "</a>" : "";

  $prev_page_open = ($page > 0) ? "<a title=\"page $page\" href=egosearch.php?p=" . ($page - 1) . "$_nfor>" : "";
  $prev_page_close = ($page > 0) ? "</a>" : "";

  $last_page_open = ($page < $pages) ? "<a title=\"last page\" href=egosearch.php?p=$pages$_nfor>" : "";
  $last_page_close = ($page < $pages) ? "</a>" : "";

  $next_page_open = ($page < $pages) ? "<a title=\"page" . chr (32) . ($page + 2) . "\" href=egosearch.php?p=" . ($page + 1) . "$_nfor>" : "";
  $next_page_close = ($page < $pages) ? "</a>" : "";

  $prevpage = "$first_page_open&lt;&lt;$first_page_close&nbsp;$prev_page_open&lt;$prev_page_close";
  $currpage = "page" . chr (32) . ($page + 1) . chr (32) . "of" . chr (32) . ($pages + 1);
  $nextpage = "$next_page_open&gt;$next_page_close&nbsp;$last_page_open&gt;&gt;$last_page_close";

  /*
    generate header, with navigation links
  */

  $form =

        makeframe (

          "$nfor's threads", false, false

        )

        .

        makeframe (

          "navigation", "<p align=center>$prevpage $currpage $nextpage</p>", true

        );

  /*
    list posts, also considering the requested page number:
    $rpp gives how many records are to be listed on a page, and depends on preferences
  */

  $i = $page * $rpp;
  $n = 0;

  $form .= "<table width=$pw>"
        .   "<td height=20 class=inv align=center>"
        .    "THREADS LIST:"
        .   "</td>"
        .  "</table>";

  /*
    $i is the index of the thread to display from the $entry array,
    $n is the counter of threads listed so far on this page:
    the loop stops when $i is at end of array, or $n is at end of page
  */

  while (($i < $c) && ($n < $rpp)) {

    $_t = intval ($tl[$i]);

    $_f = get ("forums" . intervalOf ($_t, $bs_threads), "id>$_t", "fid");
    $_e = get ("forums" . intervalOf ($_t, $bs_threads), "id>$_t", "date");
    $_T = get ("forums" . intervalOf ($_t, $bs_threads), "id>$_t", "title");

    if (empty ($_f)) {

        $form .= $inset_bridge
              .  $opcart
              .   "<tr>"
              .    "<td class=i>"
              .      "thread #{$_t}"
              .    "</td>"
              .   "</tr>"
              .   "<tr>"
              .    "<td class=au align=right>"
              .      "thread no longer exists"
              .    "</td>"
              .   "</tr>"
              .  $clcart;

    }

    else {

      $_F = get ("forums/index", "id>$_f", "name");

      $form .= $inset_bridge
            .  $opcart
            .   "<tr>"
            .    "<td class=i>"
            .     "<a class=ll target=pan href=posts.php?t=$_t>"
            .      "$_F<br>"
            .      "&gt;" . chr (32) . cutcuts (substr ($_T, 0, 30))
            .     "</a>"
            .    "</td>"
            .   "</tr>"
            .   "<tr>"
            .    "<td class=au align=right>"
            .      "started on:" . chr (32) . gmdate ("m/d/Y H:i", $_e)
            .    "</td>"
            .   "</tr>"
            .  $clcart;

    }

    $i ++;
    $n ++;

  }

  /*
    append link to return to given nickname's profile
  */

  $nlab = ($nfor == $nick) ? "your" : "$nfor's";

  $form .= $inset_shadow
        .  "<table width=$pw>"
        .   "<form action=profile.php>"
        .   "<input type=hidden name=nick value=" . base62Encode ($nfor) . ">"
        .   "<td>"
        .    "<input type=submit class=ky value=\"back to $nlab profile\" style=width:{$pw}px>"
        .   "</td>"
        .   "</form>"
        .  "</table>"
        .  $inset_shadow;

}

/*
 *
 * template initialization
 *
 */

$searchresults = str_replace

  (

    array

      (

        "[FORM]",
        "[PERMALINK]"

      ),

    array

      (

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
