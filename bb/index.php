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



define ('going', true);
error_reporting (E_ALL ^ E_NOTICE);

/*
 *
 * this file works as the index of the central frames, and is also used to load up PHP scripts
 * in couples, whenever the left frame is requested to follow the right frame by presenting
 * common operations in respect to what is the right frame holding at that moment.
 *
 */

include ("settings.php");

/*
 *
 * string conversion functions were in suitcase.php, but for some cpu usage optimization,
 * the whole suitcase.php isn't included, and relevant functions redeclared below...
 *
 */

/*
 *
 * generic strings management utilities...
 *
 */

function strfromto ($string, $from, $to) {

  /*
    returns the part of $string laying between substring $from and substring $to,
    for eg. strfromto ("anywhere i am", "anywhere", "am") would return " i "
  */

  $stbeg = strpos ($string, $from);

  if ($stbeg === false) {

    return "";

  }
  else {

    $stbeg += strlen ($from);
    $stend  = strpos ($string, $to, $stbeg);
    $stend  = ($stend === false) ? strlen ($string) : $stend;

    return substr ($string, $stbeg, $stend - $stbeg);

  }

}

/*
 *
 * base-62 strings encoding (this is my own scaled-down version of base-64)
 *
 * in theory, any characters between 32 and 255 are successfully encoded by "encode" and
 * the resulting base-62 alphanumeric string is decoded by "decode"; what they are thought
 * for is passing string parameters throught "get" arguments of an hyperlink, and more
 * specifically, they're used for instance for passing membernames to the "profile" script;
 * in this way, text strings containing particular characters used as markers in URLs, are
 * always passed transparently. Yet, the strings must not contain text control codes below
 * ASCII code 32 (such as line breaks having code 13, tabulations having code 9, etc...),
 * not that it wouldn't be impossible to encode them, but the links would take more space
 * because a group of 4 digits in base 62 would not have enough numeric range to hold the
 * sums of groups of 3 ASCII letters, if the ASCII codes weren't previously reduced by 31
 * units, 31 being the code terminating an encoded block of text.
 *
 */

function convert_to_base62 ($number) {

  /*
    converts $number from base 10 to base 62:
    digits 10 to 35 are represented by lowercase letters,
    digits 36 to 61 are represented by uppercase letters.
  */

  $result = "";

  do {

    $d = $number % 62;
    $number = (int) ($number / 62);

    if ($d < 10) {

      $result .= chr ($d + 48);

    }
    else {

      $result .= ($d < 36) ? chr ($d - 10 + 97) : chr ($d - 36 + 65);

    }

  } while ($number);

  return $result;

}

function convert_from_base62 ($number) {

  /*
    converts $number from base 62 to base 10:
    $number is in reality given to this function as an alphanumeric string,
    but the function still outputs an integer value.
  */

  $k = 1;                       // multiplier, will increase in powers of 62, starting at 62^0
  $result = 0;                  // result accumulator (integer, numeric)

  $c = 0;                       // string analysis loop counter (0 = first character)
  $n = strlen ($number);        // when $c gets to $n, the input string is over

  while ($c < $n) {

    $d = ord ($number[$c]);

    if (($d >= 48) && ($d <=  57)) $result += $k * ($d - 48);           // simple digit
    if (($d >= 97) && ($d <= 122)) $result += $k * ($d - 97 + 10);      // lowercase letter
    if (($d >= 65) && ($d <=  90)) $result += $k * ($d - 65 + 36);      // uppercase letter

    $k *= 62;

    $c ++;

  }

  return $result;

}

function encode ($string) {

  /*
    encodes $string to base62:
    the function divides the input string in chunks of upto 3 characters each
    padding an eventually incomplete chunk with zeroes, and then assembling a
    single large integer number for each chunk; assuming the three characters
    of a chunk were ASCII codes x,y,z, conversion is based on the formula:

      c = x + 225*y + 225^2*z

    after which, c (the said large integer number) is converted to base 62 by
    the corresponding function declared above; the resulting base62 string is
    finally forced to be 4 characters long by right-padding it, if necessary,
    with zeroes (being strings, I mean the character "0", or ASCII #48), then
    it's added to $result, which accumulates those 4-character substrings and
    in the end forms the output (encoded) string returned by this function.
    Note that final trailing "0" characters are removed to shorten the result.
  */

  $result = ""; // accumulator
  $c = 0;       // character index in $string

  while (1) {

    /*
      stop upon reaching the end of $string
    */

    if ($string[$c] == "") {

      break;

    }

    /*
      get 3-characters chunk: this chunk may be shorter than 3 characters, but
      it wouldn't be a problem because sebsequent code will normalize any void
      character (ASCII #0) to a null value...
    */

    $sub = substr ($string, $c, 3);

    /*
      encode each character as a number given by its ASCII code, minus 31;
      if one of the characters has ASCII < 31, encode it as zero...
    */

    $ch1 = ord ($sub[0]) - 31; if ($ch1 < 0) $ch1 = 0;
    $ch2 = ord ($sub[1]) - 31; if ($ch2 < 0) $ch2 = 0;
    $ch3 = ord ($sub[2]) - 31; if ($ch3 < 0) $ch3 = 0;

    /*
      compute chunk sum
    */

    $sum = $ch1 + (225 * $ch2) + (225 * 225 * $ch3);

    /*
      make out encoded chunk by converting above sum to base 62,
      right-pad it with "0" characters until it's 4 characters,
      and append chunk to result
    */

    $result .= str_pad (convert_to_base62 ($sum), 4, "0");

    /*
      continue parsing the string to encode, advancing by how many characters
      were effectively part of this chunk (3 unless end of string came before)
    */

    $c += strlen ($sub);

  }

  /*
    return encoded string,
    trimming extra zeroes from its right side
  */

  return rtrim ($result, "0");

}

function decode ($string) {

  /*
    decodes $string from base 62:
    the input string is parsed in chunks of 4 characters each, that eventually
    could be incomplete chunks; if the chunk is incomplete, its missing chars
    on the right side are considered to have an ASCII code of zero; once there
    is this chunk as an upto-4-characters string, representing a large base 62
    number, it's passed to the function that converts it back to base 10, and
    the result is decomposed by powers of 225 (first character has exponent 0,
    second character has exponent 1, third character has exponent 2), and the
    resulting three numbers are increased by 31 and considered as ASCII codes;
    at this point, a 3-character chunk (the decoded chunk) is assembled basing
    on the three ASCII codes taken in sequence: a character is included in the
    decoded chunk only if its resulting code is greater than 31. Finally, all
    chunks are accumulated by $result to give the decoded string.
  */

  $result = ""; // accumulator
  $c = 0;       // character index in $string

  while (1) {

    /*
      stop upon reaching the end of $string
    */

    if ($string[$c] == "") {

      break;

    }

    /*
      get one 4-character chunk (a base 62 number) and convert it to base 10
    */

    $sub = substr ($string, $c, 4);
    $sum = convert_from_base62 ($sub);

    /*
      decompose sum by powers of 225, add 31 to the resulting three codes,
      and add each code as an ASCII character to $result, providing the code
      is greater than 31...
    */

    $ch1 = ($sum % 225) + 31; if ($ch1 > 31) $result .= chr ($ch1);
    $sum = (int) ($sum / 225);
    $ch2 = ($sum % 225) + 31; if ($ch2 > 31) $result .= chr ($ch2);
    $sum = (int) ($sum / 225);
    $ch3 = ($sum % 225) + 31; if ($ch3 > 31) $result .= chr ($ch3);

    /*
      continue parsing the string to decode, advancing by how many characters
      were effectively part of this chunk (4 unless end of string came before)
    */

    $c += strlen ($sub);

  }

  return $result;

}

/*
 *
 * get left ($l) and right ($r) frame sources (base62-encoded) from URL arguments:
 *
 * their order and number DOES matter, and by convention is left (?l=) before right (&r=),
 * and arguments must be only those two: this is because the convention simplifies duties
 * in "setlfcookie" and "setrfcookie", while updating the contents of the pagframe cookie.
 *
 */

function _stripslashes ($text) {

  return (get_magic_quotes_gpc ()) ? stripslashes ($text) : $text;

}

$l = substr (trim (_stripslashes ($_GET["l"])), 0, 1000);
$r = substr (trim (_stripslashes ($_GET["r"])), 0, 1000);

/*
 *
 * both the strings may be added an entry, either numeric or base62-encoded, giving an
 * exact key or index to be appended to the corresponding URI, such as a forum ID for
 * the threads.php script, a thread ID for the posts.php script, an encoded member name
 * for members.php and profile.php, etc; when this entry is given, it's separated from
 * the rest of the encoded URI by a single underscore, e.g. "toHhnBIeUEdhacS61M1_15" is
 * the encode of "forums.php?f=", followed by the unencoded numeric index 15, resulting
 * in "forums.php?f=15" being loaded in the right frame to list threads of forum #15;
 * in conjunction to the use of an appropriate "RewriteRule" among server-side redirects
 * this allows enabling "permalinks" to specific forums, threads, generic entries, where
 * following the link produces the full corresponding frameset
 *
 */

list ($l, $l_entry) = explode (chr (95), $l);

if (isset ($l_entry)) {

  if (preg_match ("/[^0-9a-z]i/", $l_entry)) {

    unset ($l_entry);

  }

}

list ($r, $r_entry) = explode (chr (95), $r);

if (isset ($r_entry)) {

  if (preg_match ("/[^0-9a-z]i/", $r_entry)) {

    unset ($r_entry);

  }

}

$l = (isset ($l_entry)) ? decode ($l) . $l_entry : decode ($l);
$r = (isset ($r_entry)) ? decode ($r) . $r_entry : decode ($r);

/*
 *
 * get pagframe cookie contents
 *
 */

$frame_cookie_hold = decode ($frame_cookie_hold);

/*
 *
 * below are also defaults for the frameset:
 * mstats on the left side and intro on the right side...
 *
 */

$l = ($l == "") ? decode (strfromto ($frame_cookie_hold, "?l=", "&")) : $l;
$l = ($l == "") ? "side.php?si=intro" : $l;

$r = ($r == "") ? decode (strfromto ($frame_cookie_hold, "&r=", "&")) : $r;
$r = ($r == "") ? "$path_to_postline/intro.php" : $r;

/*
 *
 * check validity of URIs:
 *
 * URIs may or may not contain the legitimate path to this Postline installation,
 * relatively to the host server's document root, but they can only contain that
 * path, or they will be invalid; after the eventual presence of the said path,
 * the name of the PHP script to load must strictly match one of those given in
 * the two arrays ($l_frames and $r_frames) declared in "settings.php", or they
 * will be again invalid. It is ABSOLUTELY NO GOOD to let this page load anything
 * different from what's intended. It may otherwise try to load URIs holding some
 * dangerous "javascript:" protocol, for example, for XSS attack purposes, and/or
 * login panels from some *remote* installation of Postline (phishing).
 *
 */

$legit_path = "$path_to_postline/";
$pathlength = strlen ($legit_path);

$l = (substr ($l, 0, $pathlength) == $legit_path) ? substr ($l, $pathlength) : $l;
$r = (substr ($r, 0, $pathlength) == $legit_path) ? substr ($r, $pathlength) : $r;

list ($l_frame) = explode ("#", $l);
list ($r_frame) = explode ("#", $r);
list ($l_frame) = explode ("?", $l_frame);
list ($r_frame) = explode ("?", $r_frame);

$l = (in_array ($l_frame, $l_frames)) ? $l : "about:blank";
$r = (in_array ($r_frame, $r_frames)) ? $r : "about:blank";

/*
 *
 * get preferences' cookie flags (only the parts concerning the frameset's configuration)...
 *
 */

if (($prefs_cookie_hold[6] == "A") && ($prefs_cookie_hold[6 + 1 + 44] == $prefscfgversion)) {

  $kflags = substr ($prefs_cookie_hold, 6 + 1, 44);

}
else {

  $kflags = "nnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnnn";

}

$mlfr = ($kflags[1] == "y") ? "yes" : "";       // make left frame resizeable
$aclf = ($kflags[2] == "y") ? "yes" : "";       // always collapse left frame

/*
 *
 * analyzing preferences for left frame
 *
 */

$cols = $leftframewidth;

if (($mlfr == "yes") || ($aclf == "yes")) {

  $cols = ($aclf == "yes") ? 0 : $cols;

  $nr = "";
  $fb = ($on_frfox) ? 1 : 0;
  $fs = 4;

}
else {

  $nr = chr (32) . "noresize";
  $fb = 0;
  $fs = 0;

}

/*
 *
 * saving central frameset recovery informations (both central frames)
 *
 */

$frame_cookie_hold = "{$path_to_postline}/index.php?l=" . encode ($l) . "&r=" . encode ($r);
$frame_cookie_hold = $frame_cookie_text . encode ($frame_cookie_hold);

if ($_COOKIE[$frame_cookie_name] != $frame_cookie_hold) {

  setcookie ($frame_cookie_name, $frame_cookie_hold, 2147483647, "/");

}

/*
 *
 * specific page output (no template, this is just a frameset):
 *
 * several proprietary attributes have been set to appear as clean as possible on four
 * browsers, namely: Mozilla 1.2+, Firefox, Opera 7.54, Internet Explorer 5.5 and 6,
 * yeah, I had to do several attempts here...
 *
 */

echo (

  "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\">"
. "<html>"
. "<head>"
.  "<title>"
.   "Postline community @ $sitename"
.  "</title>"
. "</head>"
. "<frameset cols=$cols,* rows=* frameborder=$fb framespacing=$fs>"
.  "<frame name=pst scrolling=auto src=$l frameborder=$fb marginwidth=0 marginheight=0$nr>"
.  "<frame name=pan scrolling=auto src=$r frameborder=$fb marginwidth=0 marginheight=0$nr>"
. "</frameset>"
. "</html>"

);



?>
