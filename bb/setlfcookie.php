<?php

/*************************************************************************************************

            HSP Postline -- status line rendering

            PHP bulletin board and community engine
            version 2010.02.10 (10.2.10)

                Copyright (C) 2003-2010 by Alessandro Ghignola
                Copyright (C) 2003-2010 Home Sweet Pixel software

            This program is free software; you can redistribute it and/or modify it under the
            terms of the GNU General Public License as published by the Free Software Foundation;
            either version 2 of the License, or (at your option) any later version.

            This program is distributed in the hope that it will be useful, but WITHOUT ANY
            WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
            PARTICULAR PURPOSE. See the GNU General Public License for more details.

            You should have received a copy of the GNU General Public License
            along with this program; if not, write to the Free Software Foundation, Inc.,
            51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA

*************************************************************************************************/

/*
 *
 *      IMPORTANT!
 *
 *      avoid launching this and other similary dependand scripts alone,
 *      to prevent potential malfunctions and unexpected security flaws:
 *      the starting point of launchable scripts is defining 'going'
 *
 */

$proceed = (defined ('going')) ? true : die ('[ postline left-side frame cookie URI update ]');

/*
 *
 *      Postline includes
 *
 */

require ('settings.php');

/*
 *
 *      widget includes
 *
 */

require ('widgets/overall/primer.php');
require ('widgets/base62.php');
require ('widgets/strings.php');

/*
 *
 *      this code fragment replaces the content of the 'frame' cookie concerning the URI of the
 *      page to be loaded in the left-side frame: it's to be included by Postline scripts which
 *      need to change the left-side frame's record in the cookie to reflect the caller's URI
 *
 */

$prevx_cookie_hold = $frame_cookie_hold;
$given_request_uri = base62Encode ($_SERVER['REQUEST_URI']);
$frame_cookie_hold = base62Decode ($frame_cookie_hold);
$frame_cookie_hold = strReplaceFromTo ($frame_cookie_hold, '?l=', '&', '?l=' . $given_request_uri . '&');
$frame_cookie_hold = base62Encode ($frame_cookie_hold);
$frame_cookie_hold = $frame_cookie_text . $frame_cookie_hold;

$make_change = ($prevx_cookie_hold == $frame_cookie_hold) ? false : true;
$make_change = ($prevx_cookie_hold == voidString) ? false : $make_change;

if ($make_change) {

        setcookie ($frame_cookie_name, $frame_cookie_hold, 2147483647, '/');

}

?>
