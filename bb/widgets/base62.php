<?php require ('widgets/overall/primer.php'); if (!defined ("$productName/widgets/base62.php")) {

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
 *      base-62 strings encoding (this is my own scaled-down version of base-64)
 *
 *      in theory, any characters between 32 and 255 are successfully encoded by "encode" and
 *      the resulting base-62 alphanumeric string is decoded by "decode"; what they are thought
 *      for is passing string parameters throught "get" arguments of an hyperlink, and more
 *      specifically, they're used for instance for passing membernames to the "profile" script;
 *      in this way, text strings containing particular characters used as markers in URLs, are
 *      always passed transparently. Yet, the strings must not contain text control codes below
 *      ASCII code 32 (such as line breaks having code 13, tabulations having code 9, etc...),
 *      not that it wouldn't be impossible to encode them, but the links would take more space
 *      because a group of 4 digits in base 62 would not have enough numeric range to hold the
 *      sums of groups of 3 ASCII letters, if the ASCII codes weren't previously reduced by 31
 *      units, 31 being the code terminating an encoded block of text
 *
 *      warning:
 *      it's rather being kept clear than optimized, encoding large strings would be slow and
 *      shouldn't be the aim of these functions; they're supposed to be used for snippets only
 *
 */

function convertToBase62 ($number) {

        $bogusCall = (is_int ($number)) ? false : true;

        /*
         *
         *      converts $number from base 10 to base 62:
         *
         *      digits 10 to 35 are represented by lowercase letters,
         *      digits 36 to 61 are represented by uppercase letters
         *
         */

        if ($bogusCall) {

                return (voidString);

        }

        $result = voidString;

        do {

                $d = $number % 62;
                $number = (int) ($number / 62);

                if ($d < 10) {

                        $result .= chr ($d + 48);

                }

                else {

                        $result .= $d < 36 ? chr ($d - 10 + 97) : chr ($d - 36 + 65);

                }

        } while ($number);

        return ($result);

}

function convertFromBase62 ($number) {

        $bogusCall = (is_string ($number)) ? false : true;

        /*
         *
         *      converts $number from base 62 to base 10:
         *
         *      $number is in reality given to this function as an alphanumeric string,
         *      but the function still outputs an integer value
         *
         */

        if ($bogusCall) {

                return (0);

        }

        $k = 1;         // multiplier, will increase in powers of 62, starting at 62^0
        $result = 0;    // result accumulator (integer, numeric)

        $c = 0;                 // string analysis loop counter (0 = first character)
        $n = strlen ($number);  // when $c gets to $n, the input string is over

        while ($c < $n) {

                $d = ord ($number[$c]);

                if (($d >= 48) && ($d <= 57)) {

                        $result += $k * ($d - 48);              // simple digit

                }

                else
                if (($d >= 97) && ($d <= 122)) {

                        $result += $k * ($d - 97 + 10);         // lowercase letter

                }

                else
                if (($d >= 65) && ($d <= 90)) {

                        $result += $k * ($d - 65 + 36);         // uppercase letter

                }

                $k *= 62;

                ++ $c;

        }

        return ($result);

}

function base62Encode ($string) {

        $bogusCall = (is_string ($string)) ? false : true;

        /*
         *
         *      encodes $string to base62:
         *
         *      the function divides the input string in chunks of upto 3 characters each,
         *      padding an eventually incomplete chunk with zeroes, and then assembling a
         *      single large integer number for each chunk; assuming the three characters
         *      of a chunk were ASCII codes x,y,z, conversion is based on the formula:
         *
         *      c = x + 225*y + (225^2)*z
         *
         *      after which, c (the said large integer number) is converted to base 62 by
         *      the corresponding function declared above; the resulting base62 string is
         *      finally forced to be 4 characters long by right-padding it, if necessary,
         *      with zeroes (being strings, I mean the character "0", or ASCII #48), then
         *      it's added to $result, which accumulates those 4-character substrings and
         *      in the end forms the output (encoded) string returned by this function.
         *      Note that final trailing "0" characters are removed to shorten the result.
         *
         */

        if ($bogusCall) {

                return (voidString);

        }

        $result = voidString;   // accumulator
        $c = 0;                 // character index in $string

        while (1) {

                /*
                 *
                 *      stop upon reaching the end of $string
                 *
                 */

                if ($string[$c] == voidString) {

                        break;

                }

                /*
                 *
                 *      get 3-characters chunk: this chunk may be shorter than 3 characters, but
                 *      it wouldn't be a problem because sebsequent code will normalize any void
                 *      character (ASCII #0) to a null value...
                 *
                 */

                $sub = substr ($string, $c, 3);

                /*
                 *
                 *      encode each character as a number given by its ASCII code, minus 31;
                 *      if one of the characters has ASCII < 31, encode it as zero...
                 *
                 */

                $ch1 = ord ($sub[0]) - 31;
                $ch2 = ord ($sub[1]) - 31;
                $ch3 = ord ($sub[2]) - 31;

                $ch1 = $ch1 < 0 ? 0 : $ch1;
                $ch2 = $ch2 < 0 ? 0 : $ch2;
                $ch3 = $ch3 < 0 ? 0 : $ch3;

                /*
                 *
                 *      compute chunk sum
                 *
                 */

                $sum = $ch1 + (225 * $ch2) + (225 * 225 * $ch3);

                /*
                 *
                 *      make out encoded chunk by converting above sum to base 62,
                 *      right-pad it with "0" characters until it's 4 characters,
                 *      and append chunk to result
                 *
                 */

                $result .= str_pad (convertToBase62 ($sum), 4, '0');

                /*
                 *
                 *      continue parsing the string to encode, advancing by how many characters
                 *      were effectively part of this chunk (3 unless end of string came before)
                 *
                 */

                $c += strlen ($sub);

        }

        /*
         *
         *      return encoded string,
         *      trimming extra zeroes from its right side
         *
         */

        return (rtrim ($result, '0'));

}

function base62Decode ($string) {

        $bogusCall = (is_string ($string)) ? false : true;

        /*
         *
         *      decodes $string from base 62:
         *
         *      the input string is parsed in chunks of 4 characters each, that eventually
         *      could be incomplete chunks; if the chunk is incomplete, its missing chars
         *      on the right side are considered to have an ASCII code of zero; once there
         *      is this chunk as an upto-4-characters string, representing a large base 62
         *      number, it's passed to the function that converts it back to base 10, and
         *      the result is decomposed by powers of 225 (first character has exponent 0,
         *      second character has exponent 1, third character has exponent 2), and the
         *      resulting three numbers are increased by 31 and considered as ASCII codes;
         *      at this point, a 3-character chunk (the decoded chunk) is assembled basing
         *      on the three ASCII codes taken in sequence: a character is included in the
         *      decoded chunk only if its resulting code is greater than 31. Finally, all
         *      chunks are accumulated by $result to give the decoded string.
         *
         */

        if ($bogusCall) {

                return (voidString);

        }

        $result = voidString;   // accumulator
        $c = 0;                 // character index in $string

        while (1) {

                /*
                 *
                 *      stop upon reaching the end of $string
                 *
                 */

                if ($string[$c] == voidString) {

                        break;

                }

                /*
                 *
                 *      get one 4-character chunk (a base 62 number) and convert it to base 10
                 *
                 */

                $sub = substr ($string, $c, 4);
                $sum = convertFromBase62 ($sub);

                /*
                 *
                 *      decompose 'sum' by powers of 225, add 31 to the resulting three codes,
                 *      and add each code as an ASCII character to $result, providing the code
                 *      is greater than 31 (any such occurrences become void strings: prevents
                 *      using this function to generate any formatters other than an effective
                 *      blank space of code #32, as they could be dangerous or unexpected)
                 *
                 */

                $ch1 = ($sum % 225) + 31;
                $sum = (int) ($sum / 225);
                $ch2 = ($sum % 225) + 31;
                $sum = (int) ($sum / 225);
                $ch3 = ($sum % 225) + 31;

                $result .= $ch1 > 31 ? chr ($ch1) : voidString;
                $result .= $ch2 > 31 ? chr ($ch2) : voidString;
                $result .= $ch3 > 31 ? chr ($ch3) : voidString;

                /*
                 *
                 *      continue parsing the string to decode, advancing by how many characters
                 *      were effectively part of this chunk (4 unless end of string came before)
                 *
                 */

                $c += strlen ($sub);

        }

        return ($result);

}

define ("$productName/widgets/base62.php", true); } ?>
