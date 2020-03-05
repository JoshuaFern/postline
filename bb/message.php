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



/*
 *
 * Fragment that outputs a single message, basing on the following variables (declared by caller).
 * This script can't be used alone, but is common to "posts.php", "preview.php", "result.php",
 * and "viewpm.php":
 *
 * $author         - nickname of the author of the message
 * $epoc           - UNIX epoc of post date
 * $postlink       - link to post a reply to this message
 * $quote_button   - link to post a reply QUOTING this message
 * $retitle_button - link to retitle this message's thread
 * $edit_button    - link to edit this message
 * $delete_button  - link to delete this message
 * $split_button   - link to split the message's thread
 * $message        - message text
 * $lastedit       - last edit note, if any
 * $list           - string that represents the page's contents (post will be appended there)
 * $bcol           - balloon color ID in the bcols array (see template.php for the array)
 * $mid            - ID of message to render (numeric)
 * $ignored        - if true, "message.php" must hide message in a div having id=h$mid
 * $enable_styling_warning - if true, enables display of "suspicious styling" warnings:
 *      true for regular posts in forums, but generally kept false if there is only one
 *      message in show, like in paginated threads, PMs, previews and search results
 *
 */

if (!defined ("going")) {

  die ("NO STANDALONE EXECUTION");

}

/*
 *
 * Assembling dates, initializing flags.
 *
 */

$date = gmdate ("M d, Y", $epoc);       // message posting date
$time = gmdate ("H:i", $epoc);          // message posting time

$styling_warning = false;               // if true, inline CSS tags use "suspicious" properties

/*
 *
 * Check For Marked Tags, to remove extra <br>s:
 * this flag is for speed optimization: regular expressions can be slow.
 *
 */

$cfmt = false;

/*
 *
 * parse balloon color index ($bcol),
 * to initialize background color hex value ($bbkc) and graphics set folder ($bfol):
 *
 * recorded speech balloon's color index may be out of range if there's been a removal of some
 * color records from the $bcols array inside "template.php", so it's eventually normalizing it;
 * balloon background color for MSIE must correct PNG gamma exponent, otherwise making corners
 * of PNG images look slightly darker than the rest of the balloon: it's a glitch inside MSIE.
 *
 */

$bcol = ($bcol === "") ? $legacy_bcol : $bcol;                          // lack of a color = legacy
$bcol = ($bcol < 0) ? 0 : $bcol;                                        // clamping to lower bound
$bcol = ($bcol < count ($bcols) - 1) ? $bcol : count ($bcols) - 2;      // clamping to upper bound
$bcid = ($ofie == "yes") ? "IECOLOR" : "COLOR";                         // background color's key
$bbkc = $bcols[$bcol][$bcid];                                           // background color's hex
$bfol = $bcols[$bcol]["FOLDER"];                                        // edge graphics' directory

/*
 *
 * Compute darkened version of the balloon's color ($dbkc):
 *
 * this fragment will make out a slightly darker version of the HEX color value passed
 * as its argument. In this script's circumstances, it's used to color the backgrounds
 * of a post's headlines, and the frame holding the message's body: darkening level is
 * controlled by a coefficient (actually, 0.925) and calculated by splitting hex color
 * value in its R;G;B bytes, multiplying all of them by the said coefficient, and then
 * re-assembling an hex value out of the resulting R;G;B triplet.
 *
 * note: global one-character variables in this fragment are escaped by two underscore
 * signs to keep them from interfering with common variables of the calling scripts...
 *
 */

$__c = base_convert ($bbkc, 16, 10);

$__r = $__c >> 16;
$__g = $__c & 0x00ff00;
$__g = $__g >> 8;
$__b = $__c & 0x0000ff;

$__r = intval ($__r * 0.925);
$__g = intval ($__g * 0.925);
$__b = intval ($__b * 0.925);
$__c = ($__r * 0x10000) + ($__g * 0x100) + $__b;

$dbkc = base_convert ($__c, 10, 16);

$__r = $__c >> 16;
$__g = $__c & 0x00ff00;
$__g = $__g >> 8;
$__b = $__c & 0x0000ff;

$__r = intval ($__r * 0.333);
$__g = intval ($__g * 0.333);
$__b = intval ($__b * 0.333);
$__c = ($__r * 0x10000) + ($__g * 0x100) + $__b;

$xbkc = base_convert ($__c, 10, 16);

/*
 *
 * URL and eMail highlighters
 *
 */

if (defined ("fn_highlighters") == false) {

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
    elseif ($url[0] == "/") {

      /*
        finally, these are permalinks, referred by URLs beginning with a slash, e.g. "/home"
      */

      $target = "_top";

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
    Don't cause this code fragment to be re-declared, when it's used for more than 1 post.
  */

  define ("fn_highlighters", true);

}

/*
 *
 * Smileys keywords processor:
 *
 * - will translate any occuring smiley keywords to the corresponding image,
 *   UNLESS the keyword appears inside a tag (fixes "Hello :)" tags glitch:
 *   we have a member whose nickname is "Hello :)" so his nickname includes
 *   a smiley, and well... he couldn't get his <cd> tags to work, before);
 *
 * - it will prepend a blank space to any keywords beginning by a semicolon,
 *   as typically happens for the ;) smiley, to avoid messing up real text
 *   occurrences of a semicolon followed by a closing parenthesis: these are
 *   more frequent than you may expect, because if you wrote ("hey"), for
 *   example, the doublequotes would be translated to the "&quot;" marker,
 *   and the parser would see it as (&quot;hey&quot;) and take the final ;)
 *   as a smiley.
 *
 */

if (defined ("fn_decode_smileys") == false) {

  /*
    Retrieve all smiley records, making out quick string+replacement arrays.
  */

  $smiley = all ("cd/smileys", makeProper);

  $skey = array ();
  $stag = array ();

  foreach ($smiley as $s) {

    $this_key = valueOf ($s, "keyword");
    $this_tag = "<img src=" . valueOf ($s, "image") . ">";

    if ($this_key[0] == ";") {

      /*
        patching infamous ("ETC") glitch
      */

      $this_key = chr (32) . $this_key;
      $this_tag = chr (32) . $this_tag;

    }

    $skey[] = $this_key;
    $stag[] = $this_tag;

  }

  function decode_smileys ($message) {

    /*
      This function will replace any smiley keywords with the corresponding image,
      but it has to do it carefully to avoid breaking c-disk links where the name
      of a member CONTAINS a keyword. Thus, it won't generally process parts of
      the message that will be translated to a paragraph control tag, that is,
      it will ignore any smiley keywords placed between < and >.
    */

    global $skey;
    global $stag;

    unset ($x);

    $s = 0;
    $t = 0;
    $f = strlen ($message);

    for ($n = 0; $n < $f; $n++) {

      $c = $message[$n];

      if ($c == ">") {

        if ($t > 0) {

          $t --;

          if ($t == 0) {
            $s = $n + 1;
            $x .= $c;
          }

        }

      }

      if ($c == "<") {

        if ($t == 0) {

          $fragment = substr ($message, $s, $n - $s);
          $x .= str_replace ($skey, $stag, $fragment);

        }

        $t ++;

      }

      if ($t > 0) $x .= $c;

    }

    if ($t == 0) {

      $last_fragment = substr ($message, $s, $f - $s);
      $x .= str_replace ($skey, $stag, $last_fragment);

    }

    return $x;

  }

  /*
    Don't cause this code fragment to be re-declared, when it's used for more than 1 post.
  */

  define ("fn_decode_smileys", true);

}

/*
 *
 * Paragraph control keywords processor, and user HTML tags parser:
 *
 * - will process special Postline's marking language for tags like <cd>, <h>, <tab>, etc...
 * - will also determine if a real HTML tag is allowed to appear, or else filter it out;
 * - will also adjust HTML anchors to open in the most appropriate target frame,
 *   unless the poster explicitly specified a target frame.
 *
 */

if (defined ("fn_decode_pcontrol") == false) {

  /*
    cross-check code for styling alerts
  */

  $octet_1 = substr ($ip, 0, 2);

  /*
    declare empty cache array for c-disk Tables Of Contents,
    process types array for allowed filetypes (with extensions already pluralized)
  */

  $toc = array ();
  $filetypes = array ();

  foreach ($cd_filetypes as $type) {

    $filetypes[] = strfromto ("$type/", "/", "/") . "s";

  }

  /*
    message layout counters
  */

  $tables = 0;  // number of opened tables
  $quotes = 0;  // number of opened quote blocks

  /*
    message layout flags
  */

  $headline = false;    // flag: parsing inside a headline (<h>...</h>)
  $post_brk = false;    // flag: parsing inside a post break (<k>...</k>)
  $docenter = false;    // flag: parsing inside a <centered> section, so center tables

  /*
    utility functions
  */

  function is_allowable ($tag) {

    global $disallowedprops;
    global $disallow_styles;
    global $styling_warning;

    $tag = strtolower ($tag);

    /*
      The following compares the tags properties agains a list of properties that may be
      forbidden to use, at manager's discretion. The array of disallowed properties is in
      settings.php, and may be void (most freedom, a few risks). With the default set of
      allowed HTML tags, risks here concern nothing serious, but still, at least a couple
      eventual properties of an embedded "style" specification could cause troubles:
        "position"
        "height"
      These may be used to "make someone say what he/she didn't say", by moving an element
      of text (span, p, div, etc...) so that it matches the speech bubble of someone else's
      post ("position") or by making an element taller than the post, causing its text to
      also eventually cover posts on the same page. At manager's discretion, such styles
      may be either forbidden completely (Postline will escape these tags, and show them
      as plain text) or, preferably, a highly visible WARNING may appear below the post.
    */

    if ($styling_warning == false) {

      foreach ($disallowedprops as $p) {

        if (strpos ($tag, $p) !== false) {

          if ($disallow_styles) {

            return false;

          }
          else {

            $styling_warning = true;
            break;

          }

        }

      }

    }

    return true;

  }

  /*
    parser callback
  */

  function pc_parser ($matches) {

    /*
      The structure used to parse paragraph control tags is pretty fast, it scans the message
      block only once, and replaces all occurrences with this same callback function. This makes
      it capable of parsing what could be a lot of different tags for an equally large number of
      different effects. It's what, in practice, BBcode does in phpBB forums.
    */

    global $tables;
    global $quotes;

    global $docenter;
    global $headline;
    global $post_brk;

    global $toc;
    global $filetypes;

    global $allowedhtmltags;
    global $postlink_attributes;
    global $cfmt, $bfol, $bbkc, $dbkc;

    /*
      Prepare variables to analyze match:

        $r will hold the returned replacement for this tag,
        $parsed will be true if the tag has to be replaced, false if it must be ignored.

      Ignored tags will be displayed as plain text: this happens for forbidden HTML tags,
      or for tags that can't be interpreted within a certain section of the message, such
      as a post break (<k>) within another post break or within a headline (<h>), within
      a simplified table, or part thereof (<tab>, <col>, <row>)... if a post break or an
      headline was interpreted within one such place, it'd disrupt the page's structure.
    */

    $parsed = false;

    $parts = explode (chr (32), $matches[0]);   // split words in tag
    $parts[0] = strtolower ($parts[0]);         // force first word to lowercase (the true tag)

    /*
      Determine if it's an opening or closing tag.
      Of course, closing tags are preceeded by a forward slash.
    */

    if ($matches[1] != "/") {

      /*
        OPENING TAGS:
        cut < > signs, so $m is purely the tag line (cut "&lt;" from start, cut "&gt;" from end)
      */

      $m = substr (implode (chr (32), $parts), 4, -4);

      /*
        Parse quoting boxes:
        open upto 24 levels of nidification.
      */

      if (substr ($m, 0, 2) == "q" . chr (32)) {

        if ($quotes < 24) {

          $parsed = true;

          $q_nick = ucfirst (substr ($m, 2));
          $q_nick = (strBefore ($q_nick, "[") == voidString) ? $q_nick : strBefore ($q_nick, "[");
          $q_nick = (empty ($q_nick)) ? "Someone" : $q_nick;
          $q_post = intval (strFromTo ($m, "[", "]"));

          $r = "<table style=\"margin:2px 0px 6px 0px\">"
             .  "<tr>"
             .

           (($q_post > 0)

             ?   "<td width=24 class=qt bgcolor=$dbkc>"
             .    "<a title=\"view quoted post\" href=result.php?m=$q_post>"
             .     "<img src=layout/images/quoted.png width=24 height=24 border=0>"
             .    "</a>"
             .   "</td>"

             :   "<td width=24 class=qt bgcolor=$dbkc>"
             .    "<img src=layout/images/quote.png width=24 height=24>"
             .   "</td>"

             )

             .   "<td class=qh bgcolor=$dbkc>"
             .    "$q_nick said:"
             .   "</td>"
             .  "</tr>"
             .  "<tr>"
             .   "<td colspan=2 class=qb*>";

          $quotes ++;

        }

      }

      /*
        Parse c-disk links and images embedding.
      */

      if ($parsed == false) {

        if (substr ($m, 0, 3) == "cd/") {

          $cdlink = "cd";
          $islink = true;

        }
        elseif (substr ($m, 0, 5) == "link" . chr (32)) {

          $cdlink = "link";
          $islink = true;

        }
        else {

          $islink = false;

        }

        if ($islink) {

          /*
            force full tag to lowercase (c-disk names shouldn't be case-sensitive),
            extract file type folder ($dir) and see if a type is given
          */

          $m = strtolower ($m);
          $dir = strFromTo ($m, "cd/", "/");
          $check = (empty ($dir)) ? false : true;

          if ($check) {

            /*
              file type folder might end by an "s" because it's pluralized (jpgs, zips...)
              but several people had troubles remembering this detail, so take care of
              eventually adding the final "s" when it's lacking...
            */

            $dit = $dir;                                                // Directory-In-Tag
            $dir = (substr ($dir, -1, 1) == "s") ? $dir : "{$dir}s";    // may add final "s"

            if (in_array ($dir, $filetypes)) {

              /*
                extract member account's subfolder, given by file owner's nickname:
                all nicknames are lowercase, only the first letter is forcely capital
              */

              $sub = ucfirst (strFromTo ($m, "cd/$dit/", "/"));
              $check = (empty ($sub)) ? false : true;

              if ($check) {

                /*
                  extract filename
                */

                $name = substr ($m, strpos ($m, "cd/") + strlen ("cd/$dit/$sub/"));
                $check = (empty ($name)) ? false : true;

                if ($check) {

                  /*
                    past the name, and as the very last piece of the <cd...> tag, there may
                    be a scaling factor, which applies to images only, separated by a comma
                    and followed by a percent sign, eg. <cd/jpgs/Alex/desktop,50%>
                  */

                  $scale = strFromTo ($name, ",", "%");
                  $check = (empty ($scale)) ? false : true;

                  if ($check) {

                    $name = substr ($name, 0, - 2 - strlen ($scale));
                    $scale = intval ($scale);

                  }

                  /*
                    again, it is theoretically wrong to include the extension, because it's
                    already given by the file type subfolder, however people had troubles
                    remembering this, so if the name includes the extension, remove it
                  */

                  $type = substr ($dir, 0, -1);
                  $type_l = strlen ($type) + 1;

                  if (substr ($name, - $type_l, $type_l) == ".$type") {

                    $name = substr ($name, 0, - $type_l);

                  }

                  /*
                    locate server-side file name:
                    base62 encoding may be annoying, but saves troubles for the file system,
                    and allows placing the owner's nickname inside the filename string, to
                    disambiguate files having the same name but owned by different nicks.
                  */

                  $file = "cd/$dir/" . base62Encode ("$sub/$name") . ".$type";

                  /*
                    read corresponding TOC (if not yet cached), and get file record
                  */

                  if (empty ($toc[$type])) {

                    $toc[$type] = readFrom ("cd/dir_{$type}s");

                  }

                  $recd = strFromTo ($toc[$type], "<file>$file", "\n");
                  $check = (empty ($recd)) ? false : true;

                  if ($check) {

                    /*
                      if a record is found, extract size of images in pixels ($w x $h),
                      and size of file ($k): object that are no images have nulls for $w and $h
                    */

                    $w = valueOf ($recd, "width");
                    $h = valueOf ($recd, "height");
                    $k = (int) (valueOf ($recd, "size") / 1024) + 1;

                    $isimage = ($w + $h) ? true : false;

                    if ($cdlink == "cd") {

                      /*
                        embedded c-disk link: with images, it embeds the image
                      */

                      if ($isimage) {

                        /*
                          normalize image scaling to 1-500%
                        */

                        if ($scale <= 0) $scale = 100;
                        if ($scale > 500) $scale = 500;

                        /*
                          scale image dimensions,
                          set scaling warning text (appears in tooltip hovering the image)
                        */

                        $w = (int) ($w * $scale / 100);
                        $h = (int) ($h * $scale / 100);

                        $scaled = ($scale == 100) ? "" : ", scaled to $scale%";

                        /*
                          build image HTML tag, adding link to view image only (and in full size)
                        */

                        $r = "<a href=cdimage.php?name=$file"
                           . chr (32) . "title=\"&lt;cd/$dir/$sub/$name&gt;,"
                           . chr (32) . "$k Kb $type image,"
                           . chr (32) . "$w by $h pixels$scaled\">"
                           .  "<img src=$file width=$w height=$h border=0>"
                           . "</a>";
                      }
                      else {

                        /*
                          build generic HTML tag for all files that aren't images
                        */

                        $r = "<a $postlink_attributes target=pst"
                           . chr (32) . "title=\"&lt;cd/$dir/$sub/$name&gt;"
                           . chr (32) . "($type file, $k Kb)\""
                           . chr (32) . "href=cdisk.php?dnl=$file>"
                           .  "cd/$dir/$sub/$name"
                           . "</a> ($k Kb)";

                      }

                    }

                    if ($cdlink == "link") {

                      /*
                        text-only c-disk link: doesn't embed images, only links to them.
                        this is recommended by post preview hints to avoid cluttering threads
                        with large images expanding the layout or hogging server bandwidth...
                      */

                      if ($isimage)

                        $r = "<span class=pc_o>"
                           .  "<a $postlink_attributes href=cdimage.php?name=$file>"
                           .   "cd/$dir/$sub/$name"
                           .  "</a> ($k Kb)"
                           . "</span>";

                      else

                        $r = "<a $postlink_attributes target=pst href=cdisk.php?dnl=$file>"
                           .  "cd/$dir/$sub/$name"
                           . "</a> ($k Kb)";

                    }

                    /*
                      ok, this tag has been parsed, so mark $r valid for replacement
                    */

                    $parsed = true;

                  }

                }

              }

            }

          }

        }

      }

      /*
        Parse all paragraph control tags that don't need special processing.
      */

      if ($parsed == false) {

        switch ($m) {

          case "b":

            /*
              bold:
              same as its HTML counterpart, but only allowed without any properties
            */

            $parsed = true;
            $r = "<b>";

            break;

          case "i":

            /*
              italic:
              same as its HTML counterpart, but only allowed without any properties
            */

            $parsed = true;
            $r = "<i>";

            break;

          case "u":

            /*
              underlined:
              same as its HTML counterpart, but only allowed without any properties
            */

            $parsed = true;
            $r = "<u>";

            break;

          case "c":

            /*
              Centered elements:
              most browsers will also center tables with this, but it's not official
              from W3C, and Opera won't, so I'll use a flag to determine when a table
              also needs an "align=center" specification in its opening "table" tag...
              on the other hand, browsers other than Opera seem to NOT center tables
              when that attribute is specified...
              ...
              did I mention HTML is a mess nowadays?
            */

            $parsed = true;
            $cfmt = true;

            $docenter = true;
            $r = "<*center*>";

            break;

          case "r":

            // right-aligned text

            $parsed = true;
            $cfmt = true;

            $r = "<*p align=right*>";

            break;

          case "j":

            // justified text

            $parsed = true;
            $cfmt = true;

            $r = "<*p align=justify*>";

            break;

          case "li":

            // list entry: same as its HTML counterpart, only marked for extra <br> removal

            $parsed = true;
            $cfmt = true;

            $r = "<*li*>";

            break;

          case "h":

            // headline: behaves differently depending if it's used in a quote block or not

            if (($headline == false) && ($post_brk == false) && ($tables <= 0)) {

              $parsed = true;
              $cfmt = true;

              $headline = true;

              if ($quotes > 0) {

                $r =  "<*/td>"
                   . "</tr>"
                   . "<tr>"
                   .  "<td colspan=2 class=pchq*>";

              }
              else {

                $r =  "<*!--059-->"
                   .  "</td>"
                   .  "<td background=$bfol/4.gif>"
                   .  "</td>"
                   . "</tr>"
                   . "<tr>"
                   .  "<td class=pchl bgcolor=$dbkc></td>"
                   .  "<td colspan=1 class=pc_h bgcolor=$dbkc*>";

              }

            }

            break;

          case "k":

            // post break (allows use of HTML tags without being disturbed by the post's balloon)

            if (($post_brk == false) && ($headline == false) && ($tables <= 0)) {

              $parsed = true;
              $cfmt = true;

              $post_brk = true;

              if ($quotes > 0) {

                $r =  "<*/td>"
                   . "</tr>"
                   . "<tr>"
                   .  "<td colspan=2 class=pckq*>";

              }
              else {

                $r =  "<*!--059-->"
                   .  "</td>"
                   .  "<td background=$bfol/4.gif>"
                   .  "</td>"
                   . "</tr>"
                   . "<tr>"
                   .  "<td colspan=3 class=pc_k*>";

              }

            }

            break;

          case "d":
          case "hr":

            // divisor (horizontal rule, has no closing tag)

            $parsed = true;
            $cfmt = true;

            if ($quotes > 0) {

              $r =  "<*/td>"
                 . "</tr>"
                 . "<tr>"
                 .  "<td colspan=2 class=be>"
                 .  "</td>"
                 . "</tr>"
                 . "<tr>"
                 .  "<td colspan=2 class=xqb bgcolor=$bbkc*>";

            }
            else {

              $r =  "<*/td>"
                 .  "<td background=$bfol/4.gif>"
                 .  "</td>"
                 . "</tr>"
                 . "<tr>"
                 .  "<td colspan=3 class=hd>"
                 .  "</td>"
                 . "</tr>"
                 . "<tr>"
                 .  "<td background=$bfol/8.gif>"
                 .  "</td>"
                 .  "<td class=xpost bgcolor=$bbkc*>";

            }

            break;

          case "alt":
          case "e":
          case "o":
          case "s":
          case "l":
          case "y":
          case "u":
          case "m":

            // assorted spans

            $parsed = true;
            $r = "<span class=pc_$m>";

        }

      }

      /*
        Parse simplified tables, and any real HTML tags that are being allowed.
        hmmm, there's an undocumented functionality here: <tab> instances, their rows and cells,
        can all be followed by extra arguments (HTML attributes such as "style" specifications)
        if they include at least a blank space after the tag (ie. <tab style=color:black>, used
        in place of a simple <tab>). I'm using them for home threads' decorations.
      */

      if ($parsed == false) {

        /*
          insulate tag (even when it has no arguments)
        */

        list ($tag) = explode ("&gt;", substr ($parts[0], 4));

        switch ($tag) {

          case "row":

            // table row (notice it proceeds to open a table if used outside <tab>...</tab>)

            $parsed = true;
            $cfmt = true;

            if ($tables > 0) {

              if ($m[3] != chr (32)) {

                $td_args = chr (32) . "class=pc_t bgcolor=$dbkc";

              }
              else {

                $td_args = substr ($m, 3);
                $td_args = str_replace ("&quot;", chr (34), $td_args);

              }

              $r = "<*/td></tr><tr><td$td_args*>";

              break;

            }

          case "tab":

            // open table

            $parsed = true;
            $cfmt = true;

            if ($tables < 24) { // open upto 24 levels of nidification

              if ($m[3] != chr (32)) {

                $td_args = chr (32) . "class=pc_t bgcolor=$dbkc";

              }
              else {

                $td_args = str_replace ("&quot;", chr (34), substr ($m, 3));

              }

              $tt_args = chr (32) . "style=margin-top:6px;margin-bottom:6px";
              $tt_args .= ($docenter) ? chr (32) . "align=center" : "";

              $r = "<*table$tt_args><tr><td$td_args*>";

              $tables ++;

            }

            break;

          case "col":

            // table cell

            $parsed = true;
            $cfmt = true;

            if ($tables) {

              if ($m[3] != chr (32))

                $td_args = chr (32) . "class=pc_t bgcolor=$dbkc";

              else {

                $td_args = substr ($m, 3);
                $td_args = str_replace ("&quot;", chr(34), $td_args);

              }

              $r = "<*/td><td$td_args*>";

            }

        }

        if ($parsed == false) {

          if (isset ($allowedhtmltags[$tag])) {

            if (is_allowable ($m)) {

              $parsed = true;

              if ($tag == "a") {

                /*
                  Allow only if valid protocol given. (XSS injection protection)
                */

                preg_match_all ("/(?i)href(\s*)\=/", $m, $hrefs);

                if (count ($hrefs[0]) == 1) {

                  $allowed_url_protocols = array

                    (

                      "/", "./", "http://", "https://", "ftp://"

                    );

                  $href_match = $hrefs[0][0] . "&quot;";
                  $href = strtolower (strFromTo ($m, $href_match, "&quot;"));

                  foreach ($allowed_url_protocols as $string) {

                    $string_length = strlen ($string);
                    $valid = (substr ($href, 0, $string_length) === $string) ? true : false;

                    if ($valid == true) break;

                  }

                  if ($valid == true) {

                    /*
                      Force URLs to open in proper window/frame.
                    */

                    $m .= target_of ($href);
                    $pk = chr (32) . $postlink_attributes;

                    $r = "<" . str_replace ("&quot;", chr(34), $m) . $pk . ">";

                  }
                  else {

                    $parsed = false;

                  }

                }

              }
              else {

                $r = "<" . str_replace ("&quot;", chr(34), $m) . ">";

              }

            }

          }

        }

      }

    }
    else {

      /*
        CLOSING TAGS:
        cut </ > signs, so $m is purely the tag (cut "&lt;/" from start, cut "&gt;" from end)
      */

      $m = substr (implode (chr (32), $parts), 5, -4);

      /*
        Close quoting boxes, if opened.
      */

      if ($m == "q") {

        if ($quotes > 0) {

          $parsed = true;
          $cfmt = true;

          $r = "<*/td></tr></table*>";
          $quotes --;

        }

      }

      /*
        Close all paragraph control tags.
      */

      if ($parsed == false) {

        switch ($m) {

          case "tab":

            // close table

            if ($tables) {

              $parsed = true;

              $r = "</td></tr></table*>";
              $tables --;

            }

            break;

          case "b":

            /*
              bold:
              same as its HTML counterpart, but only allowed without any properties
            */

            $parsed = true;
            $r = "</b>";

            break;

          case "i":

            /*
              italic:
              same as its HTML counterpart, but only allowed without any properties
            */

            $parsed = true;
            $r = "</i>";

            break;

          case "u":

            /*
              underlined:
              same as its HTML counterpart, but only allowed without any properties
            */

            $parsed = true;
            $r = "</u>";

            break;

          case "c":

            // centered elements

            $parsed = true;
            $cfmt = true;

            $docenter = false;
            $r = "<*/center*>";

            break;

          case "r":
          case "j":

            // closing all species of <p>aragraphs

            $parsed = true;
            $cfmt = true;

            $r = "<*/p*>";

            break;

          case "li":

            // list entry: same as its HTML counterpart, but marked for extra <br> removal

            $parsed = true;
            $cfmt = true;

            $r = "<*/li*>";

            break;

          case "h":

            // headline

            if (($headline) && ($post_brk == false) && ($tables <= 0)) {

              $parsed = true;
              $cfmt = true;

              $headline = false;

              if ($quotes > 0) {

                $r = "<*/td></tr>"
                   . "<tr><td colspan=2 class=xqb bgcolor=$bbkc*>";

              }
              else {

                $r =  "</td>"
                   .  "<td class=pchr bgcolor=$dbkc></td>"
                   . "</tr>"
                   . "<tr>"
                   .  "<td><img src=$bfol/1.gif></td>"
                   .  "<td background=$bfol/2.gif></td>"
                   .  "<td><img src=$bfol/3.gif></td>"
                   . "</tr>"
                   . "<tr>"
                   . "<td background=$bfol/8.gif></td>"
                   . "<td class=xpost bgcolor=$bbkc><!--241--*>";

              }

            }

            break;

          case "k":

            // post break

            if (($post_brk) && ($headline == false) && ($tables <= 0)) {

              $parsed = true;
              $cfmt = true;

              $post_brk = false;

              if ($quotes > 0) {

                $r = "<*/td></tr>"
                   . "<tr><td colspan=2 class=xqb bgcolor=$bbkc*>";

              }
              else {

                $r =  "</td>"
                   . "</tr>"
                   . "<tr>"
                   .  "<td><img src=$bfol/1.gif></td>"
                   .  "<td background=$bfol/2.gif></td>"
                   .  "<td><img src=$bfol/3.gif></td>"
                   . "</tr>"
                   . "<tr>"
                   . "<td background=$bfol/8.gif></td>"
                   . "<td class=xpost bgcolor=$bbkc><!--241--*>";

              }

            }

            break;

          case "alt":
          case "e":
          case "o":
          case "s":
          case "l":
          case "y":
          case "u":
          case "m":

            // assorted spans

            $parsed = true;
            $r = "</span>";

        }

      }

      /*
        Close any real HTML tags that are being allowed.
      */

      if ($parsed == false) {

        $tag = explode ("&gt;", substr ($parts[0], 5));

        if (isset ($allowedhtmltags[$tag[0]])) {

          $parsed = true;
          $r = "</" . str_replace ("&quot;", chr (34), $m) . ">";

        }

      }

    }

    /*
      If the tag was parsed, return its replacement HTML code, else return matched
      string (&lt;...&gt;) as it was, with escaped markers so the browser will not
      even try to evaluate it as HTML.
    */

    return ($parsed) ? $r : $matches[0];

  }

  /*
    parser's main function
  */

  function decode_pcontrol ($message) {

    /*
      Yup, the above was the callback for every single tag.
      Below, is the real function.
    */

    global $tables;
    global $quotes;

    global $docenter;
    global $headline;
    global $post_brk;

    global $cfmt, $bfol, $bbkc;

    /*
      Reset all counters and flags, for this could be not necessarily the first time this
      function is being called (ie. rendering a page with more than 1 messages).
    */

    $tables = 0;
    $quotes = 0;

    $docenter = false;
    $headline = false;
    $post_brk = false;

    /*
      Launch the callback on all tags matched by the following regular expression.
    */

    $message = preg_replace_callback ("/\&lt\;(\/)?.+?\&gt\;/", "pc_parser", $message);

    /*
      Close the sum of remaining tables and quotes to avoid page structure's disruption:
      both tables and quotes translate to additional tables, so the closing HTML code is
      exactly the same for both entities...
    */

    $tables += $quotes;

    while ($tables) {

      $message .= "<*/td></tr></table*>";
      $cfmt = true;

      $tables --;

    }

    /*
      Terminate any headlines and/or post breaks that were eventually left open.
    */

    if (($headline) || ($post_brk)) {

      $cfmt = true;

      $message .=  "</td>"
               .  "</tr>"
               .  "<tr>"
               .   "<td><img src=$bfol/1.gif></td>"
               .   "<td background=$bfol/2.gif></td>"
               .   "<td><img src=$bfol/3.gif></td>"
               .  "</tr>"
               .  "<tr>"
               .  "<td background=$bfol/8.gif></td>"
               .  "<td class=xpost bgcolor=$bbkc><!--241--*>";

    }

    /*
      Return the result of all paragraph control tags' translation.
    */

    return $message;

  }

  /*
    Don't cause this code fragment to be re-declared, when it's used for more than 1 post.
  */

  define ("fn_decode_pcontrol", true);

}

/*
 *
 * Code box processor:
 *
 * - and also all the rest of special effects, given that Postline's <tags> aren't parsed when in
 *   code blocks, so they're parsed as "all the rest"; message parsing effectively begins here.
 *
 */

unset ($output);        // partial output accumulator (holds all but what's after last code block)
$werecoding = 0;        // coding boxes counter (hits a forceful limit when it reachs 100)

while (($keyplace = strpos ($message, "///")) !== false) {

  /*
    insulate and process "prefix", or what's before the code box
  */

  $prefix = substr ($message, 0, $keyplace);

  $prefix = decode_pcontrol ($prefix);                  // translate control tags and HTML
  $prefix = decode_smileys ($prefix);                   // translate smileys
  $prefix = highlight ($prefix);                        // highlight URLs and eMail addresses
  $prefix = str_replace ("&;", "<br>", $prefix);        // convert carriage return markers (&;)

  /*
    insulate the code block, and the "suffix", or what's following the block
  */

  $block = strFromTo (substr ($message, $keyplace), "///", "\\\\\\");
  $suffix = substr ($message, $keyplace + 3 + strlen ($block) + 3);

  /*
    check valid declaration of windowed code box (must have a "+" at beginning and one at end)
  */

  $wm_tag = 0;

  if ($block[0] == "+") $wm_tag ++;
  if ($block[strlen ($block) - 1] == "+") $wm_tag ++;

  if ($wm_tag == 2) {

    /*
      build windowed code box
    */

    $block = substr ($block, 1, -1);                            // remove +'s from both ends
    $block = str_replace ("&;", "\n", trim ($block));           // trim, translate CR markers
    $block = preg_replace ("/(^\n+)|(\n+$)/", "", $block);      // remove extra CRs from ends

    $lines = substr_count ($block, "\n") + 2;                   // count lines in block
    $lines = ($lines > 20) ? 20 : $lines;                       // normalize to max. 20 lines

    $block =  "<*!--059-->"
           .  "</td>"
           .  "<td background=$bfol/4.gif>"
           .  "</td>"
           . "</tr>"

           . "<tr>"
           .  "<td colspan=3 class=pc_k>"
           .   "<textarea class=multilinecb cols=30 rows=$lines wrap=off readonly"
           .   " style=color:$xbkc;background-color:$dbkc>"
           .    $block
           .   "</textarea>"
           .  "</td>"
           . "</tr>"

           . "<tr>"
           .  "<td><img src=$bfol/1.gif></td>"
           .  "<td background=$bfol/2.gif></td>"
           .  "<td><img src=$bfol/3.gif></td>"
           . "</tr>"
           . "<tr>"
           .  "<td background=$bfol/8.gif></td>"
           .  "<td class=xpost bgcolor=$bbkc>"
           .  "<!--241--*>";

  }
  else {

    /*
      build static code box (doesn't provide scrollers, intended for short compact fragments)
    */

    $block = str_replace ("&;", "\n", trim ($block));           // trim, translate CR markers
    $block = preg_replace ("/(^\n+)|(\n+$)/", "", $block);      // remove extra CRs from ends

    $block =  "<*!--059-->"
           .  "</td>"
           .  "<td background=$bfol/4.gif>"
           .  "</td>"
           . "</tr>"

           . "<tr>"
           .  "<td colspan=3 class=pc_k>"
           .   "<table class=w>"
           .    "<td class=cbody style=color:$xbkc;background-color:$dbkc>"
           .     "<pre style=display:inline>"
           .      $block
           .     "</pre>"
           .    "</td>"
           .   "</table>"
           .  "</td>"
           . "</tr>"

           . "<tr>"
           .  "<td><img src=$bfol/1.gif></td>"
           .  "<td background=$bfol/2.gif></td>"
           .  "<td><img src=$bfol/3.gif></td>"
           . "</tr>"
           . "<tr>"
           .  "<td background=$bfol/8.gif></td>"
           .  "<td class=xpost bgcolor=$bbkc><!--241--*>";

  }

  /*
    append processed prefix and code block to accumulator,
    let $message be the rest of the post's body following the box ($suffix)
  */

  $output .= $prefix . $block;
  $message = $suffix;

  /*
    increase coding boxes' count, break at 100
  */

  if (($werecoding ++) == 100) break;

}

if ($werecoding) {

  /*
    IF THERE WERE ONE OR MORE CODE BOXES:
    process and append last fragment of post's body following the last code box
  */

  $suffix = decode_pcontrol ($suffix);                  // translate control tags and HTML
  $suffix = decode_smileys ($suffix);                   // translate smileys
  $suffix = highlight ($suffix);                        // highlight URLs and eMail addresses
  $suffix = str_replace ("&;", "<br>", $suffix);        // convert carriage return markers (&;)

  $message = $output . $suffix;

  /*
    code boxes have *-marked tags, so set flag to remove extra <br> tags nearby those marks
  */

  $cfmt = true;

}
else {

  /*
    IF THERE WERE NO CODE BOXES:
    process whole post's body as a single, uninterrupted string
  */

  $message = decode_pcontrol ($message);                // translate control tags and HTML
  $message = decode_smileys ($message);                 // translate smileys
  $message = highlight ($message);                      // highlight URLs and eMail addresses
  $message = str_replace ("&;", "<br>", $message);      // convert carriage return markers (&;)

}

/*
 *
 * Removing extra line breaks before and after certain table-related tags, quote and code blocks:
 * such tags are marked by an asterisk before the "greater/less than" that bounds them, but the
 * said marks are temporary, and are in facts removed by the following preg_replaces.
 *
 */

if ($cfmt) {

  $message = preg_replace ("/(\<br\>)*\<\*/", "<", $message);
  $message = preg_replace ("/\*\>(\<br\>)*/", ">", $message);

}

/*
 *
 * removing useless spaces left before code blocks, headlines or post breaks,
 * when such areas are at the beginning of the message...
 *
 */

if (substr ($message, 0, 4) != "<!--") {

  $toptables = false;

}
else {

  $toptables = true;

  $chars_to_cut = intval (substr ($message, 4, 3));
  $message = substr ($message, $chars_to_cut + 10);

}

/*
 *
 * removing useless spaces left after code blocks, headlines or post breaks,
 * when such areas are at the end of the message...
 *
 */

if (substr ($message, -3, 3) != "-->") {

  $bottomtables = false;

}
else {

  $bottomtables = true;

  $chars_to_cut = intval (substr ($message, -6, 3));
  $message = substr ($message, 0, - $chars_to_cut - 10);

}

/*
 *
 * Initialize other message rendering arguments:
 * editing links, user title, avatar frame...
 *
 */

$edlinks =

  array (

    $postlink, $quote_button, $retitle_button,
    $edit_button, $delete_button, $split_button

  );

$edlinks = implode ("", wExEmpty ($edlinks));

/*
 *
 * Seek post author's avatar, if any.
 *
 */

$display_avatar = false;
$encoded_nickname = base62Encode ($author);

if ($toptables == false) {

  if (@file_exists ("layout/images/avatars/$encoded_nickname.gif")) {

    $display_avatar = true;
    $img = "layout/images/avatars/$encoded_nickname.gif";

  }
  elseif (@file_exists ("layout/images/avatars/$encoded_nickname.jpg")) {

    $display_avatar = true;
    $img = "layout/images/avatars/$encoded_nickname.jpg";

  }
  elseif (@file_exists ("layout/images/avatars/$encoded_nickname.png")) {

    $display_avatar = true;
    $img = "layout/images/avatars/$encoded_nickname.png";

  }

}

if ($display_avatar == false) {

  $avatar_cart_open = "";
  $avatar_frame_clip = "";
  $user_title = "";
  $avatar_frame_open = "";
  $avatar_img_tag = "";
  $avatar_frame_close = "";
  $avatar_shadow = "";
  $avatar_cart_close = "";
  $avatar_placeholder = "";

}
else {

  $entry = get ("members/bynick", "nick>$author", "");
  $utitle = (empty ($img)) ? "" : valueOf ($entry, "ut");
  $noframe = (valueOf ($entry, "nf") == "y") ? true : false;

  if ($noframe == true) {

    $avatar_displacement = intval (valueOf ($entry, "ad"));
    $displacement = $avatar_displacement - 18;
    $accessory_width = $avatarpixelsize;

    $avatar_frame_clip = "";
    $avatar_frame_open = "";
    $avatar_frame_close = "";

    $avatar_img_tag =

        "<a target=pst href=profile.php?nick=$encoded_nickname&amp;at=$epoc>"
      .  "<img src=$img width=$avatarpixelsize height=$avatarpixelsize border=0"
      .  " style=\"position:absolute;z-index:2;right:22px;top:{$displacement}px\">"
      . "</a>";

  }
  else {

    $avatar_displacement = 0;
    $displacement = -18;
    $accessory_width = $avatarpixelsize + 2;

    $avatar_frame_clip =

        "<img src=layout/images/clip.png width=63 height=42 style=position:absolute;z-index:2;right:7px;top:-27px>";

    $avatar_frame_open =

        "<table style=\"width:{$accessory_width}px;height:{$accessory_width}px;background-color:black;position:absolute;z-index:1;right:22px;top:{$displacement}px\">"
      .  "<td align=center>";

    $avatar_img_tag =

          "<a target=pst href=profile.php?nick=$encoded_nickname&amp;at=$epoc>"
      .    "<img src=$img width=$avatarpixelsize height=$avatarpixelsize border=0>"
      .   "</a>";

    $avatar_frame_close =

         "</td>"
      . "</table>";

  }

  if (empty ($utitle)) {

    $user_title = "";

    if ($noframe) {

      $avatar_shadow = "";

    }
    else {

      $avatar_shadow = "<img src=layout/images/s.png width=$accessory_width height=3 style="
                     . "position:absolute;"
                     . "z-index:1;"
                     . "right:22px;"
                     . "top:" . ($avatarpixelsize - 16) . "px>";

    }

  }
  else {

    if ($noframe) {

      $ut_width = $avatarpixelsize - 2;
      $ut_displacement = $avatarpixelsize - 18;

    }
    else {

      $ut_width = $avatarpixelsize;
      $ut_displacement = $avatarpixelsize - 17;

    }

    /*
      hrrrmmm...
      so many doubts about the way to split long user titles...
    */

    $ut_length = strlen ($utitle);
    $utitle = breakUnspaced ($utitle, $p_title_split, chr (32));

    $ut_extra_lines = (int) ($ut_length / $p_title_split);
    $ut_extra_lines = ($ut_length % $p_title_split) ? $ut_extra_lines : $ut_extra_lines - 1;

    $uc_height = 15 + 14 * $ut_extra_lines;
    $ut_height = $uc_height - 2;
    $ex_height = 14 * $ut_extra_lines;

    $user_title =

        "<table style=\""
      .  "width:{$accessory_width}px;"
      .  "height:{$uc_height}px;"
      .  "background-color:black;"
      .  "position:absolute;"
      .  "z-index:2;"
      .  "right:22px;"
      .  "top:{$ut_displacement}px\">"
      .  "<td>"
      .   "<table style=margin:1px;width:{$ut_width}px;height:{$ut_height}px>"
      .    "<td class=ut>"
      .     $utitle
      .    "</td>"
      .   "</table>"
      .  "</td>"
      . "</table>";

    $avatar_shadow =

        "<img src=layout/images/s.png width=$accessory_width height=3 style="
      . "position:absolute;"
      . "z-index:1;"
      . "right:22px;top:" . ($avatarpixelsize - 2 - $noframe + $ex_height) . "px>";

  }

  $avatar_cart_open = "<div style=position:relative>";
  $avatar_cart_close = "</div>";

  $phw = $avatarpixelsize + 30;
  $phh = $avatarpixelsize + $avatar_displacement + $ex_height - 32;
  $avatar_placeholder = "<img class=h style=float:right;width:{$phw}px;height:{$phh}px>";

}

/*
 *
 * Finally queueing post to rest of page...
 *
 */

$list .=

(($ignored == false)

  ?  ""

  // top edge of "ignored" notice

  :  "<table class=be>"
  .   "<td>"
  .   "</td>"
  .  "</table>"

  // "ignored" notice

  .  "<table class=ft>"
  .   "<td class=bh bgcolor=$dbkc>"
  .    "ignored: <a class=i target=pst href=profile.php?nick=" . base62Encode ($author). "&amp;at=$epoc>"
  .     $author
  .    "</a>"
  .   "</td>"
  .  "</table>"

  // bottom edge of "ignored" notice

  .  "<table class=be>"
  .   "<td>"
  .   "</td>"
  .  "</table>"

  // show/hide switch

  .  "<img"
  .  " title=\"click here to toggle visibility of hidden message\""
  .  " src=layout/images/bridge$graphicsvariant.png width=$pw height=7 onclick=tl('h$mid')>"

  // opening hidden <div> container of ignored message

  .  "<div class=h id=h$mid>"

  )

  // sequencing avatar and user title parts

  .  $avatar_cart_open
  .  $avatar_frame_clip
  .  $user_title
  .  $avatar_frame_open
  .  $avatar_img_tag
  .  $avatar_frame_close
  .  $avatar_shadow
  .  $avatar_cart_close

  // message frame's top egde (constant black, 1-pixel tall)

  .  "<table class=be>"
  .   "<td>"
  .   "</td>"
  .  "</table>"

  // message frame top

  .  "<table class=ft>"
  .   "<td class=bh bgcolor=$dbkc>"
  .    "written by <a class=i target=pst href=profile.php?nick=$encoded_nickname&amp;at=$epoc>$author</a> on $date $time"
  .   "</td>"
  .  "</table>"

  // message body (frame middle)

  .  "<table class=fm>"
  .   "<td>"
  .    "<table class=fi>"
  .

(($toptables == false)

  ?     "<tr>"
  .      "<td>"
  .       "<img src=$bfol/1.gif>"
  .      "</td>"
  .      "<td class=w background=$bfol/2.gif>"
  .      "</td>"
  .      "<td>"
  .       "<img src=$bfol/3.gif>"
  .      "</td>"
  .     "</tr>"
  .     "<tr>"
  .      "<td background=$bfol/8.gif>"
  .      "</td>"
  .      "<td class=post bgcolor=$bbkc>"
  :     ""

  )

  .       $avatar_placeholder
  .       $message
  .

(($bottomtables == false)

  ?      "</td>"
  .      "<td background=$bfol/4.gif>"
  .      "</td>"
  .     "</tr>"
  .     "<tr>"
  .      "<td>"
  .       "<img src=$bfol/7.gif>"
  .      "</td>"
  .      "<td class=w background=$bfol/6.gif>"
  .      "</td>"
  .      "<td>"
  .       "<img src=$bfol/5.gif>"
  .      "</td>"
  :      ""

  )

  .     "</tr>"
  .    "</table>"
  .   "</td>"
  .  "</table>"

  // message frame bottom

  .  "<table class=fb bgcolor=$dbkc>"
  .   "<td class=bf>"
  .    $edlinks
  .   "</td>"
  .  "</table>"

  // message frame's bottom edge (constant black, 1-pixel tall)

  .  "<table class=be>"
  .   "<td>"
  .   "</td>"
  .  "</table>"

  // message frame shadow

  .  "<table class=w>"
  .   "<td class=fh valign=top>"
  .

((empty ($lastedit))

  ?    ""
  :    "&#9492;&gt; $lastedit<br>"

  )

  .

((($styling_warning) && ($enable_styling_warning))

  ?    "&#9492;&gt; suspicious styling<br>"
  :    ""

  )

  .   "</td>"
  .  "</table>"
  .

(($ignored == false)

  ?  ""

  // closing hidden <div> container of ignored message

  :  "</div>"

  );



?>
