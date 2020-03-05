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

$em['search_disabled'] = "SEARCH IS DISABLED";
$ex['search_disabled'] =

        "Sorry, word indexing has been disabled: this is likely disabled to save CPU time.";

/*
 *
 * utility function to sort the array of resulting message IDs by the number of occurrences
 * of the search terms matching a given ID (in practice, by "relevance" of the result)
 *
 */

function sort_any ($a, $b) {

  global $occs_count; // is initialized later
  return $occs_count[$b] - $occs_count[$a];

}

/*
 *
 * utility function to return matching phrases from a chat log file basing on $epoch
 *
 */

function cl_matching_lines ($epoch) {

  $file = "logs/" . gmdate ("Y", $epoch) . "/" . gmdate ("Y_m_d", $epoch) . ".html";
  $hour = gmdate ("H", $epoch);
  $minute = gmdate ("i", $epoch);
  $text = @file_get_contents ($file);

  preg_match_all ("/\<a name\=$hour$minute\>.+?\n?\<\/a\>/", $text, $matches);

  return $matches[0];

}

/*
 *
 * check global "search disable" flag:
 * if search is disabled, output an error and stop running
 *
 */

if ($search_disable) {

  unlockSession ();
  die (because ('search_disabled'));

}

/*
 *
 * initialize accumulators
 *
 */

$form = "";                                     // page output accumulator
$list = "";                                     // list of search results (part of output)
$wl = "";                                       // wordlist as an argument via paging links
$error = false;                                 // error flag

/*
 *
 * get parameters
 *
 */

$submit = isset ($_GET["submit"]);              // submission trigger
$change = isset ($_GET["change"]);              // change parameters flag
$find = fget ("find", 3, "");                   // type of matching: "any" or "all" words
$where = fget ("where", 5, "");                 // either "posts" or "logs"
$search = fget ("search", $maxsearchlen, "");   // user-provided search pattern

/*
 *
 * consider non-empty $search pattern as an additional submission trigger,
 * to resume the search when script is launched by clicking paging links
 *
 */

if ($change) {

  $submit = false;

}
else {

  if ($submit) {

    $page = 0;

  }
  else {

    $submit = (empty ($search)) ? false : true;
    $page = intval (fget ("p", 1000, ""));

  }

}

/*
 *
 * if $where is neither "posts", nor "logs", default it to "posts";
 * if $where was "logs", compute base epoch as the epoch corrisponding to May 5 of year 2003,
 * at exactly 8:05 PM, which is the date and time of the first phrase that was "historically"
 * posted in Postline's chat frame, and corresponds to epoch zero of the chat logs' indexs
 *
 */

$where = (($where == "posts") || ($where == "logs")) ? $where : "posts";

if ($where == "logs") {

  $base_epoch = gmmktime (20, 05, 00, 05, 05, 2003, 0) + $time_offset;

}

/*
 *
 * use "wordlist_of" to retrieve an array of valid search terms from the $search pattern:
 * it will make one filtered and one unfiltered version, the difference between these two
 * arrays giving a list of ignored words, so the user can be warned about what's ignored;
 * in any cases it's useless to sort them by page of search dictionary, because all pages
 * where words belong will have to be read anyway...
 *
 */

$terms = wordlist_of ("dummy", $search, true, false);
$unfiltered_terms = wordlist_of ("dummy", $search, false, false);
$ignored_terms = wArrayDiff ($unfiltered_terms, $terms);

/*
 *
 * count terms that need to be effectively searched, and count those that were ignored,
 * connect $wl wordlist with "+" signs to be passed as an URL argument via paging links,
 * create blank-seaparated wordlist as $form_s to be re-loaded into the form if an error
 * occured, so the user won't have to type the given search pattern again to retry...
 *
 */

$terms_no = count ($terms);
$ignored_terms_no = count ($ignored_terms);

$wl = implode ("+", $terms);
$form_s = implode (chr (32), $terms);

/*
 *
 * finally do the requested search (all of the above was just "preparation")
 *
 */

if ($submit == false) {

  /*
    this is just because it's not a submit, so it will show a void form...
  */

  $error = true;

}
else {

  if ($terms_no > 0) {

    /*
      at least one term wasn't filtered out by "wordlist_of", and is valid for searching:
      so the search request is valid and processing may continue... now begin by setting up
      an array ($entries) containing the message IDs of the posts were the first searched
      word occurs; depending on the matching mode given by $find ("any" or "all"), this
      initial array will be matched with the arrays resulting from searching the other words
    */

    foreach ($terms as $t) {

      $entries = where_occurs ($where, $t);
      break;

    }

    if ($find == "any") {

      /*
        collect occurrencies of the search terms by merging arrays of matches:
        the flag $go indicates the "foreach" loop to begin acting from the second term
      */

      $go = false;

      foreach ($terms as $t) {

        if ($go) {

          $entries = array_merge ($entries, where_occurs ($where, $t));

        }
        else {

          $go = true;

        }

      }

      /*
        if not searching in logs, discard all copies of repeating IDs:
        in logs, duplicates can happen and be valid because more phrases can be posted
        within the same exact minute; in posts, the message ID is, instead, unique...
      */

      if ($where != "logs") {

        $entries = wArrayUnique ($entries);

      }

      /*
        if the search was for more terms, sort the final array by counting the repeats of each
        message IDs: when search is for 1 word, instead, the absence of sorting can output the
        results ordered from most to least recent (because of how IDs are organized in indexs)
      */

      if ($terms_no > 1) {

        $occs_count = array_count_values ($entries);
        usort ($entries, "sort_any");

      }

    }
    else {

      /*
        create an intersection of the arrays (of IDs) where each search term appears
        the flag $go indicates the "foreach" loop to begin acting from the second term
      */

      $go = false;

      foreach ($terms as $t) {

        if ($go) {

          $entries = array_intersect ($entries, where_occurs ($where, $t));

        }
        else {

          $go = true;

        }

      }

      /*
        discard all copies of repeating IDs
      */

      $entries = wArrayUnique ($entries);

    }

    /*
      discard any empty entries left in the $entries array after all that processing,
      building an array of strictly-numerically-indexed entries, and starting at index 0
    */

    $results = array ();

    foreach ($entries as $e) {

      if (!empty ($e)) {

        $results[] = $e;

      }

    }

    /*
      count entries, generate paging links,
      output search results, or the part of them that fits requested page
    */

    $c = count ($results);
    $rpp = ($bigs == "yes") ? $resultsonlarge : $resultsperpage;

    if ($c == 0) {

      $error = true;
      $form = "Sorry, no results...";

      if ($ignored_terms_no > 0) {

        $form .= "//The following words were ignored: " . implode (", ", $ignored_terms) . ".";

      }
      else {

        $form .= "//Your search for " .  implode (", ", $terms) . " returned no results: "
              .  "you may try again changing the scope from forums to chat logs, and vice-versa.";

      }

    }
    else {

      $pages = (int) (($c - 1) / $rpp);
      $args = "search.php?search=$wl&amp;find=$find&amp;where=$where&amp;p=";

      $first_page_open = ($page > 0) ? "<a title=\"first page\" href=$args{0}>" : "";
      $first_page_close = ($page > 0) ? "</a>" : "";

      $prev_page_open = ($page > 0) ? "<a title=\"page $page\" href=$args" . ($page - 1) . ">" : "";
      $prev_page_close = ($page > 0) ? "</a>" : "";

      $last_page_open = ($page < $pages) ? "<a title=\"last page\" href={$args}$pages>" : "";
      $last_page_close = ($page < $pages) ? "</a>" : "";

      $next_page_open = ($page < $pages) ? "<a title=\"page" . chr (32) . ($page + 2) . "\" href=$args" . ($page + 1) . ">" : "";
      $next_page_close = ($page < $pages) ? "</a>" : "";

      $prevpage = "$first_page_open&lt;&lt;$first_page_close&nbsp;$prev_page_open&lt;$prev_page_close";
      $currpage = "page" . chr (32) . ($page + 1) . chr (32) . "of" . chr (32) . ($pages + 1);
      $nextpage = "$next_page_open&gt;$next_page_close&nbsp;$last_page_open&gt;&gt;$last_page_close";

      $list = "<table width=$pw>"
            .  "<tr>"
            .   "<td height=20 class=inv align=center>"
            .    "match: "
            .

          (($find == "any")

            ?    implode (" <u>or</u> ", $terms)
            :    implode (" <u>and</u> ", $terms)

            )

            .   "</td>"
            .  "</tr>"
            . "</table>";

      /*
        this loop will break once there's no more results ($i >= $c)
        or the page is over ($n > $rpp), (RPP = Results Per Page)
      */

      $i = $page * $rpp;
      $n = 0;

      while (($i < $c) && ($n < $rpp)) {

        if ($where == "posts") {

          /*
            results from posts:
            get message ID from results array and convert it back from base-62 to decimal,
            locate database file holding the message having $mid as its ID, read its record
          */

          $mid = convertFromBase62 (ltrim ($results[$i], "="));
          $mdb = "posts" . intervalOf ($mid, $bs_posts);
          $msg = get ($mdb, "id>$mid", "");

          if (empty ($msg)) {

            $author = "unknown";
            $date = "(?)";

            $hint = "<i>message record not found</i>";
            $hw = "";

          }
          else {

            $author = valueOf ($msg, "author");
            $date = intval (valueOf ($msg, "date"));

            /*
              for what concerns the message's body, convert any newline codes,
              marked by special marker "&;", to blank spaces
            */

            $message = strToLower (valueOf ($msg, "message"));
            $message = str_replace ("&;", chr(32), $message);

            /*
              look for the first word that's matched in the message's body, to highlight
              the word in the surrounding text, that will be presented in the message's "hint"
            */

            foreach ($terms as $t) {

              $sc = $message;                                   // SCope of string search
              $fi = $t;                                         // string to FInd
              $fw = strpos ($sc, chr(32) . $fi . chr(32));      // Found Word?

              if ($fw !== false) {

                /*
                  word was found as an isolated word, wrapped by blank spaces on each side:
                  highlightling begins after initial blank space of match, so increase offset
                */

                $fw ++;

              }
              else {

                /*
                  search for first matching word preceeded, but not followed, by one blank space
                */

                $fw = strpos ($sc, chr(32) . $fi);
                $fw = ($fw !== false) ? $fw + 1 : $fw;

                /*
                  if still no match was found,
                  search for first matching word followed, but not preceeded, by one blank space,
                  then as a last chance, search first matching word anywhere in message's text...
                */

                $fw = ($fw === false) ? strpos ($sc, $fi . chr(32)) : $fw;
                $fw = ($fw === false) ? strpos ($sc, $fi) : $fw;

              }

              if ($fw !== false) {

                $hw = $t;       // preserve matching word for later passing it to "result.php"
                break;          // break on first word match

              }

            }

            if ($fw === false) {

              /*
                no match at all? weird... it means the search dictionary "knew" there were
                occurrences of the search terms in the message, but that wasn't true: that
                would be a malfunction or an inconsistence in the search dictionary, but it
                might be rare (in theory it should never occur, but maintainance operations,
                data loss recovery, and similar situations, could still cause this problem):
                well, if it's the case, just make the hint be the starting words of the post
              */

              $hint = trim (cutcuts (substr ($message, 0, $hintlength)));
              $hw = "";

            }
            else {

              $fw -= (int) ($hintlength / 2);
              $fw = ($fw < 0) ? 0 : $fw;

              $hint = trim (cutcuts (substr ($message, $fw, $hintlength)));
              $hw = base62Encode ($hw);

            }

          }

          $list .= $inset_bridge
                .  $opcart
                .   "<tr onclick=parent.pan.document.location='result.php?m=$mid&amp;h=$hw'>"
                .    "<td class=hn>"
                .     "&#171;$hint&#187;"
                .    "</td>"
                .   "</tr>"
                .   "<tr onclick=parent.pan.document.location='result.php?m=$mid&amp;h=$hw'>"
                .    "<td class=au align=right>"
                .     "<a class=ll target=pan href=result.php?m=$mid&amp;h=$hw>"
                .      $author . chr (32) . gmdate ("m/d/y", $date)
                .     "</a>"
                .    "</td>"
                .   "</tr>"
                .  $clcart;

        }
        else {

          /*
            results from chat logs:
            get epoch from results array and convert it back from base-62 to decimal,
            then multiply the minute-based epoch by 60 and trasform it in a seconds-based
            epoch, and add $base_epoch to find out the real epoch (processable by gmdate)
          */

          $epoch = (convertFromBase62 (ltrim ($results[$i], "=")) * 60) + $base_epoch;

          /*
            now, there may be problems with DST (Daylight Saving Time): I'm sincerely not sure
            how DST affects the epoch calculated via gmdate() or gmmktime(), but I thought the
            system would have taken care of taking the eventual DST into account; well, it did
            not... so I'm actually working this around by extending the search to any matching
            chat log files at deltas -120, -60, -30, +30, +60, and +120 minutes from the epoch
            calculated basing on the recorded entry of the chat logs search dictionary: if any
            one knows how to precisely take DST into account, have a try improving this...
          */

          $lines = array ();    // will accumulate any matching log file lines, in the loop
          $deltas = array (-120, -60, -30, 0, +30, +60, +120);  // possible DST deltas (minutes)

          foreach ($deltas as $d) {

            /*
              $lines is an array of arrays (a two-dimensional array):
              it's indexed by delta, while inmost arrays hold all matching lines for that delta
            */

            $lines[$d] = cl_matching_lines ($epoch + 60 * $d);

          }

          /*
            $date (and $time) always reflect the epoch at which a match was apperently found:
            so, even when there will be an error because the given log file was deleted from
            the "logs" folder, at least it will indicate WHICH file is now missing...
          */

          $date = $epoch;

          if (count ($lines) == 0) {

            /*
              no matching lines at all pratically always means there's no log file,
              and most probably it's no longer there because the manager deleted it,
              perhaps to save server space... it's something that could be done as a
              periodical maintainance operation
            */

            $hint = "<i>no phrase or file matches epoch</i>";

          }
          else {

            /*
              else there are lines matching that epoch (or one of the epochs resulting from
              adding one or more of the deltas to match possible DST effects): however, this
              doesn't automatically mean there will be a word to match and highlight as hint
              in the list's entry, although there MIGHT always be... anyway, it preloads the
              following error message as $hint: a possible problem could be mismatching logs
            */

            $hint = "<i>cannot locate matching phrases</i>";

            /*
              scan all line entries for all deltas, looking for any of the searched words:
              the two nested loops will break on first match, producing a hint to display...
            */

            foreach ($deltas as $d) {

              foreach ($lines[$d] as $l) {

                $l = strToLower ($l);

                /*
                  look for the first word that's matched in the message's body, to highlight
                  the word in the surrounding text, that will be presented in the message's "hint"
                */

                foreach ($terms as $t) {

                  $sc = $l;                                         // SCope of string search
                  $fi = $t;                                         // string to FInd
                  $fw = strpos ($sc, chr(32) . $fi . chr(32));      // Found Word?

                  if ($fw !== false) {

                    /*
                      word was found as an isolated word, wrapped by blank spaces on each side:
                      highlightling begins after initial blank space of match, so increase offset
                    */

                    $fw ++;

                  }
                  else {

                    /*
                      search for first matching word preceeded, but not followed, by a blank space
                    */

                    $fw = strpos ($sc, chr(32) . $fi);
                    $fw = ($fw !== false) ? $fw + 1 : $fw;

                    /*
                      if still no match was found,
                      search for first matching word followed, but not preceeded, by a blank space,
                      then as a last chance, search first matching word anywhere in message's text.
                    */

                    $fw = ($fw === false) ? strpos ($sc, $fi . chr(32)) : $fw;
                    $fw = ($fw === false) ? strpos ($sc, $fi) : $fw;

                  }

                  if ($fw !== false) {

                    break;        // out of words loop

                  }

                } // each word

                if ($fw !== false) {

                  $fw -= (int) ($hintlength / 2);
                  $fw = ($fw < 0) ? 0 : $fw;

                  $date = $epoch + 60 * $d;
                  $hint = trim (cutcuts (substr ($l, $fw, $hintlength)));

                  break 2;      // out of lines and deltas loops

                }

              } // each line

            } // each delta

          }

          /*
            $author is called so to indicate that it goes in the same place (in the list) as
            the author of a post in posts' results; here, however, indicates the chat log file
            which the matching phrase belongs to...
          */

          $time = gmdate ("Hi", $date);
          $author = "logs/" . gmdate ("Y", $date) . "/" . gmdate ("Y_m_d", $date) . ".html";

          $list .= $inset_bridge
                .  $opcart
                .   "<tr onclick=parent.pan.document.location='{$author}#$time'>"
                .    "<td class=hn>"
                .     "&#171;$hint&#187;"
                .    "</td>"
                .   "</tr>"
                .   "<tr onclick=parent.pan.document.location='{$author}#$time'>"
                .    "<td class=au align=right>"
                .     "<a class=ll target=pan href={$author}#$time>"
                .      $author . chr (32) . gmdate ("H:i", $date)
                .     "</a>"
                .    "</td>"
                .   "</tr>"
                .  $clcart;

        }

        /*
          advance loop counters for outmost loop concerning all ID entries in search results
        */

        $i ++;
        $n ++;

      }

      $list .= $inset_bridge;

    }

  }
  else {

    /*
      all terms were filtered out by "wordlist_of": it can happen when they're all very common
      words, or particular words like "heeeeello" (with a letter repeating more than 3 times),
      or alphanumeric combinations (BFG9000, PUCK6633) or even large numeric strings (300000):
      all these restrictions have the purpose of keeping the search dictionary focused on real
      words that might be worth indexing in the first place, without the dictionary page files
      become too large for efficient management...
    */

    $error = true;
    $form = "Invalid search request...";

    if ($ignored_terms_no > 0) {

      $form .= "//The following words were ignored: " . implode (", ", $ignored_terms) . ".";

    }

  }

}

/*
 *
 * form initialization
 *
 */

if ($error == false) {

  $form =

        makeframe (

          "Search results:", false, false

        )

        .

        makeframe (

          "navigation",
          "<p align=center>$prevpage $currpage $nextpage</p>",

          true

        )

        . $list
        .

       (($ignored_terms_no > 0)

        ? "<table width=$pw>"
        .  "<td height=20 class=alert align=center>"
        .   "IGNORED WORDS"
        .  "</td>"
        . "</table>"
        . $inset_bridge
        . $opcart
        .  "<td class=ls>"
        .   implode (", ", $ignored_terms) . "<hr>"
        .   "Above words are either shorter than three characters, alphanumeric codes, "
        .   "numeric values outside the range of a calendar date, very common, or silly words. "
        .   "For one or more of these reasons they aren't indexed and are ignored in searchs."
        .  "</td>"
        . $clcart
        . $inset_bridge
        : ""

        )

        . "<table width=$pw>"
        . "<form action=search.php enctype=multipart/form-data method=get>"
        . "<input type=hidden name=search value=\"$form_s\">"
        . "<input type=hidden name=where value=$where>"
        . "<input type=hidden name=find value=$find>"
        . "<input type=hidden name=change value=1>"
        .  "<td>"
        .   "<input class=ky style=width:{$pw}px type=submit name=submit value=\"change search parameters\">"
        .  "</td>"
        . "</form>"
        . "</table>"
        . $inset_shadow;

}
else {

  list ($frame_title, $frame_contents) = explode ("//", $form);
  $fw = $iw - 50;

  $form =

       ((empty ($form))

        ? makeframe ("Search for...", false, false)
        : makeframe ($frame_title, false, false)
        . (($frame_contents) ? makeframe ("information", $frame_contents, true) : "")

        )

        . $opcart
        . "<form name=finder action=search.php enctype=multipart/form-data method=get>"
        .  $fspace
        .  "<tr>"
        .   "<td width=50 class=in align=right>"
        .    "Find:&nbsp;&nbsp;"
        .   "</td>"
        .   "<td class=in>"
        .    "<input class=mf style=width:{$fw}px type=text name=search value=\"$form_s\" maxlength=$maxsearchlen>"
        .   "</td>"
        .  "</tr>"
        .  $fspace
        . $clcart
        . $inset_bridge

        . $opcart
        .  "<td class=in style=padding:4px>"
        .   "<input type=radio name=where value=posts" . (($where != "logs") ? " checked" : "") . ">&nbsp;search through forums<br>"
        .   "<input type=radio name=where value=logs" . (($where == "logs") ? " checked" : "") . ">&nbsp;search through chat logs"
        .  "</td>"
        . $clcart
        . $inset_bridge

        . $opcart
        .  "<td class=in style=padding:4px>"
        .   "<input type=radio name=find value=any" . (($find != "all") ? " checked" : "") . ">&nbsp;where any words occur<br>"
        .   "<input type=radio name=find value=all" . (($find == "all") ? " checked" : "") . ">&nbsp;where all words occur"
        .  "</td>"
        . $clcart
        . $inset_bridge

        . "<table width=$pw>"
        .  "<td>"
        .   "<input class=ky style=width:{$pw}px type=submit name=submit value=search>"
        .  "</td>"
        . "</form>"
        . "</table>"
        . $inset_shadow
        .

        makeframe (

          "instructions:",

          "Enter search terms in the field on top: separate them with blank "
        . "spaces or punctuation marks such as commas, semicolons etc. Consider that there is a "
        . "limit of $maxsearchlen characters for the whole search string, and that very common "
        . "words and any word that's shorter than 3 characters are <b>ignored</b> in searchs, "
        . "then click 'search'.", true

        )

        . "\n"
        . "<script language=Javascript type=text/javascript>\n"
        . "<!--\n"
        .   "document.finder.search.focus();\n"
        . "//-->\n"
        . "</script>\n";

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
