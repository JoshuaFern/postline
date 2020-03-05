<?php require ('widgets/overall/primer.php'); if (!defined ("$productName/widgets/strings.php")) {

/*************************************************************************************************

            Copyright (C) 2009 by Alessandro Ghignola

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
 *      generic string manipulation facility
 *
 */

function ipEncode ($ipAddress) {

        /*
         *
         *      encodes an IPv4 address in dot-quadded form (W.X.Y.Z) to a big-endian hexadecimal
         *      string of 8 digits (WWXXYYZZ), to facilitate management of the IP address' nybbles
         *      and bytes: if address doesn't look like a dot-quadded IPv4, returns it untranslated
         *
         */

        $by = explode ('.', $ipAddress);
        $ip = (count ($by) == 4)

                ? sprintf ('%02x%02x%02x%02x', $by[0], $by[1], $by[2], $by[3])
                : $ipAddress;

        return ($ip);

}

function strBefore ($string, $upto) {

        $bogusCall = (is_string ($string)) ? false : true;
        $bogusCall = (is_string ($upto)) ? $bogusCall : true;

        /*
         *
         *      returns the part of $string laying before its substring $upto,
         *      e.g. strBefore ('localhost/page?arguments', '?') would return 'localhost/page'
         *
         *            - if either argument is no string, or is a void string, returns a void string
         *            - if $upto does not occur in $string, also returns a void string
         *            - if $upto occurs twice or more, returns the part before its FIRST occurrence
         *
         */

        if ($bogusCall) {

                return (voidString);

        }

        $i = strpos ($string, $upto);

        if ($i === false) {

                return (voidString);

        }

        return (substr ($string, 0, $i));

}

function strAfter ($string, $from) {

        $bogusCall = (is_string ($string)) ? false : true;
        $bogusCall = (is_string ($from)) ? $bogusCall : true;

        /*
         *
         *      returns the part of $string laying after its substring $from,
         *      e.g. strAfter ('localhost/page?arguments#anchor', '#') would return 'anchor'
         *
         *            - if either argument is no string, or is a void string, returns a void string
         *            - if $from does not occur in $string, also returns a void string
         *            - if $from occurs twice or more, returns the part after its LAST occurrence
         *
         */

        if ($bogusCall) {

                return (voidString);

        }

        $i = strpos (strrev ($string), strrev ($from));

        if ($i === false) {

                return (voidString);

        }

        return (substr ($string, strlen ($string) - $i));

}

function strBetween ($string, $from, $upto) {

        $bogusCall = (is_string ($string)) ? false : true;
        $bogusCall = (is_string ($from)) ? $bogusCall : true;
        $bogusCall = (is_string ($upto)) ? $bogusCall : true;

        /*
         *
         *      returns the part of $string laying between substring $from and substring $upto,
         *      e.g. strBetween ('anywhere i am', 'anywhere', 'am') would return ' i '
         *
         *            - returns a void string if
         *
         *                    - either argument is no string or is a void string
         *                    - $from follows $upto in $string
         *                    - there is nothing between $from and $upto
         *                    - either $from or $upto do not occur in $string
         *
         *            - otherwise,
         *
         *                    - first occurrence of $from delimits the beginning of the result,
         *                    - last occurrence of $upto delimits the end of the result
         *
         *      e.g. strBetween ('anywhere i am, i am', 'anywhere', 'am') would return ' i am, i '
         *
         */

        if ($bogusCall) {

                return (voidString);

        }

        $i = strpos ($string, $from);
        $j = strpos (strrev ($string), strrev ($upto));

        if (($i === false) || ($j === false)) {

                return (voidString);

        }

        $i += strlen ($from);
        $j  = strlen ($string) - $j - strlen ($upto);

        if ($i >= $j) {

                return (voidString);

        }

        return (substr ($string, $i, $j - $i));

}

function strReplaceBetween ($string, $from, $upto, $replacement) {

        $bogusCall = (is_string ($string)) ? false : true;
        $bogusCall = (is_string ($from)) ? $bogusCall : true;
        $bogusCall = (is_string ($upto)) ? $bogusCall : true;
        $bogusCall = (is_string ($replacement)) ? $bogusCall : true;

        /*
         *
         *      extends the purposes of the above function and replaces the part of $string that
         *      lays between its substring $from and its substring $upto with given $replacement
         *      string, matching $from and $upto in the same way strBetween does, and INCLUDING
         *      the two substrings in the section to be replaced by the given $replacement string,
         *      e.g. strReplaceBetween ('domain/page?arguments#anchor', '?', '#', '?myarguments#')
         *      would return 'domain/page?myarguments#anchor'
         *
         *            - returns $string entirely unaltered (and does not replace anything) if
         *
         *                    - either $string, $from or $upto is no string or is a void string
         *                    - either argument is no string (including $replacement)
         *                    - $from follows $upto in $string
         *                    - either $from or $upto do not occur in $string
         *
         *            - about the substrings used as delimiters being INCLUDED in the section of
         *              the input $string to be replaced with the $replacement string, to clarify:
         *
         *                      strReplaceFromTo ('localhost/page?arguments', '/', '?', 'HELLO')
         *
         *              returns the string 'localhostHELLOarguments' replacing '/' and '?' as well,
         *              which could be intuitively unexpected, but which, for example, conveniently
         *              allows removing delimiters of constructs if the circumstances require that,
         *              while still allowing to insert delimiters back if a construct is maintained
         *
         */

        if ($bogusCall) {

                return ($string);

        }

        $i = strpos ($string, $from);
        $j = strpos (strrev ($string), strrev ($upto));

        if (($i === false) || ($j === false)) {

                return ($string);

        }

        $j = strlen ($string) - $j;

        if ($i > $j) {

                return ($string);

        }

        return (substr ($string, 0, $i) . $replacement . substr ($string, $j));

}

function strFromTo ($string, $from, $upto) {

        $bogusCall = (is_string ($string)) ? false : true;
        $bogusCall = (is_string ($from)) ? $bogusCall : true;
        $bogusCall = (is_string ($upto)) ? $bogusCall : true;

        /*
         *
         *      returns the part of $string laying between substring $from and substring $upto,
         *      e.g. strFromTo ('anywhere i am', 'anywhere', 'am') would return ' i '
         *
         *            - returns a void string if
         *
         *                    - either argument is no string or is a void string
         *                    - $from does not occur in $string
         *                    - $upto does not occur in $string past the substring $from
         *                    - there is nothing between $from and $upto
         *
         *            - otherwise,
         *
         *                    - first occurrence of $from delimits the beginning of the result,
         *                    - first SUBSEQUENT occurrence of $upto delimits the end of the result
         *
         *      e.g. strFromTo ('anywhere i am, i am', 'anywhere', 'am')
         *
         *            - would STILL return ' i '
         *            - would NOT return ' i am, i '
         *
         *      i.e. this is the 'ungreedy' counterpart of strBetween, and runs only left-to-right
         *
         */

        if ($bogusCall) {

                return (voidString);

        }

        $i = strpos ($string, $from);

        if ($i === false) {

                return (voidString);

        }

        $j = strpos ($string, $upto, $i + strlen ($from));

        if ($j === false) {

                return (voidString);

        }

        $i += strlen ($from);

        if ($i >= $j) {

                return (voidString);

        }

        return (substr ($string, $i, $j - $i));

}

function strReplaceFromTo ($string, $from, $upto, $replacement) {

        $bogusCall = (is_string ($string)) ? false : true;
        $bogusCall = (is_string ($from)) ? $bogusCall : true;
        $bogusCall = (is_string ($upto)) ? $bogusCall : true;
        $bogusCall = (is_string ($replacement)) ? $bogusCall : true;

        /*
         *
         *      extends the purposes of the above function and replaces the part of $string that
         *      lays between its substring $from and its substring $upto with given $replacement
         *      string, matching $from and $upto strictly in left-to-right order, and INCLUDING
         *      the two substrings in the section to be replaced by the given $replacement string,
         *      e.g. strReplaceFromTo ('anywhere i am', ' ', ' ', ' you ') gives 'anywhere you am'
         *
         *            - returns $string entirely unaltered (and does not replace anything) if
         *
         *                    - either $string, $from or $upto is no string or is a void string
         *                    - either argument is no string (including $replacement)
         *                    - $from does not occur in $string
         *                    - $upto does not occur in $string past the substring $from
         *
         *            - about the substrings used as delimiters being INCLUDED in the section of
         *              the input $string to be replaced with the $replacement string, to clarify:
         *
         *                      strReplaceFromTo ('localhost/page?arguments', '/', '?', 'HELLO')
         *
         *              returns the string 'localhostHELLOarguments' replacing '/' and '?' as well,
         *              which could be intuitively unexpected, but which, for example, conveniently
         *              allows removing delimiters of constructs if the circumstances require that,
         *              while still allowing to insert delimiters back if a construct is maintained
         *
         */

        if ($bogusCall) {

                return ($string);

        }

        $i = strpos ($string, $from);

        if ($i === false) {

                return ($string);

        }

        $j = strpos ($string, $upto, $i + strlen ($from));

        if ($j === false) {

                return ($string);

        }

        $j += strlen ($upto);

        return (substr ($string, 0, $i) . $replacement . substr ($string, $j));

}

function breakUnspaced ($string, $max, $breakingString) {

        $bogusCall = (is_string ($string)) ? false : true;
        $bogusCall = (is_int ($max)) ? $bogusCall : true;
        $bogusCall = (is_string ($breakingString)) ? $bogusCall : true;

        /*
         *
         *      breaks a string's eventually-too-long unspaced fragments so that such fragments can
         *      be no longer than $max characters (forcing blank spaces where necessary), e.g.
         *
         *              breakUnspaced ('what\'s a synchrocyclotron?', 15, ' ')
         *
         *      returns "what's a synchrocyclotro n?" since that word is longer than 15 characters,
         *      therefore constituting a long-enough fragment of text that is not "spaced" in any
         *      points; this function is useful to fit text into parts of the screen that would not
         *      tolerate more than a certain amount of characters to be visualized without forceful
         *      wrapping effects or exceeding an intended width; PHP's 'wordwrap' function does the
         *      same but may split HTML entities (e.g. '&bullet;'), while this function is intended
         *      to process HTML text eventually including entities, being treaten as one character:
         *      beware that it needs well-formed HTML entities to acknowledge them; a browser often
         *      recognizes one of those even if no semicolon terminates it, but that would not work
         *      for this function!
         *
         *            - returns $string entirely unaltered (in any cases) if
         *
         *                    - either $string or $breakingString is no string or is a void string
         *                    - argument $max is not integer, or is negative, or is zero
         *
         */

        if ($bogusCall) {

                return ($string);

        }

        if ($max <= 0) {

                /*
                 *
                 *      anti-loop check:
                 *      the function would hang if executed with $max being null or negative
                 *
                 */

                return ($string);

        }

        /*
         *
         *      initialize accumator string $a,
         *      define the regexp matching HTML entities
         *
         */

        $a = voidString;
        $x = '/\&\#?[A-Za-z0-9]+?\;/';

        /*
         *
         *      count HTML entities (eg. "&#59;", "&amp;") that must count as a single character of
         *      text, and that cannot be split by forcing spaces in their middle: for this purpose,
         *      they are matched by a regular expression and saved into array $t; then they are all
         *      replaced by a marking character (\0, which is implicitly supposed NOT to originally
         *      occur in $string); after the string has been analyzed and breakingString eventually
         *      forced where appropriate, all entities saved into $t will be inserted back in their
         *      original positions and in place of the said \0 markers
         *
         */

        preg_match_all ($x, $string, $t);
        $string = preg_replace ($x, "\0", $string);

        /*
         *
         *      start analyzing the string to find all unspaced sequences exceeding $max
         *
         */

        while (1) {

                /*
                 *
                 *      locate first/next explicit whitespace character in $string
                 *
                 */

                $p = preg_match ('/\s/', $string, $match, PREG_OFFSET_CAPTURE);
                $p = ($p) ? $match[0][1] : false;

                if (($p === false) || ($p > $max)) {

                        /*
                         *
                         *      if no space could be found, or if the position of the first space
                         *      found relatively to the beginning of $string exceeds the value of
                         *      $max, begin splitting the string by:
                         *
                         *    - accumulating substring $fragment, of upto $max characters, into $a;
                         *    - appending $breaking_string to $a after $fragment (force in result);
                         *    - cutting $fragment from beginning of $string, to prepare $string for
                         *      the next iteration
                         *
                         */

                        $a .= substr ($string, 0, $max);

                        if (strlen ($string) > $max) {

                                /*
                                 *
                                 *      postscript:
                                 *
                                 *      the breaking string gets appended only when what remains of
                                 *      $string after the last blank space was found is effectively
                                 *      longer than $max, otherwise, at the end of $string it would
                                 *      always end up in a $p === false condition, which produces a
                                 *      result which would always have a copy of $breakingString at
                                 *      its end
                                 *
                                 */

                                $a .= $breakingString;

                        }

                        $string = substr ($string, $max);

                }

                else {

                        /*
                         *
                         *      if space has been found before $max characters since the beginning
                         *      of $string, just accumulate the substring coming before the space,
                         *      and including the space, onto accumulator string $a, then cut that
                         *      same substring from the beginning of $string, preparing $string for
                         *      the next iteration
                         *
                         */

                        $a .= substr ($string, 0, $p + 1);
                        $string = substr ($string, $p + 1);

                }

                /*
                 *
                 *      given that in any cases $string has been cut from its beginning in whole or
                 *      in part, if there's nothing left, past the latest found fragment, break
                 *
                 */

                if ($string == voidString) {

                        break;

                }

        }

        /*
         *
         *      put back HTML numeric entities where they were, replacing the \0 markers,
         *      and finally return the resulting string...
         *
         */

        foreach ($t[0] as $h) {

                $f1 = substr ($a, 0, strpos ($a, "\0"));
                $f2 = substr ($a, strlen ($f1) + 1);

                $a = $f1 . $h . $f2;

        }

        return ($a);

}

define ("$productName/widgets/strings.php", true); } ?>
