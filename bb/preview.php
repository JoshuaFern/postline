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
 * initialize output accumulators
 *
 */

$list = "";

/*
 *
 * arguments to preview new threads (poll options: see "postform.php" for comments on processing)
 *
 */

$poll = fget ("poll", $maxpolllen, chr (32));
$poll = str_replace ("|", chr (32), $poll);
$poll = preg_replace ("/\s{2,}/", chr (32), $poll);

/*
 *
 * bugfix [7/6/2006]
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
 * arguments to preview replies
 *
 */

$tid = intval (fget ("tid", 1000, ""));                 // thread ID

/*
 *
 * arguments to preview either replies or new threads (which apply in both cases)
 *
 */

$forum = fget ("forum", 1000, chr (32));                // forum title
$fid = intval (fget ("fid", 1000, ""));                 // forum ID
$title = fget ("title", $maxtitlelen, chr (32));        // thread title
$author = fget ("author", 1000, "");                    // author of previewed message
$message = fget ("message", $maxmessagelen, "&;");      // post text (preview shows truncation)

/*
 *
 * determining speech ballon color
 *
 */

if (isset ($_POST["editing"])) {

  /*
    on editing existing messages,
    get speech balloon color from arguments
  */

  $bcol = intval (fget ("bcol", 10, ""));

}
else {

  /*
    when default speech balloon color is set to -1, it's to be picked randomly, so provide:
    it must be done because index -1 doesn't exist in the speech bubble colors table,
    it's only a special marker to mean "random"...
  */

  $bcol = ($bcol == -1) ? mt_rand (0, count ($bcols) - 2) : $bcol;

}

/*
 *
 * in any cases, normalize speech bubble color ID to existing table limits:
 * - at least one row (ID 0) must be given in the said table (see template.php)
 *
 */

$bcol = ($bcol < 0) ? 0 : $bcol;
$bcol = ($bcol < count ($bcols) - 1) ? $bcol : count ($bcols) - 2;

/*
 *
 * collapse adjacent text formatters in title and poll options list
 *
 */

$title = preg_replace ("/" . chr (32) . "{2,}/", chr (32), $title);
$poll = preg_replace ("/" . chr (32) . "{2,}/", chr (32), $poll);

/*
 *
 * if no title provided, make the title a warning message:
 * - in threads, say there's no thread title;
 * - in private messages, say there's no subject line;
 * - else, it's a reply, and the title doesn't count.
 *
 */

if (empty ($title)) {

  if (isset ($_POST["pm"])) {

    $title = "WARNING: NO SUBJECT LINE";

  }
  elseif (isset ($_POST["thread"])) {

    $title = "WARNING: NO THREAD TITLE";

  }

}

/*
 *
 * if no post text is given, make the text a warning message:
 * - this is often due to the user agent not handling Javascripts, since fields
 *   inputted in post forms are copied to the preview form with a javascript...
 *
 */

if (empty ($message)) {

  $message = "&lt;h&gt;&lt;c&gt;--- WARNING: NO MESSAGE BODY ---&lt;/c&gt;&lt;/h&gt;"
           . "A possible problem if you can't see your text in this preview would be that "
           . "your actual browser does not support javascripts, or that javascripts are "
           . "disabled. The preview functionality needs a simple and harmless script to "
           . "copy text fields from the effective form to a &quot;hidden form&quot; that "
           . "shares the data to preview.";

}

/*
 *
 * initialize rendering parameters:
 *
 * - by convention, when the author's nickname is provided it's an effective preview;
 * - when no nickname is provided, it's because this script was called by someone who
 *   clicked the link for what was someone else's viewing, either from someone else's
 *   profile, or from the online members list: in that case report that such previews
 *   are (of course) never tracked.
 *
 */

if (empty ($author)) {

  /*
    no author nickname: it's supposed to be an alert (see above comments)
  */

  $nick = $servername;  // a fake nickname
  $title = "";          // no title
  $poll = "";           // no poll

  $message = "This member is either doing a post preview for a message to be posted in "
           . "the public forums, or a preview of a private message, but such informations "
           . "are obviously not being tracked, therefore they are not publicly available.";

  $bcol = $default_bcol;                        // default speech balloon color
  $hhip = "yes";                                // force help not to show: it'd be useless

}
else {

  /*
    but even if it's an effective preview, it could be anonymous:
    this happens, for example, for shared password warnings sent by moderators
  */

  if (isset ($_POST["anon"])) {

    $nick = $servername;        // a fake nickname
    $bcol = $default_bcol;      // default speech balloon color
    $hhip = "yes";              // force help not to show: it'd be useless

  }

}

/*
 *
 * count options in poll:
 * if options are given, render the poll
 *
 */

$opts = wExplode (";", $poll);
$c = count ($opts);

/*
 *
 * render the poll
 *
 */

if ($c == 0) {

  $lastedit = "";

}
else {

  $phtm = "<table class=w>"
        .  "<tr>"
        .   "<td>"
        .    "<table class=w style=\"margin-top:10px;margin-bottom:10px\">";

  /*
    after opening the options' table, add a row for each option:
    $n is used to alternate colored stripes in proportional bars,
    $c to know when $n reachs the last option (and avoid a vertical spacer)
  */

  $n = 0;

  foreach ($opts as $o) {

    $phtm .=  "<tr>"
          .    "<td align=right>"
          .     "<input type=radio name=vote>"
          .    "</td>"
          .    "<td>"
          .     "<img width=1 class=h>"
          .    "</td>"
          .    "<td width=100% class=pl>"
          .     $o
          .    "</td>"
          .   "</tr>";

    $n ++;

    if ($n != $c) {

      $phtm .= "<tr>"
            .   "<td height=1>"
            .   "</td>"
            .  "</tr>";

    }

  }

  $phtm .=    "<tr>"
        .      "<td height=6>"
        .      "</td>"
        .     "</tr>"
        .     "<tr>"
        .      "<td colspan=3>"
        .       "<input class=rf type=submit value=\"submit vote\" style=\"margin:6px 6px 0px 0px\">"
        .       "<input class=rf type=submit value=\"close poll\">"
        .      "</td>"
        .     "</tr>"
        .    "</table>"
        .   "</td>"
        .  "</tr>"
        . "</table>";

  $message = "&lt;l&gt;{$title}&lt;/l&gt;"
           . "&;NO members voted in this poll so far"
           . "&lt;h&gt;{$phtm}&lt;/h&gt;"
           . "$message";

  $lastedit = "NOTE: polls don't work in previews";

}

/*
 *
 * initialize rendering arguments for "message.php":
 * - there are no control links in previews because tipically, the message doesn't exist yet.
 *
 */

$mid = "";              // message ID, but it's only used for hidden ignored messages
$ignored = false;       // of course it would be meaningless here: ignoring your preview? :)

$postlink = "";         // link to post a reply
$pm_button = "";        // link to send PM to author
$quote_button = "";     // link to reply quoting this post
$retitle_button = "";   // link to retitle the thread
$edit_button = "";      // link to edit the message
$delete_button = "";    // link to delete the message
$split_button = "";     // link to split the thread starting from this message

$enable_styling_warning = false;

if (isset ($_POST["editing"])) {

  /*
    when previewing changes to an existing message, report edited post informations
  */

  $epoc = intval (fget ("ctime", 10, ""));      // message creation time (original post)

  /*
    "predicting" the presence of a "last changed" note:
    - it happens when author's nickname doesn't correspond this user's nickname,
      or if a given amount of time ($mercifuledit) has passed since creation time.
  */

  if (($author != $nick) || ($epoc + $mercifuledit < $epc)) {

    $e_nick = "<a target=pst href=profile.php?nick=" . base62Encode ($nick) . "&amp;at=$epc>$nick</a>";
    $e_date = gmdate ('M d, Y', $epc);
    $e_time = gmdate ('H:i', $epc);

    $noauthor = (empty ($lastedit)) ? false : true;
    $lastedit .= "last changed by $e_nick on $e_date at $e_time";

  }

}
else {

  /*
    else, it's a preview of a new post (from "postform.php")
  */

  $noauthor = false;

  $author = $nick;
  $epoc = $epc;

}

/*
 *
 * render the message
 *
 */

include ("message.php");

/*
 *
 * append help, if requested:
 * - there's a switch in "kprefs.php" to hide help notes in previews:
 *   when that switch is on, global variable $hhip contains "yes".
 *
 */

if ($hhip != "yes") {

  unset ($aht);

  foreach ($allowedhtmltags as $tag => $tagnotes) {

    $aht .= "<li>&lt;{$tag}&gt; = {$tagnotes}</li>";

  }

  $smiley_count = count (all ("cd/smileys", makeProper));

  $how_to_add_smileys =

    ($smiley_count < $allowedsmileys)

        ?  "If you want to use a smiley that's not provided by that list, you might "
        .  "upload it to the <a target=pst href=cdisk.php>c-disk</a>, which will also "
        .  "associate it with a given keyword and add it to the said list."
        :  "";

  $list .=

      makeframe (

           "<center>"
        .   "<br>"
        .   "<span class=pc_l>POSTING HOW-TO</span>"
        .   "<br>"
        .   "help notes on special effects and useful stuff"
        .   "<br>&nbsp;"
        .  "</center>", false, false

      )

        .

      makeframe (

           "Paragraph control tags:",

           "There's two kinds of them: one-shot tags and wrap-around tags. One-shot tags only "
        .  "have an opening instance, like &lt;d&gt;, which is a horizontal divider. Wrap-around "
        .  "tags have two instances, marking beginning and end of a text span that is affected "
        .  "by the tag, as in &lt;c&gt;center this&lt;/c&gt;. Here follows a recap of all "
        .  "available tags, some of which will be more deeply examined in later paragraphs:"
        .  "<ul>"
        .  "<li>&lt;b&gt;...&lt;/b&gt; = bold, e.g. to &lt;b&gt;boldly&lt;/b&gt; go</li>"
        .  "<li>&lt;i&gt;...&lt;/i&gt; = italics, e.g. we &lt;i&gt;meant&lt;/i&gt; to do it</li>"
        .  "<li>&lt;u&gt;...&lt;/u&gt; = underline, e.g. it is &lt;u&gt;important&lt;/u&gt;</li>"
        .  "<li>&lt;c&gt;...&lt;/c&gt; = centered elements</li>"
        .  "<li>&lt;r&gt;...&lt;/r&gt; = right-aligned text span</li>"
        .  "<li>&lt;j&gt;...&lt;/j&gt; = justified text span</li>"
        .  "<li>&lt;alt&gt;...&lt;/alt&gt; = use an alternative font</li>"
        .  "<li>&lt;e&gt;...&lt;/e&gt; = emphasized text span</li>"
        .  "<li>&lt;o&gt;...&lt;/o&gt; = outline border</li>"
        .  "<li>&lt;m&gt;...&lt;/m&gt; = marks a mistake</li>"
        .  "<li>&lt;l&gt;...&lt;/l&gt; = write in large text</li>"
        .  "<li>&lt;s&gt;...&lt;/s&gt; = write in small text</li>"
        .  "<li>&lt;y&gt;...&lt;/y&gt; = tiny text, intentionally unreadable</li>"
        .  "<li>&lt;h&gt;...&lt;/h&gt; = headline: a highly attractive title</li>"
        .  "<li>&lt;k&gt;...&lt;/k&gt; = post break: a balloon edge-free section</li>"
        .  "<li>&lt;li&gt;...&lt;/li&gt; = list element (like in this very list)</li>"
        .  "<li>&lt;d&gt; or &lt;hr&gt; = horizontal rule</li>"
        .  "<li>&lt;q Nickname[messageID]&gt;...&lt;/q&gt; = quoting \"Nickname\"</li>"
        .  "<li>&lt;cd/types/nickname/filename&gt; = embedding object from c-disk</li>"
        .  "<li>&lt;link cd/types/nickname/filename&gt; = linking to c-disk object</li>"
        .  "<li>/// simple_code_here \\\\\\ = mark simple code fragment</li>"
        .  "<li>///+ lots_of_code_here +\\\\\\ = mark <u>windowed</u> code fragment</li>"
        .  "</ul>"
        .  "<u>Notes</u>: "
        .  "Code fragments cannot be inserted one inside another, and <em>might not be "
        .  "quoted</em>, because they break the quote block's structure. Windowed code "
        .  "fragments, marked by ///+...+\\\\\\ differ from simple fragments because they "
        .  "provide scrollbars to view the code in all of its parts and without "
        .  "overstretching the layout of the page.", false

      )

        .

      ((isset ($aht))

        ?

      makeframe (

           "Allowed HTML tags:",
           "Other than using the above shortcut tags, you may use a subset of common HTML tags "
        .  "the manager allowed for use in posts. Here follows the actual list of allowed tags, "
        .  "along with a brief description for each of them."
        .  "<ul>" . $aht . "</ul>"
        .  "* it is advisable to check out the official syntax for this tag at "
        .  "<a target=_blank href=http://www.w3c.org>W3C</a>.", false

      )

        : ""

      )

        .

      makeframe (

           "No scripts are allowed:",

           "Scripts may expose members' accounts to possible hijacking via cross-site scripting "
        .  "techniques which work by stealing the login cookies. For this and other reasons, no "
        .  "scripts are ever allowed in the message's text, as well as any other data submitted "
        .  "to this system by its users, neither in ASCII nor in URL-encoded forms. We hope you "
        .  "understand that it's a truly necessary precaution. If you see an orange error page, "
        .  "upon submitting your message, it means you have, intentionally or not, triggered the "
        .  "filter which prevents the use of scripts.", false

      )

        .

      makeframe (

           "Creating tables:",

           "Tables are generally used to organize data in well-aligned \"grids\" of ordered rows "
        .  "and columns: since HTML tables aren't often allowed in posts, there is a set of "
        .  "alternative tags to create a table. It's basically a simplification of HTML tables: "
        .  "the tags to make them are &lt;tab&gt;, &lt;row&gt; and &lt;col&gt;. Simply, a table "
        .  "begins with either a &lt;tab&gt; or a &lt;row&gt; tag. Both row and col are one-shot "
        .  "tags that separate rows and colums. When you need a new colum in the current row, "
        .  "type &lt;col&gt;, when you need a new row, type &lt;row&gt;. When you need to "
        .  "restart writing normal text, type &lt;/tab&gt;.", false

      )

        .

      makeframe (

           "Quoting others:",

           "If you want to quote another message in full you may click the 'quote' link next "
        .  "to the 'reply' link below a message. It will copy the existing text to your message "
        .  "and place it between two tags: &lt;q nickname[message-id]&gt;...text...&lt;/q&gt;, "
        .  "because that's the way quoting blocks might be defined. Of course you may also type "
        .  "those tags yourself, to manually create a quoting block. Reference message ID may "
        .  "be omitted.", false

      )

        .

      makeframe (

           "Embedding c-disk objects:",

           "The &lt;cd&gt; tag is used to embed an object that someone saved to the c-disk, "
        .  "and it syntax is: &lt;cd/folder/nickname/filename&gt;. You must separate cd, folder, "
        .  "nickname and filename with forward slashes. The word &quot;folder&quot; must be "
        .  "replaced with the name of the folder holding the file (ie. gifs, jpgs, zips...). "
        .  "If the object is an image, it can also be scaled to reduce or expand its apparent "
        .  "size, by appending an additional parameter as in the following example: "
        .  "&lt;cd/jpgs/Myself/MyPicture,50%&gt; ...which would scale the image to half its "
        .  "size. Note there is a COMMA between the file name and the scale. Allowed scaling "
        .  "ranges from 1% to 500%. If for some reason you do not want to show an image directly "
        .  "within the message (you guess it would be too big or too long for slow connections), "
        .  "you may instead post a LINK to the image by using &lt;link cd/...&gt;", false

      )

        .

      makeframe (

           "Marking smileys:",
           "There is a button in the left panel to show a list of smileys. Typing one of the "
        .  "keywords in the message's text will convert the keyword to the corresponding "
        .  "emoticon. {$how_to_add_smileys}", false

      )

        .

      makeframe (

           "Marking links and email addresses:",
           "Anything that begins with \"http://\", or with \"www.\", and continues with a "
        .  "theoretically legal URL, will be highlighted and made clickable as a link that will "
        .  "open in a new window, except for the case where the link refers to a page of this "
        .  "site (which will open in the appropriate frame). Anything that contains an @ sign "
        .  "and may be a possible email address will be highlighted and made clickable "
        .  "as a \"mailto:\" link.", false

      )

        .

      makeframe (

           "One last note...",
           "If you know what these help notes say and you don't want them to "
        .  "encumber your previews anymore, edit your preferences and check the box "
        .  "\"hide help in previews\".", false

      );

}

/*
 *
 * template initialization
 *
 */

$forum = "<a class=fl href=" . (($fid > 0) ? "threads.php?f=$fid" : "#") . ">..</a>";

$title = shorten ($title, $tt_hint_length);
$title = "<a class=fl href=" . (($tid > 0) ? "posts.php?t=$tid" : "#") . ">$title</a>";

$forum_posts = str_replace

  (

    array

      (

        "[FORUM]",
        "[THREAD]",
        "[PAGING]",
        "[LIST]",
        "[USERSLIST]",
        "[PERMALINK]"

      ),

    array

      (

        $forum,
        $title,
        "preview",
        $list,
        "not applicable to previews",
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
 * (unless no-spy flag checked)
 *
 */

if (($login == true) && ($nspy != "yes")) {

  $this_uri = "{$path_to_postline}/preview.php";

  if (getlsp ($id) != $this_uri) {

    setlsp ($id, $this_uri);

  }

}

/*
 *
 * releasing locked session frame, page output:
 *
 * BUG FIX: discard possible session creation or update, or it could change the nickname of the
 * user that's doing the preview to "bulletin_board_system_server"... that was fun...
 *
 */

define ("disable_session_creation", true);
define ("disable_session_update", true);

echo (pquit ($forum_posts));



?>
