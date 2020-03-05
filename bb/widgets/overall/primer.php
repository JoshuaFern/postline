<?php $productName = "postline"; if (!defined ("$productName/widgets/overall/primer.php")) {

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

error_reporting (E_ALL ^ E_NOTICE);

/*
 *
 *      unsetting shared but non-completely-initialized global variables,
 *      possibly to prevent overridings
 *
 */

$em = array ();                 // global array of error messages (errors.php)
$ex = array ();                 // global array of error message explanations (errors.php)
$errorHandlers = array ();      // global array of error handlers for 'because' (errors.php)
$onSessionLock = array ();      // global array of event handlers for 'lockSession' (locker.php)
$onSessionUnlock = array ();    // global array of event handlers for 'unlockSession' (locker.php)

/*
 *
 *      defining some common defines - :)
 *
 */

define ('voidString', '');      // I don't like writing '' a lot, it always looks weird
define ('blank', chr (32));     // and ' ' is even worse, chr (32) being... complicated

/*
 *
 *      PHP function wrappers correcting a few problems with PHP functions behavior,
 *      or working as generic commodities:
 *
 *      sometimes I've not been 100% sure about the precence of these problems in certain
 *      array functions, perhaps they were bugs causing random misfortunes, and I presume
 *      they don't affect all versions of PHP anyway; I'm keeping them to be sure
 *
 *      in other cases (e.g. wExplode, wImplode) a wrapper introduces slightly different
 *      behavior for the native function it replaces - be aware the result is different!
 *
 */

function wStripslashes ($argument) {

        $bogusCall = (is_string ($argument)) ? false : true;

        /*
         *
         *      strips escaping slashes only if $argument is a string and "magic quotes" are on,
         *      i.e. only if they might have been added to $_GET or $_POST arguments; it may be
         *      made uninfluent once these messy interferences will be completely deprecated
         *
         */

        if ($bogusCall) {

                return ($argument);

        }

        $doStrip = (get_magic_quotes_gpc ()) ? true : false;
        $doStrip = (get_magic_quotes_runtime ()) ? true : $doStrip;

        return (($doStrip == true) ? stripslashes ($argument) : $argument);

}

function wFilesize ($file) {

        /*
         *
         *      corrects the behavior of 'filesize', which seems not to realize changes
         *      to file sizes within the same session correctly (doesn't seem to update
         *      the size in the stats cache, which I'd expect it to do, at least in the
         *      same PHP script run); but I expect filesize to always return the file's
         *      actual size, i.e. to "count" the bytes for me, promptly and on the spot
         *
         */

        clearstatcache ();

        return (@filesize ($file));

}

function wExEmpty ($array) {

        $bogusCall = (is_array ($array)) ? false : true;

        /*
         *
         *      excludes any empty strings from $array and returns a "clean" array, deprived
         *      of such empty string values; used to clean up apparent cases of arrays where
         *      empty strings were left by functions 'array_diff' and 'array_unique', but it
         *      was happening long time ago: if there is a particular case in which this has
         *      to happen, I'm not sure what triggers it; perhaps it was normal and I simply
         *      didn't get how the functions were supposed to work under given circumstances...
         *
         */

        if ($bogusCall) {

                return (array ());

        }

        $result = array ();

        foreach ($array as $a) {

                if ($a !== voidString) {

                        $result[] = $a;

                }

        }

        return ($result);

}

function wExplode ($separator, $list) {

        $bogusCall = (is_string ($separator)) ? false : true;
        $bogusCall = (is_string ($list)) ? $bogusCall : true;

        /*
         *
         *      does the same as 'explode', but returns a void array if $list is an empty string:
         *      the usual 'explode' would otherwise return an array with one element being a void
         *      string in case an empty string was exploded by any separator
         *
         */

        if ($bogusCall) {

                return (array ());

        }

        return (($list === voidString) ? array () : wExEmpty (explode ($separator, $list)));

}

function wArrayDiff ($array1, $array2) {

        $bogusCall = (is_array ($array1)) ? false : true;
        $bogusCall = (is_array ($array2)) ? $bogusCall : true;

        /*
         *
         *      wArrayDiff does the same as 'array_diff', but then excludes any empty values
         *      that may have remained into the source array after array_diff is performed...
         *
         */

        if ($bogusCall) {

                return (array ());

        }

        return (wExEmpty (array_diff ($array1, $array2)));

}

function wArrayUnique ($array) {

        $bogusCall = (is_array ($array)) ? false : true;

        /*
         *
         *      wArrayUnique does the same as 'array_unique', but then excludes any empty values
         *      that may have remained into the source array after array_unique is performed...
         *
         */

        if ($bogusCall) {

                return (array ());

        }

        return (wExEmpty (array_unique ($array)));

}

define ("$productName/widgets/overall/primer.php", true); } ?>
