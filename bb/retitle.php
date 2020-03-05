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
 * initialize accumulators
 *
 */

$form = "";                     // page output accumulator
$hint = "";                     // additional hint appended to confirmation message
$error = false;                 // error flag: if false, shows confirmation message
$dontrememberthisurl = false;   // true, discards URL tracker duties to avoid repeating actions

unset ($parent_forum);

/*
 *
 * this warning is presented along with error messages claiming a title or message was too long:
 * it might explain the user why the message looks shorter than the limit, but still doesn't get
 * throught the said checks...
 *
 */

$string_length_warning =

  "<hr>"
. "If you believed it was shorter than the limit, please be aware of the fact that a few "
. "special characters are being 'escaped' while your post is being processed: characters like "
. "the greater/lower than signs (&lt; &gt;), ampersands (&amp;) and doublequotes (&quot;) are "
. "transformed into special tags that are <u>longer</u> than one character. After such filter "
. "has been applied, the resulting text may have grown well beyond the limit. And please note "
. "that it's no mistake: it's just the way this board works. You might so consider shortening "
. "your entry, or diminish your use of the above special characters.";

/*
 *
 * get simple parameters
 *
 */

$submit = isset ($_POST["submit"]);     // submission trigger
$t = intval (fget ("t", 1000, ""));     // target thread ID (indicates thread to retitle)

/*
 *
 * check security code on submission of retitler's form
 *
 */

if ($submit) {

  $code = intval (fget ("code", 1000, ""));
  $submit = ($code == getcode ("pst")) ? true : false;

}

/*
 *
 * retrieve submitted title:
 * limit is set to a conventional megabyte of text, which might be well below the maximum
 * size of a post, to later check if any of these fields turn out to be longer than allowed.
 *
 */

$title = fget ("title", 1000000, "");

/*
 *
 * retrieve submitted poll options:
 * a little post-processing, other than the checks made by "fget", is necessary to ensure
 * that the pipe, or vertical bar, (|), does not occur among the options, because it will
 * be used to save voters' informations. Also any carriage returns here are translated to
 * simple blank spaces, as well as any whitespace formatters (eg. tabs); finally, for any
 * multiple occurrences of a blank space, a single space is preserved.
 *
 */

$poll = fget ("poll", 1000000, chr (32));
$poll = str_replace ("|", chr (32), $poll);
$poll = preg_replace ("/\s{2,}/", chr (32), $poll);

/*
 *
 * bugfix [3/13/2006]
 *
 * replace &lt; &gt; &quot; in poll options (semicolon separates options):
 * less than/greater than are replaced with blanks, doublequotes with double single quote
 *
 */

$poll =

  str_replace (

    array ("&quot;", "&lt;", "&gt;"),
    array (chr (39) . chr (39), chr (32), chr (32)),

    $poll

  );

/*
 *
 * replace carriage returns in form input versions of the above fields:
 * yes, there could be existing text even if this is not a final submission, due to errors:
 * preserving these fields avoids the user to have to retype them if they don't get throught.
 *
 */

$title_in = str_replace ("&;", "\n", $title);
$poll_in = $poll;

/*
 *
 * thread retitling operations
 *
 */

if ($login == true) {

  if ($submit == false) {

    /*
      if it's no submission, retrieve title and poll options of thread to retitle:
      they'll eventually show in the form (indicatively)...
    */

    $error = true;

    $tdb = "forums" . intervalOf ($t, $bs_threads);
    $thread_record = get ($tdb, "id>$t", "");
    $parent_forum = intval (valueOf ($thread_record, "fid"));
    $forum_record = get ("forums/index", "id>$parent_forum", "");

    if ((empty ($thread_record)) || (empty ($forum_record)) || (may_see ($forum_record) == false)) {

      /*
        either the thread record doesn't exist, or its parent forum ID doesn't exist,
        or its parent forum is hidden, and members are not allowed to know it exists...
      */

      $form = "(unknown thread)";

    }
    else {

      /*
        alright, thread is in a public forum:
        eventually, you can pre-load the title of the thread, but only if member didn't
        re-load the page after making changes to the title already, or changes would be
        obviously discarded, so chech that there is no text in the title field, then do
        the same for the poll options field...
      */

      $form = "Retitling thread #$t";

      if (empty ($title_in)) {

        $title_in = valueOf ($thread_record, "title");
        $title_in = str_replace ("&;",  "\n",  $title_in);

      }

      if (empty ($poll_in)) {

        $poll_options = wExplode (";", valueOf ($thread_record, "poll"));
        $options_list = array ();

        foreach ($poll_options as $o) {

          list ($option) = explode ("|", $o);
          $options_list[] = $option;

        }

        $poll_in = implode (";" . chr (32), $options_list);

      }

    }

  }
  else {

    /*
      retitling submission:
      set flag to witheld tracking of this URL (avoid to repeat the submit on reloads).
    */

    $dontrememberthisurl = true;

    /*
      obtain exclusive write access:
      database manipulation ahead...
    */

    lockSession ();

    /*
      locate target thread record, and that of its parent forum
    */

    $tdb = "forums" . intervalOf ($t, $bs_threads);
    $thread_record = get ($tdb, "id>$t", "");
    $parent_forum = intval (valueOf ($thread_record, "fid"));
    $forum_record = get ("forums/index", "id>$parent_forum", "");

    if ((empty ($forum_record)) || (empty ($thread_record)) || (may_see ($forum_record) == false)) {

      /*
        either the forum, or the thread entry, haven't been found in the database,
        or this user isn't allowed to know they exist (because the forum is hidden)
      */

      $error = true;

      $form = "Can't find the forum...//...this thread belongs to. Maybe the parent forum "
            . "has been deleted in the meantime. In the worst case, there may be troubles "
            . "with the forums' index.";

    }
    else {

      /*
        thread exists AND this member can see that it exists:
        - is this thread belonging to the user that triggered the retitling request?
        - are the thread, or its parent forum, locked, by any chance?
      */

      $is_starter = (valueOf ($thread_record, "starter") == $nick) ? true : false;
      $is_not_locked = (valueOf ($thread_record, "islocked") == "yes") ? false : true;

      $thread_started_on = intval (valueOf ($thread_record, "date"));
      $is_starter = ($thread_started_on < $jtime) ? false : $is_starter;

      $forum_access = may_access ($forum_record);
      $thread_access = ( ($is_admin) || ($is_mod) || (($is_starter) && ($is_not_locked)) ) ? true : false;

      if (($forum_access == true) && ($thread_access == true)) {

        /*
          all authorization checks passed:
          forum and thread exist AND this member can make changes to the thread
          - there may be, however, a limitation on until when it's possible to
            edit ($canedit), but it won't affect moderators and administrators.
        */

        $canedit = ((!$posteditdelay) || ($ctime + $posteditdelay >= $epc)) ? true : false;

        if (($is_admin) || ($is_mod) || ($canedit)) {

          /*
            clear record change flags:
            if one is true in the end, the record will be effectively saved
          */

          $title_changed = false;
          $poll_changed = false;

          /*
            check if the title has been changed:
            if yes, add retitling informations to thread record's history
          */

          $former_title = valueOf ($thread_record, "title");

          if ($former_title != $title) {

            /*
              check if new title fits maximum length of a thread's title ("settings.php")
            */

            if (strlen ($title) <= $maxtitlelen) {

              /*
                on multiple retitling operations, remember the original title
                as part of the thread's history...
              */

              $original_title = valueOf ($thread_record, "retitlefrom");
              $former_title = (empty ($original_title)) ? $former_title : $original_title;

              /*
                update thread record
              */

              $thread_record = fieldSet ($thread_record, "title", $title);
              $thread_record = fieldSet ($thread_record, "retitleby", $nick);
              $thread_record = fieldSet ($thread_record, "retitlefrom", $former_title);
              $thread_record = fieldSet ($thread_record, "last", "$nick retitled the thread");
              $thread_record = fieldSet ($thread_record, "laston", strval ($epc));

              /*
                set flag to indicate that there's been effective changes to the record
              */

              $title_changed = true;

            }
            else {

              $hint = "...but the new title exceeds $maxtitlelen characters and, "
                    . "sorry, is therefore longer than allowed. " . $string_length_warning;

            }

          }

          /*
            manage addition or removal of associated polls
          */

          if (empty ($poll)) {

            /*
              submitted poll options list is empty:
              if it formerly wasn't, record that this user removed the poll, and clear voters
            */

            $former_poll = valueOf ($thread_record, "poll");

            if (!empty ($former_poll)) {

              $thread_record = fieldSet ($thread_record, "poll", "");
              $thread_record = fieldSet ($thread_record, "voters", "");
              $thread_record = fieldSet ($thread_record, "poll_close", "");
              $thread_record = fieldSet ($thread_record, "last", "$nick removed the poll");
              $thread_record = fieldSet ($thread_record, "laston", strval ($epc));

              $poll_changed = true;

              $hint = "...and poll removed successfully. Tip: if you want "
                    . "to run a new poll in this same thread, you may use "
                    . "the &quot;make further changes&quot; link and write the "
                    . "new options. Since you have removed the previous poll, "
                    . "everyone will be able to vote in the new poll again.";

            }

          }
          else {

            /*
              submitted poll options list is not empty:
              check if it fits the maximum length of a poll's options list ("settings.php")
            */

            if (strlen ($poll) <= $maxpolllen) {

              /*
                filter submitted options list, removing any void or duplicated options
              */

              $submitted_options = wExplode (";", $poll);
              $valid_options = array ();

              foreach ($submitted_options as $o) {

                $o = trim ($o);

                $is_not_void = (empty ($o)) ? false : true;
                $is_no_duplicate = (in_array ($o, $valid_options)) ? false : true;

                if (($is_not_void) && ($is_no_duplicate)) $valid_options[] = $o;

              }

              /*
                compare new list ($valid_options) against former list ($existing_options):
                to any former option that's still present in the new list is left its count
                of votes; to any new option that wasn't in the former list is assigned an
                initial count of zero voters; the final array will be $updated_poll_options.
              */

              $unchanged_options = array ();
              $updated_poll_options = array ();

              $existing_poll = valueOf ($thread_record, "poll");
              $existing_options = explode (";", $existing_poll);

              foreach ($existing_options as $o) {

                list ($option) = explode ("|", $o);

                if (in_array ($option, $valid_options)) {

                  $updated_poll_options[] = $o;
                  $unchanged_options[] = $option;

                }

              }

              $new_options = wArrayDiff ($valid_options, $unchanged_options);
              $updated_poll_options = array_merge ($updated_poll_options, $new_options);

              /*
                finally checking if something has changed in the poll,
                and eventually storing the new list of options and voters in thread's record...
                hm... yes, in the "history", a change in the poll's options replaces the notice
                about a possible retitling of the thread: it's considered to be more important.
              */

              $updated_poll = implode (";", $updated_poll_options);

              if ($updated_poll != $existing_poll) {

                $act = (empty ($existing_poll)) ? "started a poll" : "changed poll's options";

                $thread_record = fieldSet ($thread_record, "poll", $updated_poll);
                $thread_record = fieldSet ($thread_record, "last", "$nick $act");
                $thread_record = fieldSet ($thread_record, "laston", strval ($epc));

                $poll_changed = true;

                $hint = "...and poll updated successfully.";

              }

            }
            else {

              $hint = "...but the poll wasn't changed, because its list of "
                    . "options exceeds $maxpolllen characters, which is the "
                    . "limit for those lists. Please try being a bit more "
                    . "synthetic writing the options' list." . $string_length_warning;

            }

          }

          /*
            now, if either title or poll have changed, write back updated thread's record,
            else output a warning notice: submission was received but no changes were made
          */

          if (($title_changed) || ($poll_changed)) {

            set ($tdb, "id>$t", "", $thread_record);

          }

          /*
            only if the title has changed, also change it in the recent discussions' archive,
            if there are records matching the former title of the thread...
          */

          if ($title_changed) {

            /*
              load recent discussions archive building an array ($rd_a) out of its
              newline-delimited records, initialize filtered array ($rd_f) to hold
              the new version of the archive, scan $rd_a looking for all records
              where the thread ID ($_t) corresponds to this thread ($t), replace
              former thread title ($_T) with new thread title ($title), and finally
              write back the archive if at least one record was changed ($rd_m).
            */

            $rd_a = all ("forums/recdisc", asIs);
            $rd_f = array ();
            $rd_m = false;

            foreach ($rd_a as $r) {

              list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);

              if ($_t == $t) {

                $rd_m = true;
                $_T = $title;

                $rd_f[] = implode (">", array ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t));

              }
              else {

                $rd_f[] = $r;

              }

            }

            if ($rd_m) {

              asm ("forums/recdisc", $rd_f, asIs);

            }

          }

        }
        else {

          $error = true;

          $form = "Sorry, you're out of time.//"
                . "The time span allowed for changes to this thread has expired. "
                . "From now on, only moderators may change this thread: this is "
                . "to keep people from continuously editing threads, making the "
                . "replies eventually look off-topic.";

        }

      }
      else {

        $error = true;

        $form = "Sorry, access denied.//"
              . "Either this thread, or its parent forum, has been locked. Because of this, "
              . "at the moment only moderators and administrators are allowed to post there.";

      }

    }

  }

}
else {

  $error = true;

  $form = "You are not logged in.//<a href=mstats.php>go to login panel</a>";

}

/*
 *
 * form initialization
 *
 */

if ($error == false) {

  /*
    confirmation message
  */

  $hint .= (empty ($hint)) ? "" : "<hr>";

  $form =

      makeframe (

          "Changes acquired!",

          $hint

        .     "<a " . link_to_posts ("pan", $t, "", "", true) . ">Show changes</a>"
        . "<br><a href=retitle.php?t=$t>Make further changes</a>"
        . "<br><a href=postform.php?t=$t>Post in this thread</a>"
        . "<br><a " . link_to_threads ("pan", $parent_forum, "") . ">Index of this forum</a>"
        . "<br><a " . link_to_forums ("pan") . ">Index of all forums</a>", true

      );

}
else {

  /*
    build retitling form
  */

  list ($frame_title, $frame_contents) = explode ("//", $form);

  $form =

        makeframe (

          $frame_title, false, false

        )

        .

      (($frame_contents)

        ?

        makeframe (

          "details", $frame_contents, true

        )

        : "");

  /*
    if not logged in, all the rest is witheld:
    it will only output an error message ("you are not logged in")
  */

  if ($login == true) {

    $form .= "<table width=$pw>"
          .  "<form name=retitler action=retitle.php?t=$t enctype=multipart/form-data method=post>"
          .  "<input type=hidden name=code value=" . setcode ("pst") . ">"
          .   "<td height=20 class=inv align=center>"
          .    "thread title, topic:"
          .   "</td>"
          .  "</table>"

          .  $inset_bridge

          .  $opcart
          .   "<td class=in>"
          .    "<input class=sf style=width:{$iw}px type=text name=title value=\"$title_in\" maxlength=$maxtitlelen>"
          .   "</td>"
          .  $clcart

          .  $inset_shadow

          .  "<table width=$pw>"
          .   "<td height=20 class=inv align=center>"
          .    "poll options:"
          .   "</td>"
          .  "</table>"

          .  $inset_bridge

          .  $opcart
          .   "<td>"
          .    "<textarea style=width:{$iw}px;height:160px name=poll>$poll_in</textarea>"
          .   "</td>"
          .  $clcart

          .  $inset_shadow

          .  "<table width=$pw>"
          .   "<td height=20 class=inv align=center>"
          .    "operations:"
          .   "</td>"
          .   $bridge
          .   "<td>"
          .    "<input class=su style=width:{$pw}px type=submit name=submit value=\"retitle + add/change poll\">"
          .   "</td>"
          .   $shadow
          .  "</form>"
          .  "</table>"
          .

        makeframe (

             "Instructions:",

             "Above is a form to retitle the thread. "
          .  "The field on top holds the title of the thread, which you may change (there is a "
          .  "$maxtitlelen characters limit, though). You may also change the options of "
          .  "a poll associated with this thread, or write a list of options in the larger "
          .  "field below the thread's title, to add a poll now. If some options are showen "
          .  "in the said field, it means a poll is already present: options must be separated "
          .  "by semicolons (;). You may change (or remove) some of the existing options, "
          .  "but if you do, the count of votes those options got so far will be reset to "
          .  "zero. If you delete all the options in the poll, the poll itself will disappear. "
          .  "If you then write a new set of options (or even the same set) you will obtain a "
          .  "new version of the poll in the same thread, allowing everyone to vote again.", true

        )

          .  "\n"
          .  "<script language=Javascript type=text/javascript>\n"
          .  "<!--\n"
          .    "document.retitler.title.focus();\n"
          .  "//-->\n"
          .  "</script>\n";

  }

}

/*
 *
 * template initialization
 *
 */

$postpanel = str_replace

  (

    array

      (

        "[FORM]",
        "[MODCP]",
        "[PERMALINK]"

      ),

    array

      (

        $form,
        "",
        $permalink

      ),

    $postpanel

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page upon showing or
 * hiding the chat frame, and for the online members list links (unless no-spy flag checked).
 *
 */

if ($dontrememberthisurl == false) {

  include ("setlfcookie.php");

}

/*
 *
 * releasing locked session frame
 *
 */

echo (pquit ($postpanel));



?>
