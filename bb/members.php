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
 * retrieve and explode ban list
 *
 */

if (defined ("bml") == false) {

  $bml = trim (strtolower (readFrom ("stats/bml")));
  $bml = wExplode ("\n", $bml);

  /*
    Don't cause this fragment to be re-processed, when it's used for more than 1 entry.
  */

  define ("bml", true);

}

/*
 *
 * URL and eMail highlighters
 *
 */

if (defined ("fn_highlighters") == false) {

  /*
    clear postlink_attributes here, as it's not a real post with the intended background behind
  */

  $postlink_attributes = "";

  function islocal ($url, $names) {

    /*
      This function determines if a page points to a local URL in the bulletin board's domain,
      and wether its destination script matches one of those given in a comma-separated list,
      passed as $names. Returns true if both the conditions are so, false otherwise.
    */

    global $sitedomain;

    $name = explode (",", $names);

    foreach ($name as $n) {

      $p = strpos ($url, $n);

      if ($p !== false) {

        /*
          one of the names within the $names list appears in the $url string
        */

        $s = strpos ($url, $sitedomain);

        if ($s !== false) {

          /*
            $sitedomain also appears in the $url string:
            the page is local if $sitedomain appears before $n (name in the $names list)
          */

          if ($s < $p) return true;

        }

        /*
          the page may be local even if $sitedomain isn't part of the URL,
          providing there is no protocol indicator (:) before the page's name,
          in which case the page's path could be relative to the current directory,
          for e.g. $url = "../index.php"
        */

        if (strpos (substr ($url, 0, $p), ":") === false) return true;

      }

    }

    return false;

  }

  function target_of ($url) {

    global $l_frames;
    global $r_frames;

    /*
      This function returns the most appropriate target frame for loading a page,
      depending on its URL (if it's a local page, open it in the appropriate frame of
      the bulletin board's frameset; if it's external to the site, open in a new window).
    */

    $url = strtolower ($url);
    $target = "_blank";

    if (islocal ($url, "index.php")) {

      /*
        the "index.php" script, which is supposed to be "postline/index.php",
        needs to be loaded inside the "pag" frame, ie. as the new central frameset
      */

      $target = "pag";

    }
    elseif (islocal ($url, implode (",", $r_frames))) {

      /*
        these are all pages that might load inside the right frame (pan)
      */

      $target = "pan";

    }
    elseif (islocal ($url, implode (",", $l_frames))) {

      /*
        and these are all pages that might load inside the left frame (pst)
      */

      $target = "pst";

    }

    return chr (32) . "target=$target";

  }

  function highlight ($string) {

    /*
      This function will scan $string for any possible links beginning with "http://" or "www.":
      when one is found, it's transformed into an HTML link, and also pointed to the most
      appropriate target frame. It will also highlight possible mailto links...
    */

    global $link_terminators;
    global $parblock_terminators;
    global $email_terminators;
    global $postlink_attributes;

    $i = 0;     // pointer within string
    $t = false; // flag: true while parsing inside an HTML tag

    while (($c = $string[$i]) != "") {

      /*
        count HTML tag openers and closers
      */

      if ($c == "<") $t = true;
      elseif ($c == ">") $t = false;

      /*
        if this character isn't part of a tag...
      */

      if ($t == false) {

        /*
          ...could it be an email address?
          look for "@"...
        */

        if ($c == "@") {

          /*
            possible email address match found, and outside tags:
            if it could be valid, it'll be highlighted as intended
          */

          $s = $i;      // remembers the position of the @ sign
          $L = 1;       // length of address string to replace: so far, the @ sign alone
          $b = $i;      // beginning of address string within actual $string text
          $i = $i - 1;  // update $i to point at the character preceeding the @ sign

          /*
            backward scan until the beginning of mail address' username
          */

          while (($c = $string[$i]) != "") {

            if (in_array ($c, $email_terminators)) {

              /*
                username ends because a common kind of terminator has been found
              */

              break;

            }

            $L ++;
            $b --;
            $i --;

          }

          /*
            forward scan until the end of the mail address' domain
          */

          $i ++;        // back to non-terminator character following the terminator
          $i += $L;     // skip the whole "user@" part, prepare to examine the domain

          while (($c = $string[$i]) != "") {

            if (in_array ($c, $email_terminators)) {

              /*
                domain ends because a common kind of terminator has been found
              */

              break;

            }

            $L ++;
            $i ++;

          }

          /*
            the address is considered possibly valid, and highlighted, if it has:
            - at least 6 characters complessive length (x@x.xx);
            - at least 1 character before the @ sign;
            - at least 4 characters after the @ sign.
          */

          if (($L >= 6) && ($s - $b >= 1) && ($i - $s >= 4)) {

            /*
              get destination address, which is the same, whole address string to highlight
            */

            $d = substr ($string, $b, $L);

            /*
              assemble HTML anchor for this mailto link,
              assemble new version of entry $string, with the address replaced by its link
            */

            $a = "<a $postlink_attributes" . target_of ($d) . chr (32) . "href=\"mailto:$d\">$d</a>";
            $string = substr ($string, 0, $b) . $a . substr ($string, $i);

            /*
              advance $i to point at the character right after the above closing "</a>" tag:
              the amount of characters to skip to "synchronize" it with the new version of
              $string is given by whatever exceeds the original length of mail address $d
            */

            $i += strlen ($a);
            $i -= strlen ($d);

          }

        }
        else {

          /*
            ...and if it's no "@" sign, it could be the initial of an URL...
          */

          if ($c == "h") {

            $match = (substr ($string, $i, 7) == "http://") ? true : false;

          }
          elseif ($c == "w") {

            $match = (substr ($string, $i, 4) == "www.") ? true : false;

          }
          else {

            $match = false;

          }

          if ($match) {

            /*
              possible URL match found, and outside tags:
              if valid, must be highlighted as intended
            */

            $l = ($c == "h") ? 7 : 4;

            $L = $l;            // length of URL string to replace, so far
            $b = $i;            // beginning of URL string within actual $string text
            $i = $i + $l;       // update $i to end of URL prefix within actual $string text
            $p = false;         // true after finding a ? in the URL: detects GET parameters block

            while (($c = $string[$i]) != "") {

              if ($c == "&") {

                /*
                  this check is necessary to determine wether a newline (<br>) has been inserted
                  past the URL: Postline stores newlines in posts using a special marker: "&;"
                  (an ampersand followed by a semicolon), and newlines always terminate URLs
                */

                if ($string[$i + 1] == ";") break;

              }

              if ($p) {

                if (in_array ($c, $parblock_terminators)) {

                  /*
                    in parameters block, found what's almost certainly a terminator:
                    the URL (hopefully) ends here, although it's possible for these
                    terminators to appear in a GET parameter blocks, but uncommon...
                  */

                  break;

                }

              }
              else {

                if ($c == "?") {

                  /*
                    realize that since now you are parsing a GET parameters block,
                    but stop if there's effectively nothing following the question mark,
                    which in this case might be a simple punctuator...
                  */

                  $p = true;

                  if (in_array ($string[$i + 1], $link_terminators)) break;

                }
                else {

                  if (in_array ($c, $link_terminators)) {

                    /*
                      if a GET parameters block hasn't been detected so far,
                      the URL ends because a more common kind of terminator has been found
                    */

                    break;

                  }

                }

              }

              $L ++;
              $i ++;

            }

            /*
              if it's a significant match (at least 4 characters follow the prefix),
              effectively highlight it
            */

            if ($L >= ($l + 4)) {

              /*
                get destination of URL, which is the same, whole URL string to highlight
              */

              $d = substr ($string, $b, $L);

              /*
                set proper URL prefix in case this URL begins with "www.", assuming http protocol
              */

              $x = ($l == 4) ? "http://" : "";

              /*
                assemble HTML anchor for this URL,
                assemble new version of entry $string, with the URL replaced by its HTML anchor
              */

              $a = "<a $postlink_attributes" . target_of ($d) . chr (32) . "href=\"$x$d\">$d</a>";
              $string = substr ($string, 0, $b) . $a . substr ($string, $i);

              /*
                advance $i to point at the character right after the above closing "</a>" tag:
                the amount of characters to skip to "synchronize" it with the new version of
                $string is given by whatever exceeds the original length of the URL $d
              */

              $i += strlen ($a);
              $i -= strlen ($d);

            }

          }

        }

      }

      $i ++;

    }

    return $string;

  }

  /*
    Don't cause this code fragment to be re-declared, when it's used for more than 1 entry.
  */

  define ("fn_highlighters", true);

}

/*
 *
 * get parameters
 *
 */

$by = fget ("by", 5, "");               // sort by (either "alpha" or "cron")
$or = fget ("or", 4, "");               // order type ("asc" or "desc")

$page = intval (fget ("p", 1000, ""));  // number of page to display
$skip = 0;                              // number of records to skip from beginning of page

/*
 *
 * this determines if arguments to go to specific sorting methods or a specific page were given,
 * which in turn disables the permalink (which does not accept arguments other than "about");
 * for the same reason, $presenting is set to "nobody" as a token to witheld the "about" permalink,
 * until the relevant argument is found by later code
 *
 */

$args_not_given = ((count ($_GET)) || (count ($_POST))) ? false : true;
$presenting = "nobody";

/*
 *
 * load nicknames list
 *
 */

$members = all ("members/bynick", makeProper);

/*
 *
 * define search box fragment
 *
 */

$search_box = "<table class=w>"
            . "<form name=search action=members.php enctype=multipart/form-data method=post>"
            .  "<tr>"
            .   "<td width=100% height=23 class=inv style=\"border:1px solid black\" align=right>"
            .    "type a nickname to search for and press &#171;enter&#187; - - - &nbsp;"
            .   "</td>"
            .   "<td class=in width=1 style=\"padding:0px 3px 0px 3px;border:1px solid black\">"
            .    "<input class=mf type=text name=find value=\"\" size=20 maxlength=20 style=width:150px>"
            .   "</td>"
            .  "</tr>"
            . "</form>"
            . "</table>"
            . "\n"
            . "<script language=Javascript type=text/javascript>\n"
            . "<!--\n"
            .   "document.search.find.focus();\n"
            . "//-->\n"
            . "</script>\n";

/*
 *
 * check if a search by nickname (or part thereof) is to be done
 *
 */

$fullmatch = false;
$find = ucfirst (strtolower (fget ("find", 20, "")));

if (empty ($find)) {

  $fullmatch = true;
  $find = strtolower (fget ("present", -20, ""));

  if ($find == "me") {

    $find = ($login == true) ? $nick : voidString;

  }
  else {

    if ($find == strtolower ($servername)) {

      $find = voidString;

      $form =

            makeframe (

              "$servername is the server...", false, false

            )

            . $search_box
            . "<table style=width:100%;height:92%>"
            .  "<td>"
            .   "<center>"
            .   "<table align=center style=\"border:4px solid #C00000\">"
            .    "<td style=\"padding:6px;text-align:center\">"
            .     "$servername is the server,<br>"
            .     "and it's not currently in this list"
            .    "</td>"
            .   "</table>"
            .   "</center>"
            .  "</td>"
            . "</table>";

      $memberlist = str_replace (

        array ("[FORM]", "[SOPS]", "[PERMALINK]"),
        array ($form, "", ""),

        $memberlist

      );

      exit (pquit ($memberlist));

    }
    else {

      $find = ucfirst ($find);

    }

  }

  $presenting = (empty ($find)) ? "nobody" : base62Encode ($find);
  $args_not_given = ($presenting == "nobody") ? true : $args_not_given;

}

if (!empty ($find)) {

  $i = 0;
  $f = false;
  $l = strlen ($find);

  sort ($members);

  foreach ($members as $m) {

    if ($fullmatch == true) {

      if ($find == valueOf ($m, "nick")) {

        $f = true;
        break;

      }

    }
    else {

      if ($find == substr (valueOf ($m, "nick"), 0, $l)) {

        $f = true;
        break;

      }

    }

    $i ++;

  }

  if ($f) {

    $sorting = "nick, ascending";

    $by = "alpha";
    $or = "asc";

    $page = intval ($i / $membersperpage);
    $skip = $i % $membersperpage;

  }
  else {

    $form =

          makeframe (

            "Member not found!", false, false

          )

          . $search_box
          . "<table style=width:100%;height:92%>"
          .  "<td>"
          .   "<center>"
          .   "<table align=center style=\"border:4px solid #C00000\">"
          .    "<td style=\"padding:6px;text-align:center\">"
          .     "no search match for:<br>$find"
          .    "</td>"
          .   "</table>"
          .   "</center>"
          .  "</td>"
          . "</table>";

    $memberlist = str_replace (

      array ("[FORM]", "[SOPS]", "[PERMALINK]"),
      array ($form, "", ""),

      $memberlist

    );

    exit (pquit ($memberlist));

  }

}

/*
 *
 * list nicknames
 *
 */

if (count ($members)) {

  /*
    proceed to user-selected sorting method unless a search by nickname ($find) is requested
  */

  if (empty ($find)) {

    if ($by == "alpha") {

      $sorting = "nick";

      if ($or == "desc") {

        rsort ($members);
        $sorting .= ", descending";

      }
      else {

        sort ($members);
        $sorting .= ", ascending";

      }

    }
    else {

      $sorting = "date";

      if ($or == "desc") {

        $sorting .= ", descending";

      }
      else {

        $sorting .= ", ascending";
        $members = array_reverse ($members);

      }

    }

  }

  /*
    calculate number of pages in members' list, by counting nicknames ($c) and approximating
    to an integer number of pages ($pages) by truncating decimals, respecting $membersperpage
    as defined by "settings.php"; pages number is calculated from $c - 1, because the count of
    pages is based at zero, and if members are 10 and members per page are 10 as well, you
    shouldn't have two pages begin page #0 and page #1 (it'd be an "off by one" glitch...)
  */

  $c = count ($members);
  $pages = (int) (($c - 1) / $membersperpage);

  /*
    setup paging links ($prevpage, $currpage, $nextpage),
    including $by and $or arguments via GET method to not lose sorting while changing pages...
  */

  $arg_by = empty ($by) ? "" : "by=$by&amp;";
  $arg_or = empty ($or) ? "" : "or=$or&amp;";

  /*
    if there's more than one page...
    (remember pages are numbered from page 0 as the first page)
  */

  if ($pages > 0) {

    /*
      ...initialize paging links
    */

    $div = ($pages > $pp_mlist) ? " ... " : chr (32);

    $paging =

          (($page == 0)

            ? "p. <a class=alert>1</a>"                                         // when already on page 1
            : "p. <a class=fl href=members.php?{$arg_by}{$arg_or}p=0>1</a>"     // clickable link to page 1

          )

            . $div;

    $number = ($page < $pp_mlist) ? 1 : $page - $pp_mlist + 1;
    $nlimit = ($number + (2 * $pp_mlist - 1)  >= $pages) ? $pages : $number + (2 * $pp_mlist - 1);

    while ($number < $nlimit) {

      $paging .= chr (32)
              .

            (($page == $number)

              ?  "<a class=alert>"
              :  "<a class=fl href=members.php?{$arg_by}{$arg_or}p=$number>"

            )

              .  ($number + 1) . "</a>";

      $number ++;

    }

    $paging .= $div
            .

          (($page == $pages)

            ? "<a class=alert>" . ($pages + 1) . "</a>"
            : "<a class=fl href=members.php?{$arg_by}{$arg_or}p=$pages>" . ($pages + 1) . "</a>"

          );

  }

  /*
    generate list header
  */

  $form =

         makeframe (

            "Members [by $sorting]: $paging", false, false

         )

         . $search_box;

  /*
    begin displaying $members array at $i (first entry of requested page),
    keeping a counter of displayed members as $n (which starts at zero);
    halt when $i meets $c (no more members to list),
    or when $n meets $membersperpage (no more members to list in this page)
  */

  $i = $page * $membersperpage;
  $n = 0;

  while (($i < $c) && ($n < $membersperpage + $skip)) {

    /*
      the $skip argument was determined basing on where, in this page,
      the member's nickname matching a search was found in the $members array:
      if no search by nickname was requested, $skip is zero and all members on this page
      will be displayed, else only a certain amount of members, specified by $skip, will
      be displayed, to make the search match stand out as the first entry on its page...
    */

    if ($n >= $skip) {

      /*
        determine profile ID ($pid) and database file holding profile data ($db),
        then read profile record ($r) from the database (reads from same file will be cached)
      */

      $pid = intval (valueOf ($members[$i], "id"));
      $db = "members" . intervalOf ($pid, $bs_members);
      $r = get ($db, "id>$pid", "");

      if (!empty ($r)) {

        /*
          if the record was successfully read, display profile data in list:
          begin by collecting common values for repeated steps...
        */

        $this_nick = valueOf ($members[$i], "nick");
        $this_link = base62Encode ($this_nick);
        $this_rank = valueOf ($r, "auth");
        $this_tint = intval (valueOf ($r, "ntint"));

        $is_this_admin = in_array ($this_rank, $admin_ranks);
        $is_this_mod = in_array ($this_rank, $mod_ranks);

        /*
          check if there's some kind of avatar to display, else use generic placeholder:
          it will simply look for an existing picture file matching the encoded nickname...
        */

        if (@file_exists ("$path_to_avatars/$this_link.gif")) {

          $avatar_present = true;
          $img = "$path_to_avatars/$this_link.gif";

        }
        elseif (@file_exists ("$path_to_avatars/$this_link.jpg")) {

          $avatar_present = true;
          $img = "$path_to_avatars/$this_link.jpg";

        }
        elseif (@file_exists ("$path_to_avatars/$this_link.png")) {

          $avatar_present = true;
          $img = "$path_to_avatars/$this_link.png";

        }
        else {

          $avatar_present = false;
          $img = "$path_to_avatars/ancillary/generic.png";

        }

        /*
          fill informative fields
        */

        $aka = valueOf ($r, "aka");
        $aka = (!empty ($aka)) ? ", a.k.a. $aka" : "";

        $acr = "regular";
        $acr = ($is_this_mod) ? "moderation" : $acr;
        $acr = ($is_this_admin) ? "administration" : $acr;
        $acr = ($pid == 1) ? "service management" : $acr;

        $tcn = intval (valueOf ($r, "threads"));
        $pcn = intval (valueOf ($r, "posts"));
        $tcn = ($tcn) ? $tcn : "no";
        $pcn = ($pcn) ? $pcn : "no";

        $session_start_time = get ("stats/sessions", "id>$pid", "beg");
        $set_wanted = false;
        $set_missing = false;

        if ($session_start_time >= ($epc - $sessionexpiry)) {

          /*
            if there's an actual session for this ID, the member is online
          */

          $act = "<em>actually online</em>";

        }
        else {

          /*
            else, inform about when the member was last seen online
          */

          $act = intval (valueOf ($r, "lastlogin"));

          if ($act == 0) {

            /*
              there could be members that register but then never login,
              or did never login yet; if they don't within a certain period
              of time, specified by "settings.php", they might be removed (pruning)...
            */

            $act = "never logged in";

          }
          else {

            /*
              note:
                1 day is 86400 seconds
                1 year is simplified to be 360 days, with 30 days per month
            */

            if ($act >= ($epc - 86400))

              $act = "active to date";

            elseif ($act >= ($epc - 2*86400))

              $act = "been here yesterday";

            elseif ($act >= ($epc - 7*86400))

              $act = "seen " . intval (($epc - $act) / 86400) . " day(s) ago";

            elseif ($act >= ($epc - 30*86400))

              $act = "seen " . intval (($epc - $act) / (7*86400)) . " week(s) ago";

            elseif ($act >= ($epc - 360*86400)) {

              $set_wanted = true;
              $act = "seen " . intval (($epc - $act) / (30*86400)) . " month(s) ago";

            }
            else {

              $set_missing = true;
              $act = "absent, since a year or more";

            }

          }

        }

        $clas = valueOf ($r, "gender") . chr (32) . valueOf ($r, "species");
        $habi = valueOf ($r, "habitat");
        $born = valueOf ($r, "born");

        $clas = ($clas != chr (32)) ? "<br>classification: $clas" : "";
        $habi = (!empty ($habi)) ? "<br>habitat: $habi" : "";
        $born = (!empty ($born)) ? "born: $born," . chr (32) : "";

        /*
          retrieve photograph, if any, and calculate photograph margins
        */

        unset ($existing_photo);

        if     (@file_exists ("$path_to_photos/{$this_link}.gif")) $existing_photo = "$path_to_photos/{$this_link}.gif";
        elseif (@file_exists ("$path_to_photos/{$this_link}.jpg")) $existing_photo = "$path_to_photos/{$this_link}.jpg";
        elseif (@file_exists ("$path_to_photos/{$this_link}.png")) $existing_photo = "$path_to_photos/{$this_link}.png";

        $photograph_file = (isset ($existing_photo)) ? $existing_photo : "$path_to_photos/ancillary/generic.png";
        list ($photograph_width, $photograph_height) = @getimagesize ($photograph_file);

        $photograph_width = ($photograph_width > $maxphotowidth) ? $maxphotowidth : $photograph_width;
        $photograph_height = ($photograph_height > $maxphotoheight) ? $maxphotoheight : $photograph_height;

        $lr = (20 + $maxphotowidth - $photograph_width) / 2;
        $tb = (20 + $maxphotoheight - $photograph_height) / 2;

        $photograph_style =

          "width:{$photograph_width}px;"
        . "height:{$photograph_height}px;"
        . "margin-left:{$lr}px;"
        . "margin-top:{$tb}px;"
        . "margin-right:{$lr}px;"
        . "margin-bottom:{$tb}px";

        /*
          retrieve "bit about"
        */

        $bit_about = valueOf ($r, "about");

        $bit_about = (empty ($bit_about))

          ? "We still know nothing about $this_nick, "
          . "as the profile's owner didn't tell us anything yet. "
          . "This paragraph will hold informations given in the &quot;bit about&quot; "
          . "field of the corresponding profile form."

          : highlight (str_replace ("&;", "<br>", $bit_about));

        /*
          check kickouts and bans, staff and major member status
        */

        $kd = intval (valueOf ($r, "kicked_on"))
            + intval (valueOf ($r, "kicked_for"));

        $set_title = get ("members/bynick", "nick>$this_nick", "ut");
        $set_staff = (($is_this_admin == true) || ($is_this_mod == true)) ? true : false;
        $set_major = (valueOf ($r, "major") == "yes") ? true : false;
        $is_banned = ((in_array (strtolower ($this_nick), $bml)) && ($is_this_admin == false)) ? true : false;
        $is_kicked = ($kd >= $epc) ? true : false;

        if ($is_banned) {

          $set_wanted = false;  // heh, that would be a bit contredictory
          $set_missing = false; // yes, it may obviously be missing, after a year or so of ban

        }

        /*
          add entry to displayed list
        */

        $form .= "<table class=ot>"
              .  "<tr>"
              .   "<td class=mlcell>"
              .    "<img width=$avatarpixelsize height=$avatarpixelsize border=0 src=$img>"
              .   "</td>"
              .   "<td class=mlrow valign=top>"
              .    "<span class=n>"
              .     "<a target=pst href=profile.php?nick=$this_link>... $this_nick</a>{$aka}"
              .    "</span>"
              .    "<br>"
              .    $clas
              .    $habi
              .    "<br>{$born}here since: " . gmdate ("F d, Y", valueOf ($r, "reg"))
              .    "<br>literal rank: " . $this_rank . ", access rights: " . $acr
              .    "<br>activity: $pcn posts, $tcn threads, $act"
              .   "</td>"
              .   "<td class=mid valign=top>"
              .    $pid
              .   "</td>"
              .  "</tr>"
              .  "</table>"

              . "<table class=mi>"
              .  "<td class=micell valign=top>"
              .   "<div style=position:relative>"
              .

            (($set_staff == true) ? "<div class=sis><img src=$path_to_photos/ancillary/staff.png class=sig></div>" : "")

              .

            (($set_major == true) ? "<div class=sim><img src=$path_to_photos/ancillary/major.png class=sig></div>" : "")

              .

            (($is_banned == true) ? "<div class=sib><img src=$path_to_photos/ancillary/banned.png class=sig></div>" : "")

              .

            (($is_kicked == true) ? "<div class=sik><img src=$path_to_photos/ancillary/kicked.png class=sig></div>" : "")

              .

            (($set_wanted == true) ? "<div class=siw><img src=$path_to_photos/ancillary/wanted.png class=sig></div>" : "")

              .

            (($set_missing == true) ? "<div class=mia><img src=$path_to_photos/ancillary/missing.png class=sig></div>" : "")

              .

            ((empty ($set_title)) ? "" : "<div class=ut>{$set_title}</div>")

//            .   "<div class=bih><img src=layout/images/clip.png class=binder></div>"
              .   "<div class=miname>$this_nick</div>"
              .   "<div class=pgh>"
              .    "<img src={$photograph_file} style=\"{$photograph_style}\">"
              .   "</div>"
              .   "<div class=ba>$bit_about</div>"
              .   "<ul class=linkslist>"
              .    "<li class=links>"
              .     "<a target=pst class=mll href=egosearch.php?n={$this_link}>"
              .      "all threads by $this_nick"
              .     "</a>"
              .    "</li>"
              .    "<li class=links>"
              .     "<a target=pst class=mll href=cdisk.php?sub={$this_link}>"
              .      "all files provided by $this_nick"
              .     "</a>"
              .    "</li>"
              .

            ((($login == true) && ($nick != $this_nick))

              ?    "<li class=links>"
              .     "<a target=pst class=mll href=sendpm.php?to={$this_link}>"
              .      "send private message to $this_nick"
              .     "</a>"
              .    "</li>"
              :    ""

              )

              .   "</ul>"
              .   "<div>"
              .  "</td>"
              . "</table>";

      }

    }

    $i ++;
    $n ++;

  }

  /*
    build sorting options form
  */

  $sops =

        "<img class=h height=7>" .

        makeframe (

            "Members [by $sorting]: $paging",

            "<table>"
          . "<form action=members.php enctype=multipart/form-data method=get>"
          . "<input type=hidden name=p value=$page>"
          .  "<tr>"
          .   "<td style=padding-right:20px>"
          .    "<input type=radio name=by value=alpha" . (($by == "alpha") ? chr (32) . "checked" : "") . ">&nbsp;by nickname<br>"
          .    "<input type=radio name=by value=cron" . (($by != "alpha") ? chr (32) . "checked" : "") . ">&nbsp;by registration date<br>"
          .   "</td>"
          .   "<td>"
          .    "<input type=radio name=or value=asc" . (($or != "desc") ? chr (32) . "checked" : "") . ">&nbsp;ascending order<br>"
          .    "<input type=radio name=or value=desc" . (($or == "desc") ? chr (32) . "checked" : "") . ">&nbsp;descending order<br>"
          .   "</td>"
          .  "</tr>"
          . "</table>"
          . "<table class=w>"
          .  "<td>"
          .   "<input class=ky type=submit name=submit value=\"sort list\" style=width:120px;margin-top:6px>"
          .  "</td>"
          .  "<td align=right valign=bottom>"
          .   "<a href=logs/prunelog.txt>view list of accounts removed for inactivity</a>"
          .  "</td>"
          . "</form>"
          . "</table>", false

        );

}
else {

  $sops = "";

  $form =

        makeframe (

          "Members", false, false

        )

        . "NO REGISTERED MEMBERS!"
        . "<hr>"
        . "Warning: management account registration pending!<br>"
        . "<em>Please register management account before going public!</em>";

}

/*
 *
 * permalink initialization
 *
 */

if ($enable_permalinks) {

  if ($presenting == "nobody") {

    $permalink = ($args_not_given)

      ? str_replace (

          "[PERMALINK]", $perma_url . "/" . $members_keyword, $permalink_model

        )

      : "";

  }

  else {

    $permalink = str_replace

      (

        "[PERMALINK]", $perma_url . "/" . $about_keyword . "/" . $presenting, $permalink_model

      );

  }

}

/*
 *
 * template initialization
 *
 */

$memberlist = str_replace

  (

    array (

      "[FORM]",
      "[SOPS]",
      "[PERMALINK]"

    ),

    array (

      $form,
      $sops,
      $permalink

    ),

    $memberlist

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

echo (pquit ($memberlist));



?>
