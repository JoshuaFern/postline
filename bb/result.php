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
 * initialize accumulators, get parameters
 *
 */

$parent_forum = "";                     // parent forum ID (always present in paging links)
$forum = "(unknown forum)";             // remains so when message isn't found
$thread = "(unknown thread)";           // remains so when message isn't found
$list = "";                             // output accumulator used by "message.php"
$paging = "";                           // paging links

$m = intval (fget ("m", 1000, ""));     // get message ID of message to show
$hint = fget ("h", -1000, "");          // decode text to highlight (in search results)

$nonpublic_forum = false;               // if true in the end, it won't track the page's URL

/*
 *
 * locate DB file holding post record, find post record and show single message:
 * this script is used to show single messages, but especially for search results
 *
 */

$mdb = "posts" . intervalOf ($m, $bs_posts);
$post = get ($mdb, "id>$m", "");

$error = (empty ($post)) ? true : false;

/*
 *
 * if found, provide links to reach message's actual forum and thread
 *
 */

if ($error == false) {

  /*
    retrieve parent thread ID from message record,
    check if thread exists AND if it's in a public forum
  */

  $t = valueOf ($post, "tid");

  if (empty ($t)) {

    $error = true;

  }
  else {

    /*
      locate parent forum of message's parent thread:
      if either thread record or forum record doesn't exist, or if parent forum is hidden,
      set error flag; note that it's very important for the script to react in the same,
      identical way in the two possible cases where a forum or a thread don't exist, or
      when the parent forum is hidden; otherwise, different reactions may spoil existence
      of hidden messages (if the user is clever enough to try passing random message IDs...)
    */

    $tdb = "forums" . intervalOf (intval ($t), $bs_threads);
    $thread_record = get ($tdb, "id>$t", "");
    $parent_forum = intval (valueOf ($thread_record, "fid"));
    $forum_record = get ("forums/index", "id>$parent_forum", "");

    if ((empty ($forum_record)) || (empty ($thread_record)) || (may_see ($forum_record) == false)) {

      $error = true;

    }
    else {

      $forum = valueOf ($forum_record, "name");
      $thread = shorten (valueOf ($thread_record, "title"), $tt_hint_length);

      $nonpublic_forum = (valueOf ($forum_record, "istrashed") == "yes") ? true : false;

    }

  }

}

/*
 *
 * generate output
 *
 */

if ($error == true) {

  $list  = "Message not found in forums database.";

}
else {

  /*
    turning off intercom calls (mark new post in a hidden forum as read)
  */

  if (($login == true) && ($m == $intercom)) {

    lockSession ();

    $db = "members" . intervalOf ($id, $bs_members);
    $r = get ($db, "id>$id", "");

    if (!empty ($r)) {

      set ($db, "id>$id", "intercom", "");

    }

  }

  /*
    preloading rendering variables required by "message.php" (see)
  */

  $author = valueOf ($post, "author");
  $epoc = intval (valueOf ($post, "date"));
  $message = valueOf ($post, "message");

  $bcol = valueOf ($post, "bcol");
  $bcol = ($bcol === "") ? "" : intval ($bcol);

  $mid = "";
  $ignored = false;

  $postlink = $pm_button = $quote_button =
  $retitle_button = $edit_button = $delete_button = $split_button = "";
  $enable_styling_warning = false;

  /*
    search hints highlighting, if anything is to highlight
  */

  if (!empty ($hint)) {

    $sc = strtolower ($message);                        // SCope of string search
    $fi = strtolower ($hint);                           // string to FInd
    $fw = strpos ($sc, chr(32) . $fi . chr(32));        // Found Word?

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

      /*
        if a match (of any of the above sorts) was found, check if the match occurs in the
        middle of an html or paragraph control tag: if it's occuring there, you'd better
        not highlight it to avoid disrupting the tag's structure by forcing another tag in
        a completely wrong place... I can't loop until first match outside tags is found,
        because in the first place, search indexing doesn't discriminate words appearing in
        tags, and if that was the only occurrence that lead the search script to list this
        message as a result, this script would hang in the loop...
      */

      $gt_pos = strpos ($message, "&gt;", $fw); // position of 1st ">" sign following match
      $lt_pos = strpos ($message, "&lt;", $fw); // position of 1st "<" sign following match

      /*
        the rule now is: an html or paragraph control tag is assumed to be terminated by a
        greater than sign, so if a greater than is found, and it does NOT follow the first
        less than (tag opener) after the matching word's position, the match might be part
        of the tag that's ideally closed by the said "greater than" sign; if there were no
        greater than signs at all, or if the first greater than comes AFTER the first less
        than sign, highlighting the matching word is LEGAL, and it will be done.
      */

      if (($gt_pos === false) || ($gt_pos > $lt_pos)) {

        $message = substr ($message, 0, $fw)                    // text coming before match
                 . "<span class=key>"                           // inserted highlighting tag
                 . substr ($message, $fw, strlen ($fi))         // word to highlight
                 . "</span>"                                    // closing highlighting tag
                 . substr ($message, $fw + strlen ($fi));       // text coming after match

      }

    }

  }

  /*
    compiling last edit notice
  */

  $editor = valueOf ($post, "edit");

  if (empty ($editor)) {

    $lastedit = "";

  }
  else {

    $e_epoc = intval (valueOf ($post, "on"));
    $e_date = gmdate ("M d, Y", $e_epoc);
    $e_time = gmdate ("H:i", $e_epoc);

    $lastedit = "last changed by $editor on $e_date at $e_time";

  }

  /*
    set a simple paging caption and a link to reach the page of the parent thread
    that holds this message...
  */

  $paging = "<a class=fl" . chr (32) . link_to_posts ("", $t, "of$m", $m, false) . ">"
          .  "MESSAGE #$m"
          . "</a>";

  /*
    output message body
  */

  include ("message.php");

}

/*
 *
 * permalink initialization:
 *
 * always, even in search results, although highlighters will not be memorized in permalinks,
 * but in that case it is way too useful to point someone else at the result of a search
 *
 */

$permalink = (($enable_permalinks) && ($error == false))

  ? str_replace

      (

        "[PERMALINK]", $perma_url . "/" . $message_keyword . "/" . $m, $permalink_model

      )

  : "";

/*
 *
 * template initialization
 *
 */

$forum_posts = str_replace (

  array (

    "[FORUM]",
    "[THREAD]",
    "[PAGING]",
    "[USERSLIST]",
    "[LIST]",
    "[PERMALINK]"

  ),

  array (

    "<a class=fl" . chr (32) . link_to_threads ("", $parent_forum, 0) . ">$forum</a>",
    "<a class=fl" . chr (32) . link_to_posts ("", $t, "", "", false) . ">$thread</a>",
    $paging,
    "(not applicable to single message display)",
    $list,
    $permalink

  ),

  $forum_posts

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
 * (unless no-spy flag checked OR the forum is hidden)
 *
 */

if (($login == true) && ($nspy != "yes") && ($nonpublic_forum == false)) {

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

echo (pquit ($forum_posts));



?>
