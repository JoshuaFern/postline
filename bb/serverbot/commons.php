<?php if (!defined ('postline/serverbot/commons.php')) {

/*************************************************************************************************

            HSP Postline

            PHP community engine
            version 2010.01.07 (10.1.7)

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
 *      IMPORTANT!
 *
 *      avoid launching this and other similary dependand scripts alone,
 *      to prevent potential malfunctions and unexpected security flaws:
 *      the starting point of launchable scripts is defining 'going'
 *
 */

$proceed = (defined ('going')) ? true : die ('[ postline serverbot response processors tools ]');

/*
 *
 *      basic tools for response processors
 *
 */

function r_unknown () {

        // $GLOBALS['r'] = r_connect ('?'); // debugging only

}

function choose_from ($p) {

        $n = mt_rand (0, count ($p) - 1);

        return $p[$n];

}

function r_connect ($r) {

        $f = array ();
        $i = 0;
        $n = 0;
        $l = strlen ($r);
        $p = voidString;
        $s = voidString;

        while ($l) {

                $c = $r[$i];

                switch ($c) {

                        case '(':
                        case '[':
                        case '{':

                                ++ $n;

                                if ($n == 1) {

                                        $p = voidString;
                                        $t = $c;

                                }

                                else {

                                        $p .= $c;

                                }

                                break;

                        case ')':
                        case ']':
                        case '}':

                                -- $n;

                                if ($n == 0) {

                                        $f[$t] = $p;

                                }

                                else {

                                        $p .= $c;

                                }

                                break;

                        default:

                                $p .= ($n >= 1) ? $c : voidString;
                                $s .= ($n == 0) ? $c : voidString;

                }

                ++ $i;
                -- $l;

        } $r = trim ($s);

        $GLOBALS['exp'] = isset ($f['(']) ? trim ($f['(']) : voidString;
        $GLOBALS['yea'] = isset ($f['[']) ? trim ($f['[']) : voidString;
        $GLOBALS['nay'] = isset ($f['{']) ? trim ($f['{']) : voidString;

        $r = substr ($r, 0, 4) == 'http' ? "<a target=\"_blank\" href=\"$r\">$r</a>" : $r;

        if ($GLOBALS['r'] == voidString) {

                $s1 = voidString;
                $v1 = voidString;
                $s2 = rtrim ($r, '?!>');
                $v2 = (strlen ($s2) < strlen ($r)) ? voidString : '.';

        }

        else {

                $GLOBALS['r'] = rtrim ($GLOBALS['r'], '.,');

                $s1 = rtrim ($GLOBALS['r'], '?!>');
                $t1 = substr ($GLOBALS['r'], strlen ($s1));
                $v1 =

                        (

                                (strlen ($s1) < strlen ($GLOBALS['r']))

                                        ? $t1

                                        : choose_from (array (

                                                ',',
                                                chr (32) . 'and',
                                                ', and',
                                                '...'

                                        ))

                        ) . chr (32);

                $s2 = rtrim ($r, '?!');
                $v2 = (strlen ($s2) < strlen ($r)) ? voidString : '.';

        }

        return ($s1 . $v1 . $r . $v2);

}

function getAnswer ($queryWords, $singularDefinitors, $pluralDefinitors) {

        if (!defined ('fnRemoteRequest')) { function remoteRequest ($host, $request) {

                if (!defined ('fnDecodeChunkedData')) { function decodeChunkedData ($data) {

                        $r = voidString;

                        while ($data != voidString) {

                                list ($size, $rest) = explode ("\r\n", $data, 2);

                                $size = hexdec (trim ($size));
                                $part = substr ($rest, 0, $size);
                                $data = substr ($rest, $size + 2);

                                $r .= $part;

                        }

                        return ($r);

                } define ('fnDecodeChunkedData', true); }

                $r = voidString;
                $s = @fsockopen ($host, 80, $errno, $errstr, 3);

                if ($s) {

                        @fwrite ($s, $request);

                        do {

                                $p = @fread ($s, 7000);
                                $r .= $p;

                        } while ($p);

                        @fclose ($s);

                }

                $h = (strBefore ($r, "\r\n\r\n"));
                $r = ($h == voidString) ? $r : substr ($r, strlen ($h) + 4);
                $f = (strpos ($h, 'Transfer-Encoding: chunked') === false) ? false : true;

                return (($f) ? decodeChunkedData ($r) : $r);

        } define ('fnRemoteRequest', true); }

        if (!defined ('fnSingularOf')) { function singularOf ($w) {

                if (!defined ('fnEndsBy')) { function endsBy ($w, $e) {

                        return ((substr ($w, - strlen ($e), strlen ($e)) === $e) ? true : false);

                } define ('fnEndsBy', true); }

                if (endsBy ($w, 'ees')) {

                        return (rtrim ($w, 'es') . 'ee');       // banshees -> banshee

                }

                else
                if (endsBy ($w, 'hes')) {

                        return (rtrim ($w, 'hes') . 'sh');      // crashes -> crash

                }

                else
                if (endsBy ($w, 'ies')) {

                        return (rtrim ($w, 'ies') . 'y');       // fairies -> fairy

                }

                else
                if (endsBy ($w, 'is')) {

                        return ($w);                            // axis -> axis (saves Noctis!)

                }

                else
                if (endsBy ($w, 'ves')) {

                        return (rtrim ($w, 'ves') . 'f');       // leaves -> leaf

                }

                else
                if (endsBy ($w, 's')) {

                        return (rtrim ($w, 's'));               // cars -> car

                }

                else {

                        return ($w);                            // already singular, irregular...

                }

        } define ('fnSingularOf', true); }

        if (!defined ('fnPluralOf')) { function pluralOf ($w) {

                if (!defined ('fnEndsBy')) { function endsBy ($w, $e) {

                        return ((substr ($w, - strlen ($e), strlen ($e)) === $e) ? true : false);

                } define ('fnEndsBy', true); }

                if (endsBy ($w, 'f')) {

                        return (rtrim ($w, 'f') . 'ves');       // leaf -> leaves

                }

                else
                if (endsBy ($w, 'h')) {

                        return (rtrim ($w, 'h') . 'es');        // crash -> crashes

                }

                else
                if (endsBy ($w, 's')) {

                        return ($w);                            // axis -> axis (I don't mind!)

                }

                else
                if (endsBy ($w, 'y')) {

                        return (rtrim ($w, 'y') . 'ies');       // fairy -> fairies

                }

                else {

                        return ($w . 's');                      // car -> cars

                }

        } define ('fnPluralOf', true); }

        if (!defined ('fnProcessParagraph')) { function processParagraph ($p, $x, $y) {

                $p = preg_replace ('/[\\x00-\\x1f]/', chr (32), $p);
                $p = preg_replace ('/\\x20{2,}/', chr (32), $p);
                $p = preg_replace ('/\<.*?\>/', voidString, $p);
                $p = preg_replace ('/\(.*?\)/', voidString, $p);
                $p = preg_replace ('/\[.*?\]/', voidString, $p);
                $s = split ('[' . chr (32) . '.,:;!?"]', strtolower ($p));
                $i = array_unique (array_intersect ($x, $s));
                $j = array_unique (array_intersect ($y, $s));
                $c = count ($i);
                $d = count ($j);

                return (array ($p, max ($c, $d)));

        } define ('fnProcessParagraph', true); }

        if (!defined ('fnFirstStatementOf')) { function firstStatementOf ($p) {

                $i = array (

                        'Corp.' => '[Corp]',
                        'Inc.' => '[Inc]'

                );

                foreach ($i as $k => $v) {

                        $p = str_replace ($k, $v, $p);

                }

                $s = explode ('.' . chr (32), $p);
                $s = count ($s) == 1 ? explode ('.', $p) : $s;
                $s = $s[0];

                foreach ($i as $k => $v) {

                        $s = str_replace ($v, $k, $s);

                } return (rtrim ($s, ',:;'));

        } define ('fnFirstStatementOf', true); }

        /*
         *
         *      wikipedia query results in an array $a of <p>paragraphs</p>
         *
         */

        $q = ucfirst (implode (chr (95), $queryWords));
        $h = utf8_decode (remoteRequest

        (

                "en.m.wikipedia.org",

                "GET /wiki/{$q} HTTP/1.1"
              . "\r\nHost: en.m.wikipedia.org"
              . "\r\nConnection: close"
              . "\r\nUser-Agent: Mary Lou"
              . "\r\n"
              . "\r\n"

        )); preg_match_all ('/(\<p\>)(.*?)(\<\/p\>)/', $h, $a);

        /*
         *
         *      building singular and plural versions of each query word
         *
         */

        $s = array ();
        $l = array ();

        foreach ($queryWords as $w) {

                $w = strtolower ($w);

                $s[] = singularOf ($w);
                $l[] = pluralOf ($w);

        }

        /*
         *
         *      the answer the caller gets if nothing relevant was matched or an error occurred,
         *      and a flag indicating the answer was, at least apparently, "strongly" matched
         *
         */

        $r = voidString;
        $m = 'none';

        /*
         *
         *      try all paragraphs, counting all matches of query terms and definitors greater than
         *      or equal to the $t threshold, i.e. the number of terms plus at least one definitor;
         *      in case of multiple paragraphs breaking the threshold, select the first paragraph,
         *      as it's most likely to be the definition
         *
         */

        $x = array_merge ($s, $singularDefinitors);
        $y = array_merge ($l, $pluralDefinitors);
        $t = count ($s) + 1;

        foreach ($a[2] as $p) {

                list ($p, $v) = processParagraph ($p, $x, $y);

                if ($v >= $t) {

                        $m = 'strong';
                        $r = $p;

                        break;

                }

        }

        /*
         *
         *      if not matched yet,
         *      try locating the sole paragraph "in show" to a human viewer:
         *
         *      using the mobile version of wikipedia might present the generic definition there,
         *      and that paragraph may, as a last chance, contain generic answers on the subject;
         *      this is determined as the very last <p>...</p> section preceeding a certain "key"
         *      and is then selected if it crosses or equals a minimalistic threshold of 1 of the
         *      terms of the initial query in singular or plural form
         *
         */

        if ($r == voidString) {

                preg_match_all ('/(\<p\>)(.*?)(\<\/p\>)/', strBefore ($h, '<h2>'), $a);

                if (count ($a[2]) > 0) {

                        list ($p, $v) = processParagraph ($a[2][count ($a[2]) - 1], $s, $l);

                        if ($v >= 1) {

                                $m = 'weak';
                                $r = $p;

                        }

                }

        }

        $m = (strpos ($r, 'may refer to') === false) ? $m : 'disamb';

        return (array (firstStatementOf ($r), $m));

}

define ('postline/serverbot/commons.php', true); } ?>
