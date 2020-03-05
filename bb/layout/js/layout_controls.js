/*
 *
 *      HSP Postline
 *
 *      frameset control
 *      version 2010.02.10 (10.2.10)
 *
 *                      Copyright (C) 2003-2010 by Alessandro Ghignola
 *                      Copyright (C) 2003-2010 Home Sweet Pixel software
 *
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 *
 */

/*
 *
 *      make this reflect the name given in 'settings.php':
 *      its length determines the offsets where yes/no flags are searched in preferences' cookie
 *
 */

var siteName = 'AnyNowhere';

/*
 *
 *      decoding vertical (rows) frame preferences:
 *      is chat box set to full or half-size? is navigation bar compact? is status line compact?
 *
 */

var cookies = document.cookie.split (';');
var halveChatBox = 'n';
var smallTop = 'n';
var smallStatus = 'n';

for (i in cookies) {

        parts = cookies[i].split ('=');
        if (parts[1]) {

                cookieName = parts[0];
                cookieContent = parts[1];
                found = /postline\_prefs$/.test (cookieName);

                if (found) {

                        halveChatBox = cookieContent[76 + siteName.length];
                        smallTop = cookieContent[79 + siteName.length];
                        smallStatus = cookieContent[80 + siteName.length];

                }

        }

}

var cbRows = (halveChatBox == 'y') ? '48' : '96';
var tnRows = (smallTop == 'y') ? '23' : '47';
var dlRows = (smallStatus == 'y') ? '24' : '48';

/*
 *
 *      tied to the onLoad event of 'layout/frameset.html',
 *
 *      alters the 'cols' property of the frameset to resize side navigation bars and extend the
 *      frameset to the entire browser window, altering frame contents according to preferences,
 *      as of the '[...]_postline_prefs' cookie
 *
 *            - relPath might be void if 'frameset.js' is included from files in 'html/layout',
 *              or be set to '../' in case it's included from files in 'html/layout/doors'
 *
 */

function applyFramesetPreferences (relPath) {

        var cfW, sfCols, cfCols, snCols, nwCols;

        var extendFrameset = 'n';
        var smallSides = 'n';
        var smallTop = 'n';
        var cookies = document.cookie.split (';');

        for (i in cookies) {

                parts = cookies[i].split ('=');
                if (parts[1]) {

                        cookieName = parts[0];
                        cookieContent = parts[1];
                        found = /postline\_prefs$/.test (cookieName);

                        if (found) {

                                if (cookieContent != 'default') {

                                        extendFrameset = cookieContent[75 + siteName.length];
                                        smallSides = cookieContent[78 + siteName.length];
                                        smallTop = cookieContent[79 + siteName.length];

                                }

                        }

                }

        }

        if (typeof (window.innerWidth) == 'number') {

                cfW = window.innerWidth;

        }

        else {

                if (document.documentElement && document.documentElement.clientWidth) {

                        cfW = document.documentElement.clientWidth;

                }

                else {

                        if (document.body && document.body.clientWidth) {

                                cfW = document.body.clientWidth;

                        }

                }

        }

        if (smallSides == 'n') {

                //cfCols = (cfW > 966) ? '912' : '745';
                cfCols = (cfW > 966) ? ((cfW > 1222) ? ((cfW > 1542) ? '1488' : '1168') : '912') : '745';

        }

        else {

                //cfCols = (cfW > 966) ? '935' : '768';
                cfCols = (cfW > 966) ? ((cfW > 1222) ? ((cfW > 1542) ? '1511' : '1191') : '935') : '768';

        }

        //sfCols = (extendFrameset == 'y') ? '0' : '*'; // keep out sky bars if extended
        sfCols = (extendFrameset == 'y') ? '32' : '*';  // make persistent sky bars

        cfCols = (extendFrameset == 'y') ? '*' : cfCols;
        snCols = (smallSides == 'y') ? '32' : '55';
        nwCols = sfCols + ',' + snCols + ',' + cfCols + ',' + sfCols;

        if (document.layers) {                          // Netscape, Firefox, probably deprecated

                document.layers['fset'].cols = nwCols;

        }

        else {

                if (document.all) {                     // MSIE, but acknowledged by Opera as well

                        eval ('document.all.fset.cols=nwCols');

                }

                else {

                        if (document.getElementById) {  // anything else, standard

                                document.getElementById('fset').cols = nwCols;

                        }

                }

        }

        //if (extendFrameset != 'y') {  // make persistent sky bars

                etl.document.location =
                ecl.document.location =
                eil.document.location =
                ehl.document.location =
                eml.document.location =
                ebl.document.location = relPath + 'extension_left.html';

                etr.location =
                ecr.location =
                eir.location =
                ctr.location =
                emr.location =
                ebr.location = relPath + 'extension_right.html';

        //}                             // make persistent sky bars

        if (smallSides == 'y') {

                _ml.document.location = relPath + 'ls_compact.html';

        }

        else {

                _ml.document.location = relPath + 'ls.html';

        }

        if (smallTop == 'y') {

                nav.document.location = relPath + 'nav_compact.html';

        }

        else {

                if (extendFrameset == 'y') {

                        nav.document.location = relPath + 'nav_extended.html';

                }

                else {

                        if (cfW > 966) {

                                nav.document.location = relPath + 'nav_024.html';

                        }

                        else {

                                nav.document.location = relPath + 'nav_800.html';

                        }

                }

        }

        dwl.document.location = relPath + '../../downline.php?js=on';

}

/*
 *
 *      also tied to the onLoad event of 'layout/frameset.html',
 *
 *      reads URL arguments and retrieves the 'l' and 'r' locations as provided to 'frameset.html',
 *      then checks if both locations were given in the expected base62-encoded form, composed of
 *      characters in the alphanumeric ranges, and with the sole possible exception of underscores,
 *      as a single underscore is used in permalink redirects to insulate the URL from the base-62
 *      or numeric argument (such as a member's encoded nickname or a thread, forum or message ID);
 *      upon successfully checking validity of both arguments, echoes them to 'index.php', loading
 *      that script in the 'pag' frame, replacing the central frameset's temporary placeholder: as
 *      of .htaccess, it only applies to permalink redirects involving an argument, not to 'doors',
 *      which are entry points that need graceful degradation in case javascripts were not enabled,
 *      enabling them to reach their (fixed) locations anyway (navigation bars and the status line
 *      are witheld in 'doors' because they'd be loaded twice in case javascripts were on, and that
 *      constitutes the majority of cases)
 *
 */

function switchToDestination (relPath) {

        var destination = relPath + '../../index.php';
        var args = document.location.href.split ('?');

        if ((args[1]) && (args[1] != 'submit=apply')) {

                var a = args[1].split ('&');

                if ((a[0]) && (a[1])) {

                        var b = a[0].split ('=');
                        var c = a[1].split ('=');
                        var l = unescape (b[1]);
                        var r = unescape (c[1]);
                        var l_invalid = /[^A-Za-z0-9\_\.]/.test (l);
                        var r_invalid = /[^A-Za-z0-9\_\.]/.test (r);

                        if ((l_invalid) || (r_invalid)) {

                                destination = relPath + 'error.html';

                        }

                        else {

                                destination = relPath + '../../index.php?l=' + l + '&r=' + r;

                        }
                }

                else {

                        destination = relPath + '../../index.php';

                }

        }

        parent.pag.document.location = destination;

}

/*
 *
 *      chat frame management:
 *
 *      the 'toggleChat' is called in reply to clicks on the show/hide chat frame gadget,
 *      and upon displaying the frameset after verifying its initial state (on/off); then
 *      there's 'verifyChatState', which in fact verifies the initial state of the frame,
 *      and calls toggleChat to adjust the frameset in accord to that state
 *
 */

var cState = 0;
var cLines = cbRows / 16;

function toggleChat () {

        var nwCapt;
        var nwRows;
        var l1, l2, l3, l4;

        if (cState == 0) {

                cState = 1;
                nwCapt = 'HIDE CHAT FRAME';
                nwRows = tnRows + ',' + cbRows + ',23,15,*,' + dlRows;
                l1 = 'chat_frame_holder.html';
                l2 = '../../input.php';
                l3 = 'chat_scroll_left.html';
                l4 = 'socket_left.html';

        }

        else {

                cState = 0;
                nwCapt = 'SHOW CHAT FRAME';
                nwRows = tnRows + ',0,0,15,*,' + dlRows;
                l1 = 'fill_void.html';
                l2 = '../../input.php?coff=1';
                l3 = l4 = 'socket_left.html';

        }

        if (document.layers) {                          // Netscape, Firefox, probably deprecated

                document.layers['linktext'].innerHTML = nwCapt;
                parent.document.layers['fset'].rows = nwRows;
                parent.chg.document.location = l1;
                parent.cli.document.location = l2;
                parent._cl.document.location = l3;
                parent._il.document.location = l4;

        }

        else {

                if (document.all) {                     // MSIE, but acknowledged by Opera as well

                        eval ('document.all.linktext.innerHTML = nwCapt');
                        eval ('parent.document.all.fset.rows = nwRows');
                        eval ('parent.chg.document.location = l1');
                        eval ('parent.cli.document.location = l2');
                        eval ('parent._cl.document.location = l3');
                        eval ('parent._il.document.location = l4');

                }

                else {

                        if (document.getElementById) {  // anything else, standard

                                document.getElementById('linktext').innerHTML = nwCapt;
                                parent.document.getElementById('fset').rows = nwRows;
                                parent.chg.document.location = l1;
                                parent.cli.document.location = l2;
                                parent._cl.document.location = l3;
                                parent._il.document.location = l4;

                        }

                }

        }

}

function verifyChatState () {

        var cookies = document.cookie.split (';');
        var hasChat = false;

        for (i in cookies) {

                parts = cookies[i].split ('=');
                if (parts[1]) {

                        cookieName = parts[0];
                        cookieContent = parts[1];
                        found = /postline\_cflag$/.test (cookieName);

                        if (found) {

                                hasChat = /\_on$/.test (cookieContent);

                        }

                }

        }

        cState = (hasChat) ? 0 : 1;     // the flag is, in reality, reversed...
        toggleChat ();                  // ...because toggleChat will then re-reverse it

}

function makeTallerChatFrame () {

        if (cLines == 12) {

                return;

        }

        cLines = 2 * cLines;
        cbRows = 16 * cLines;
        nwRows = tnRows + ',' + cbRows + ',23,15,*,' + dlRows;

        if (document.layers) {                          // Netscape, Firefox, probably deprecated

                parent.parent.document.layers['fset'].rows = nwRows;

        }

        else {

                if (document.all) {                     // MSIE, but acknowledged by Opera as well

                        eval ('parent.parent.document.all.fset.rows = nwRows');

                }

                else {

                        if (document.getElementById) {  // anything else, standard

                                parent.parent.document.getElementById('fset').rows = nwRows;

                        }

                }

        }

}

function makeShortrChatFrame () {

        if (cLines == 3) {

                return;

        }

        cLines = cLines / 2;
        cbRows = 16 * cLines;
        nwRows = tnRows + ',' + cbRows + ',23,15,*,' + dlRows;

        if (document.layers) {                          // Netscape, Firefox, probably deprecated

                parent.parent.document.layers['fset'].rows = nwRows;

        }

        else {

                if (document.all) {                     // MSIE, but acknowledged by Opera as well

                        eval ('parent.parent.document.all.fset.rows = nwRows');

                }

                else {

                        if (document.getElementById) {  // anything else, standard

                                parent.parent.document.getElementById('fset').rows = nwRows;

                        }

                }

        }

}

/*
 *
 *      backgrounds alignment:
 *
 *      'yOfFrame' is a function returning the top y coordinate of a frame, independently
 *      from the IE-specific window.screenTop property, which exists as window.screenY in
 *      Opera, and which in both those browsers reports the top Y coordinate of the frame
 *      rather than that of the browser's client window area; unfortunately, this kind of
 *      behavior (which is non-standard but more flexible) isn't replicated by Firefox or
 *      Google Chrome, and thus, I had to write this work-around to enable my backgrounds
 *      to be "spread" across different frames according to the actual 'rows' property of
 *      the main frameset
 *
 */

function yOfFrame (n) {

        var h = 0;

        if (parseInt (navigator.appVersion) > 3) {

                if (navigator.appName.indexOf ('Microsoft') == -1) {

                        h = top.innerHeight;

                }

                else {

                        h = parent.document.documentElement.clientHeight;

                        if (!h) {

                                h = parent.document.body.clientHeight;

                        }

                }

        }

        var r = parent.document.getElementById('fset').rows.split(',');
        var s = new Array ();
        var y;

        s[0] = 0;
        s[1] = s[0] + Number (r[0]);
        s[2] = s[1] + Number (r[1]);
        s[3] = s[2] + Number (r[2]);
        s[4] = s[3] + Number (r[3]);
        s[5] = h - Number (r[5]);

        switch (n) {

                case 'etl':
                case '_tl':
                case 'nav':
                case 'etr':

                        y = 0;
                        break;

                case 'ecl':
                case '_cl':
                case 'chg':
                case 'ecr':

                        y = 1;
                        break;

                case 'eil':
                case '_il':
                case 'cli':
                case 'eir':

                        y = 2;
                        break;

                case 'ehl':
                case 'ctl':
                case 'ctc':
                case 'ctr':

                        y = 3;
                        break;

                case 'eml':
                case '_ml':
                case 'pag':
                case 'emr':

                        y = 4;
                        break;

                case 'ebl':
                case '_bl':
                case 'dwl':
                case 'ebr':

                        y = 5;
                        break;

                default:

                        y = 0;

        }

        return (s[y]);

}
