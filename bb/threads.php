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
 * get parameters
 *
 */

$f = intval (fget ("f", 1000, ""));                     // forum ID
$page = intval (fget ("p", 1000, ""));                  // page number

/*
 *
 * initialize accumulators
 *
 */

$forum_name = "";                                       // name of forum, initially empty
$paging = "";                                           // paging links
$list = "";                                             // page output accumulator
$witheld_tracking = false;                              // URL tracked unless listing hidden forum

$opicon = "n";                                          // new thread post icon
$oplink = "postform.php?f=$f";                          // new thread post link
$option = "Post a new thread...";                       // new thread post option text
$optext = "click the link above to create a new topic"; // new thread post option description

/*
 *
 * if given forum ID is empty, or if user isn't allowed to see the forum,
 * pop exactly the same error message...
 *
 */

$forum_record = get ("forums/index", "id>$f", "");

if ((empty ($forum_record)) || (may_see ($forum_record) == false)) {

  $error = true;
  $list = "No such forum...";

}
else {

  $error = false;
  $forum_name = valueOf ($forum_record, "name");
  $witheld_tracking = (valueOf ($forum_record, "istrashed") == "yes") ? true : false;

}

/*
 *
 * visually show warnings in place of "post new thread" options:
 *
 * but keep linking option to the postform, if user has administration rights,
 * else link to "locked.php", which is only a warning message. Of course, even
 * if this part kept linking to "postform.php", postform would still keep user
 * from posting a new thread in a locked or closed forum...
 *
 */

if ($error == false) {

  $is_locked = false;

  if (valueOf ($forum_record, "isclosed") == "yes") {

    $is_locked = true;
    $option = "This forum is closed!";

  }

  if (valueOf ($forum_record, "islocked") == "yes") {

    $is_locked = true;
    $option = "This forum is locked!";

  }

  if ($is_locked) {

    if ($is_admin) {

      $optext = "yet, administrators may create new topics here";

    }
    else {

      $opicon = "f";
      $oplink = "locked.php";
      $optext = "sorry, you cannot create new topics here";

    }

  }

}

/*
 *
 * if logged in, check recent discussions archive and which discussions this member has viewed,
 * to later highlight the threads holding unread posts... the process fills two arrays:
 *
 * $urd = UnReaD posts flag (indexed by thread ID, either true or not set)
 * $fup = ID of First Unread Post (indexed by thread ID, numeric)
 *
 */

$urd = array ();
$fup = array ();

if (($login) && ($error == false)) {

  $recent_posts = all ("forums/recdisc", asIs);
  $viewed_recent_posts = getvrd ($id);

  foreach ($recent_posts as $r) {

    list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);

    /*
      the following means, to add an entry to the above arrays if:
      - $urd flag for this thread has not yet been set (so it only processes first unread post)
      - message ID $_m doesn't appear in member's VRD table (making it an unread post)
    */

    if ($urd[$_t] !== true) {
      if (in_array ($_m, $viewed_recent_posts) == false) {

        $urd[$_t] = true;
        $fup[$_t] = $_m;

      }
    }

  }

}

/*
 *
 * generate list of logged-in users browsing this forum
 *
 */

if ($witheld_tracking == true) {

  $userslist = "staff members are not tracked while browsing hidden forums";

}
else {

  $sessions = all ("stats/sessions", makeProper);
  $c = count ($sessions);
  $userslist = array ();

  if ($c) {

    /*
      start from first session record,
      loop for each session record and upto a given limit ($userslistsize as of "settings.php")
    */

    $n = 0;

    while (($n < $c) && ($n < $userslistsize)) {

      /*
        is this user not the viewer? is this session active? (did not expire yet)
        it checks because there may be expired sessions not yet removed from "stats/sessions"
      */

      $_id = intval (valueOf ($sessions[$n], "id"));
      $_beg = intval (valueOf ($sessions[$n], "beg"));

      if (($_id != $id) && (($_beg + $sessionexpiry) >= $epc)) {

        /*
          retrieve nickname
        */

        $_nick = valueOf ($sessions[$n], "nick");

        /*
          get last tracked URI:
          seeking for "threads.php" where argument "f" corresponds to this forum's ID...
        */

        list ($arg_name, $arg_value) = explode ("=", getlsp ($_id));
        list ($arg_value) = explode ("&", $arg_value);

        if ($arg_name == "{$path_to_postline}/posts.php?t") {

          /*
            this user is reading a thread
          */

          $r_tid = intval ($arg_value);
          $r_fid = intval (get ('forums' . intervalOf ($r_tid, $bs_threads), "id>$r_tid", 'fid'));

          if ($r_fid == $f) {

            /*
              this user is reading a thread within THIS forum:
              append entry to accumulator...
            */

            $profile_link = "profile.php?nick=" . base62Encode ($_nick);
            $userslist[] = "<a target=pst href=$profile_link>$_nick</a>";

          }

        }

        if ($arg_name == "{$path_to_postline}/threads.php?f") {

          /*
            this user is browsing a forum's index
          */

          if ($arg_value == $f) {

            /*
              this user is browsing THIS forum's index:
              append entry to accumulator...
            */

            $profile_link = "profile.php?nick=" . base62Encode ($_nick);
            $userslist[] = "<a target=pst href=$profile_link>$_nick</a>";

          }

        }

      }

      $n ++;

    }

  }

  if (count ($userslist) == 0) {

    /*
      there's nobody browsing this forum
    */

    $userslist = "no" . chr (32) . (($login == true) ? "other" . chr (32) : "") . "members are browsing this forum";

  }
  else {

    /*
      concatenate nicknames in list
    */

    $rest = (($login == true) ? $c - 1 : $c) - $userslistsize;
    $end_list = ($rest > 0) ? ", and another {$rest} member" . (($rest == 1) ? "" : "s") : "";
    $userslist = implode ("," . chr (32), $userslist) . $end_list . ".";

  }

}

/*
 *
 * generate forum threads listing
 *
 */

if ($error == false) {

  /*
    start by initializing table header,
    and adding a "fake" thread entry on top to provide the link to post a new thread
  */

  $list = "<table class=w>"
        .  "<tr>"
        .   "<td class=tc>"
        .    "<a href=$oplink target=pst>"
        .     "<img src=layout/images/thread$opicon.png width=48 height=48 border=0>"
        .    "</a>"
        .   "</td>"
        .   "<td colspan=2 class=te>"
        .    "<div class=tr>"
        .     "<a href=$oplink target=pst>"
        .      $option
        .     "</a>"
        .    "</div>"
        .    "<div class=td>"
        .     $optext
        .    "</div>"
        .   "</td>"
        .  "</tr>"
        .  "<tr>"
        .   "<td class=sp>"
        .   "</td>"
        .  "</tr>";

  /*
    this Y coordinate is used for special signals (unread posts, polls...) and is refered
    to the top edge of the frame holding this HTML output: values that are not constant
    may be set by "template.php" to match the position of signals to the template...
  */

  $list_y = $t_base_yoffset + 48 + $t_icon_spacing;

  /*
    read sticky threads and normal threads directories:
    they are separated files, for easier management...
  */

  $stickys = all ("forums/st-$f", makeProper);
  $threads = array_merge ($stickys, all ("forums/th-$f", makeProper));

  /*
    count normal threads, count sticky threads (compared to $c, determines where stickies end),
    loop throught $threads array using index $t, starting with a zero threads count as $n,
    until end of array ($t >= $c) or end of page ($n >= $threadsperpage)
  */

  $c = count ($threads);
  $k = count ($stickys);

  $n = 0;
  $t = $page * $threadsperpage;

  while (($t < $c) && ($n < $threadsperpage)) {

    /*
      get thread ID from entry $threads[$t] in threads' directory $threads,
      locate database file holding corresponding thread record, read the record,
      check if the record effectively exists and if yes, append entry to the list...
    */

    $tid = intval (valueOf ($threads[$t], "id"));
    $tdb = "forums" . intervalOf ($tid, $bs_threads);

    $thread_record = get ($tdb, "id>$tid", "");
    $record_exists = (empty ($thread_record)) ? false : true;

    if ($record_exists) {

      /*
        retrieve general informations about this thread
      */

      $title = valueOf ($thread_record, "title");                       // title
      $posts = wExplode (";", valueOf ($thread_record, "posts"));       // posts array
      $number_of_posts = count ($posts);                                // number of posts
      $starter = valueOf ($thread_record, "starter");                   // author of thread
      $time = intval (valueOf ($thread_record, "date"));                // epoch of creation
      $date = gmdate ("M d, Y", $time);                                 // date of creation
      $event = valueOf ($thread_record, "last");                        // history entry
      $mover = valueOf ($thread_record, "moveby");                      // who moved it
      $retitler = valueOf ($thread_record, "retitleby");                // who retitled it

      /*
        setup link to profile of thread's author at recorded date
      */

      $starter_link =

        "<a target=pst href=profile.php?nick=" . base62Encode ($starter) . "&amp;at=$time>$starter</a>";

      /*
        setup paging links (for this thread, not for the whole list of threads)
      */

      $ispaged = (valueOf ($thread_record, "ispaged") == "yes") ? true : false;
      $ppp = ($ispaged) ? 1 : $postsperpage;

      $pages = (int) (($number_of_posts - 1) / $ppp);
      $paging = ($pages + 1) . chr (32) . "page" . (($pages > 0) ? "s" : "");

      /*
        setup thread icon name (thread, threadl, threadp)
      */

      $icon  = "layout/images/thread";
      $icon .= (valueOf ($thread_record, "islocked") == "yes") ? "l" : "";
      $icon .= ($t < $k) ? "p" : "";

      /*
        setup links to jump to First Unread Post ($fup_open, $fup_close),
        or normally reach the thread's first page by clicking its title ($url_open, $url_close)
      */

      $in = valueOf ($threads[$t], "to");       // thread entry includes "to" if it's a ghost

      if (empty ($in)) {

        /*
          if thread wasn't moved, the target forum ID in links will be the $f argument,
          and links to last post and to first page are always provided as normal...
        */

        $in = $f;
        $link = true;

      }
      else {

        /*
          if thread WAS moved, the target forum ID for links is given by the "to" field
          of the thread's entry in the directory ($threads[$t]), but no link will be
          provided if the ghost is that of a thread that has been moved to a trashed forum,
          which is conventionally assumed to mean the deletion of the whole thread...
        */

        $link = (get ("forums/index", "id>$in", "istrashed") == "yes") ? false : true;

      }

      if ($link) {

        $url_open = "<a " . link_to_posts ("", $tid, "", "", false) . ">";
        $url_close = "</a>";

        if ($urd[$tid]) {

          $fup_open = "<a " . link_to_posts ("", $tid, "of{$fup[$tid]}", $fup[$tid], false) . ">";
          $fup_close = "</a>";

        }

        if (valueOf ($thread_record, "nl") == "yes") {

          $url_open = "<a " . link_to_posts ("", $tid, "last", "lastpost", false) . ">";

        }

      }
      else {

        $url_open = "";
        $url_close = "";
        $fup_open = "";
        $fup_close = "";

      }

      /*
        setup history notice to show what happened to the thread most recently:
        history could be void, if a moderator decides to erase it, so check for it
      */

      $event_epoch = intval (valueOf ($thread_record, "laston"));

      if ($event_epoch == 0) {

        $event = "";

      }
      else {

        $event_date = gmdate ("M d, y", $event_epoch);
        $event_time = gmdate ("H:i", $event_epoch);

        $event = strBefore ($event, chr (32) . "here") . chr (32) . "[$event_date]";

      }

      /*
        has someone ever moved the thread?
        if yes, where? setup $moving notice...
      */

      if (empty ($mover)) {

        $moving = "";

      }
      else {

        $actual_forum = valueOf ($thread_record, "moveto");
        $former_forum = valueOf ($thread_record, "movefrom");

        if ($f == $in) {

          /*
            thread was moved HERE from another forum
          */

          $moving = "moved here from &quot;$former_forum&quot by $mover";

        }
        else {

          /*
            thread was moved somewhere else from THIS forum (and the icon is a ghost)
          */

          $icon .= "h";
          $altt .= ", that's been moved to another forum";

          $moving = "moved to &quot;$actual_forum&quot; by $mover";

        }

      }

      /*
        has someone ever retitled the thread?
        if yes, from which title? setup $retitling notice...
      */

      if (empty ($retitler)) {

        $retitling = "";

      }
      else {

        $former_title = valueOf ($thread_record, "retitlefrom");
        $retitling = "originally &quot;$former_title&quot;, last re-titled by $retitler";

      }

      $history_notes = implode (", ", wExEmpty (array ($retitling, $moving)));
      $last_post_link = link_to_posts ("", $tid, $pages, "lastpost", false);

      /*
        setup thread entry highlight (when new posts are present)
      */

      $npHighlight =

           (($urd[$tid]) && ($ofie != "yes"))

            ? blank . "style=\"background-image:url(layout/images/slot{$graphicsvariant}n.png)\""
            : "";

      /*
        setup unread post "star" icon
      */

      $news =

           ($urd[$tid])

            ? "<img"
            . " style=position:absolute;left:{$t_news_xoffset}px;top:" . ($list_y + $t_news_yoffset) . "px"
            . " src=layout/images/star.png width=24 height=24 border=0>"
            : "";

      /*
        setup additional signals ($poll, $hist)
      */

      $signal_x = $t_sign_xoffset;
      $signal_y = $list_y + $t_sign_yoffset;

      $poll =

           (valueOf ($thread_record, "poll") == "")

            ? ""
            : "<img"
            . " style=position:absolute;right:{$signal_x}px;top:{$signal_y}px"
            . " src=layout/images/poll.png width=24 height=24 border=0>";

      $signal_x += (empty ($poll)) ? 0 : $t_sign_spacing;

      $hist =

          (((empty ($retitling)) && (empty ($moving)))

            ? ""
            : "<a href=\"javascript:alert('" . addslashes ($history_notes) . "')\">"
            .  "<img"
            .  " style=position:absolute;right:{$signal_x}px;top:{$signal_y}px"
            .  " src=layout/images/last.png width=24 height=24 border=0>"
            . "</a>"

            );

      /*
        it's all ready to append this thread to the list:
        generating entry's HTML below...
      */

      $list .= "<tr>"
            .   "<td class=tc{$npHighlight}>"
            .    $url_open
            .     "<img src=$icon.png width=48 height=48 border=0>"
            .    $url_close
            .    $fup_open
            .     $news
            .    $fup_close
            .    $poll
            .    $hist
            .   "</td>"
            .   "<td class=te{$npHighlight}>"
            .    "<div class=tr>"
            .     $url_open
            .      $title
            .     $url_close
            .     "&nbsp;<a $last_post_link title=\"see last post\"><img src=layout/images/lastpage.png width=14 height=12></a>"
            .    "</div>"
            .    "<div class=td>"
            .     "by $starter_link"
            .

          ((empty ($event))

                ? ""
                : "; $event"

            )

            .    "</div>"
            .   "</td>"
            .   "<td class=pc{$npHighlight}>"
            .    "&nbsp;$number_of_posts posts,<br>"
            .    "&nbsp;<a $last_post_link title=\"see last post\">$paging</a>"
            .   "</td>"
            .  "</tr>";


      /*
        append an horizontal rule if this entry is the last sticky thread, to make stickies
        evidently separated from normal threads and give them more importance: $c > $k means
        that there actually ARE stickies in the thread's directory (but there may be none...)
      */

      if (($c > $k) && ($t == $k - 1)) {

        $list .= "<tr>"
              .   "<td>"
              .   "</td>"
              .   "<td colspan=2 class=pp>"
              .   "</td>"
              .  "</tr>";

        $list_y += $t_rule_spacing;     // height of the horizontal rule following pinned threads

      }

      /*
        append a common spacer to separate all entries in the list,
        accordingly update $list_y to also account for the height of an entry ($t_icon_spacing)
      */

      if ((($t + 1) < $c) && (($n + 1) < $threadsperpage)) {

        $list .= "<tr>"
              .   "<td class=sp>"
              .   "</td>"
              .  "</tr>";

        $list_y += 48 + $t_icon_spacing;

      }

    }

    $t ++;      // increase thread index in $threads array
    $n ++;      // increase listed threads count for this page

  }

  /*
    close entries table
  */

  $list .= "</table>";

  /*
    setup paging links:
    this time, for the whole list of threads
  */

  $pages = (int) ($c / $threadsperpage);

  if ($pages == 0) {

    $paging = "p. <a class=alert>1</a>";

  }
  else {

    $div = ($pages > $pp_forum) ? " ... " : chr (32);

    $paging =

          (($page == 0)

            ? "p. <a class=alert>1</a>"                                 // when already on page 1
            : "p. <a class=fl href=threads.php?f=$f&amp;p=0>1</a>"      // clickable link to page 1

          )

            . $div;

    $number = ($page < $pp_forum) ? 1 : $page - $pp_forum + 1;
    $nlimit = ($number + (2 * $pp_forum - 1)  >= $pages) ? $pages : $number + (2 * $pp_forum - 1);

    while ($number < $nlimit) {

      $paging .= chr (32)
              .

            (($page == $number)

              ?  "<a class=alert>"
              :  "<a class=fl href=threads.php?f=$f&amp;p=$number>"

            )

              .  ($number + 1) . "</a>";

      $number ++;

    }

    $paging .= $div
            .

          (($page == $pages)

            ? "<a class=alert>" . ($pages + 1) . "</a>"
            : "<a class=fl href=threads.php?f=$f&amp;p=$pages>" . ($pages + 1) . "</a>"

          );

  }

}

/*
 *
 * determine permalink presence (present if no arguments given, other than forum ID)
 *
 */

unset ($_GET["f"]);
unset ($_POST["f"]);

$args_not_given = ((count ($_GET)) || (count ($_POST))) ? false : true;

/*
 *
 * permalink initialization
 *
 */

$permalink = (($enable_permalinks) && ($args_not_given) && ($error == false))

  ? str_replace

      (

        "[PERMALINK]", $perma_url . "/" . $threads_keyword . "/" . $f, $permalink_model

      )

  : "";

if ((isset ($forum_permas[$f])) && ($page == 0)) {

  $permalink = (($enable_permalinks) && ($error == false))

    ? str_replace

        (

          "[PERMALINK]", $perma_url . "/" . $forum_permas[$f], $permalink_model

        )

    : "";

}

/*
 *
 * template initialization
 *
 */

$forum_threads =
  str_replace (

    array (

      "[FORUM]",
      "[PAGING]",
      "[USERSLIST]",
      "[LIST]",
      "[PERMALINK]"

    ),

    array (

      "<a class=fl href=threads.php?f=$f>$forum_name</a>",
      $paging,
      $userslist,
      $list,
      $permalink

    ),

    $forum_threads

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

if (($login == true) && ($nspy != "yes") && ($witheld_tracking == false)) {

  $this_uri = substr ($_SERVER["REQUEST_URI"], 0, 100);

  if (getlsp ($id) != $this_uri) {

    setlsp ($id, $this_uri);

  }

}

/*
 *
 * releasing locked session frame
 *
 */

echo (pquit ($forum_threads));



?>
