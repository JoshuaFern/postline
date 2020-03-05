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

require ('settings.php');
require ('suitcase.php');
require ('sessions.php');

/*
 *
 *      error code to track:
 *      make sure it's a legit max-3-digit number -- we're outputting an HTML file with that name!
 *
 */

$errorCode = fget ('code', 3, voidString);
$errorCode = strval (intval ($errorCode));
$validCode = file_exists ("../errors/{$errorCode}.html") ? true : false;

if ($validCode == false) {

        unlockSession ();
        die ('[ postline error tracking script ]');

} // unsupported error code

/*
 *
 *      errors tracking
 *
 */

$requestURI = base64_encode ($_SERVER['REQUEST_URI']);
$ignoreThis = get ("errors/{$errorCode}-ignorelist", "request>{$requestURI}", wholeRecord);

if (empty ($ignoreThis)) {

        $referrer = base64_encode ($_SERVER['HTTP_REFERER']);
        $method = base64_encode ($_SERVER['REQUEST_METHOD']);
        $userAgent = base64_encode ($_SERVER['HTTP_USER_AGENT']);

        $records = all ("errors/{$errorCode}", makeProper);
        $records = array_slice ($records, -$errorspercode, $errorspercode);

        $records[]

              = "<time>{$epc}"
              . "<client>{$ip}"
              . "<request>{$requestURI}"
              . "<referrer>{$referrer}"
              . "<method>{$method}"
              . "<useragent>{$userAgent}"
              . (($login == true) ? "<nick>{$nick}" : voidString);

        lockSession ();
        asm ("errors/{$errorCode}", $records, makeProper);

} // referred URI was not in the ignorelist (and error has been tracked)

/*
 *
 *      releasing locked session frame, page output
 *
 */

echo (pquit (file_get_contents ("../errors/{$errorCode}.html")));

?>
