<?php if (!defined ('postline/serverbot.php')) {

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
 *      IMPORTANT!
 *
 *      avoid launching this and other similary dependand scripts alone,
 *      to prevent potential malfunctions and unexpected security flaws:
 *      the starting point of launchable scripts is defining 'going'
 *
 */

$proceed = (defined ('going')) ? true : die ('[ postline serverbot language processor ]');

/*
 *
 *      widget includes
 *
 */

require ('settings.php');
require ('widgets/overall/primer.php');
require ('widgets/base62.php');
require ('widgets/dbengine.php');
require ('widgets/errors.php');
require ('widgets/locker.php');
require ('widgets/strings.php');

/*
 *
 *      specific includes
 *
 */

require ('serverbot/commons.php');
require ('serverbot/models.php');
require ('serverbot/responses.php');

/*
 *
 *      postline input connection
 *
 */

$_POST['f'] = readFrom ('serverbot/follow-ups');
$_POST['k'] = readFrom ('serverbot/context');
$_POST['q'] = $lineToProcess;

/*
 *
 *      retrieving context records
 *
 */

$f = empty ($_POST['f']) ? array () : unserialize ($_POST['f']);
$k = empty ($_POST['k']) ? array () : explode (',', $_POST['k']);

/*
 *
 *      determining generic context-driven "feelings"
 *
 */

$contextPattern = array_count_values ($GLOBALS['k']);
$unsure = ($contextPattern['r_unknown'] > 1) ? true : false;
$bored = $boredOf = false;

foreach ($contextPattern as $r => $n) { if ($n >= 2) {

        $bored = true;
        $boredOf = $r;

        break;

}} // matches the first (most recent) response occurring at least twice in saved context records

/*
 *
 *      splitting query into (up to five) prepositions:
 *      the limit is necessary to keep their increasing number of combinations to overload 'p_join'
 *
 */

$s = array_slice (split ('[.,:;!?]', $_POST['q']), 0, 5);
$p = array ();

foreach ($s as $q) {

        $q = str_word_count ($q, 1);

        for ($i = 1; $i < count ($q) - 1; ++ $i) {

                $w = $q[$i];

                if ($m_root[".$w."] == 'p_split') {

                        $p[] = array_slice ($q, 0, $i);
                        $q = array_slice ($q, $i + 1);
                        $i = 0;

                }

        }

        if (count ($q)) {

                $p[] = $q;

        }

}

/*
 *
 *      normalizing prepositions
 *
 */

$n = array

        (

                "i'm" => array ('I', 'am'),
                "you're" => array ('you', 'are'),
                "he's" => array ('he', 'is'),
                "she's" => array ('she', 'is'),
                "it's" => array ('it', 'is'),
                "we're" => array ('we', 'are'),
                "they're" => array ('they', 'are'),
                "that's" => array ('that', 'is'),
                "what's" => array ('what', 'is'),
                "where's" => array ('where', 'is'),
                "who's" => array ('who', 'is'),
                "isn't" => array ('is', 'not'),
                "aren't" => array ('are', 'not'),
                "weren't" => array ('were', 'not'),
                "haven't" => array ('have', 'not'),
                "hadn't" => array ('had', 'not'),
                "can't" => array ('can', 'not'),
                "won't" => array ('will', 'not'),
                "couldn't" => array ('could', 'not'),
                "shouldn't" => array ('should', 'not'),
                "mightn't" => array ('might', 'not'),
                "wouldn't" => array ('would', 'not'),
                "how's" => array ('how', 'is'),
                '4' => array ('for'),
                'u' => array ('you')

        );

$u = $p;
$p = array ();

foreach ($u as $s) {

        $t = array ();

        foreach ($s as $w) {

                $x = strtolower ($w);

                if (isset ($n[$x])) {

                        foreach ($n[$x] as $v) {

                                $t[] = $v;

                        }

                }

                else {

                        $t[] = rtrim ($w, "'");

                }

        } // each word in preposition

        $p[] = $t;

} // each preposition

/*
 *
 *      collecting a straight run of responses:
 *
 *      it's what implements rip-down rules and it's a tad complicated; for each preposition in the
 *      entry $q query, it reduces the preposition, word after word, in any of the two directions
 *      where a matching word could be found (the initial direction $d is a guess, then it keeps
 *      trying to match first in that direction, as constructs in the syntax models are generally
 *      given "word within word" in a same direction); upon matching a word, it restricts the model
 *      to the array of available choices from there, and keeps doing so until the preposition has
 *      no more words after a match, or a single word remains (which is then searched for a match)
 *
 */

function p_parse ($q, $d, $a = array (), $z = array ()) {

        if (!defined ('fnWSelect')) { function wSelect ($n, $w) {

                foreach ($n as $k => $v) {

                        $x = split (chr (32), $k);

                        if (in_array ($w, $x)) {

                                return ($v);

                        }

                }

                return (array ());

        } define ('fnWSelect', true); }

        /*
         *
         *      parsing prepositions
         *
         */

        $i = array_merge ($GLOBALS['f'], array ($GLOBALS['m_root']));
        $u = count ($i) - 1;

        foreach ($q as $p) {

          $k = false;
          $b = $p;
          $t = 0;

          foreach ($i as $j) {

            do {

              $g = false;
              $n = $j;
              $r = $d;

              do {

                if ($d > 0) {

                  $f = false;
                  $s = $p;

                  do {

                    $w = strtolower ($p[0]);
                    $p = array_slice ($p, 1);
                    $e = wSelect ($n, "$w.");

                    if (count ($e) > 0) {

                      $f = true;
                      $g = true;
                      $h = $p;
                      $n = $e;

                    }

                  } while (count ($p)); // forward loop to end of preposition

                  if ($f == false) {

                    $p = $s;

                    do {

                      $w = strtolower ($p[count ($p) - 1]);
                      $p = array_slice ($p, 0, -1);
                      $e = wSelect ($n, ".$w");

                      if (count ($e) > 0) {

                        $d = -1;
                        $f = true;
                        $g = true;
                        $h = $p;
                        $n = $e;

                      }

                    } while (count ($p)); // reverse loop to end of preposition

                    if ($f == false) {

                      break; // direction decision loop

                    }

                  } // no forward match, been trying reverse match

                } // forward run first

                else
                if ($d < 0) {

                  $f = false;
                  $s = $p;

                  do {

                    $w = strtolower ($p[count ($p) - 1]);
                    $p = array_slice ($p, 0, -1);
                    $e = wSelect ($n, ".$w");

                    if (count ($e) > 0) {

                      $f = true;
                      $g = true;
                      $h = $p;
                      $n = $e;

                    }

                  } while (count ($p)); // reverse loop to end of preposition

                  if ($f == false) {

                    $p = $s;

                    do {

                      $w = strtolower ($p[0]);
                      $p = array_slice ($p, 1);
                      $e = wSelect ($n, "$w.");

                      if (count ($e) > 0) {

                        $d = 1;
                        $f = true;
                        $g = true;
                        $h = $p;
                        $n = $e;

                      }

                    } while (count ($p)); // forward loop to end of preposition

                    if ($f == false) {

                      break; // direction decision loop

                    }

                  } // no reverse match, been trying forward match

                } // reverse run first

              } while (1); // direction decision loop, until break statement

              $l = false;
              $m = null;

              list ($l, $m) = isset ($n['match']) ? array ('match', $n['match']) : array ($l, $m);
              list ($l, $m) = isset ($n['alone']) ? array ('alone', $n['alone']) : array ($l, $m);
              list ($l, $m) = isset ($n['final']) ? array ('final', $n['final']) : array ($l, $m);

              if ($l === false) {

                if ($g == true) {

                  $d = $r;
                  $p = $h;

                } // word match occurred: will then reparse with a further trimmed preposition

                else {

                  if ($t == $u) {

                    $a[] = 'r_unknown';
                    $z[] = $b;

                  } // no leaf node matched, no further word matches, last model used: unknown

                } // no leaf node matched, and no further word matches: we will try next model

              } // no leaf node matched

              else {

                if (is_array ($m)) {

                  $n = $m;

                } // response has built-in context-driven alternatives

                else {

                  $n = array ('(default)' => $m);

                } // response always calls a same processor, implicitly the default one

                foreach ($n as $x => $y) {

                  $c = strBetween ($x, '(', ')');
                  $x = trim (strReplaceBetween ($x, '(', ')', voidString));

                  switch ($c) {

                    case 'bored':

                      $m = ($GLOBALS['bored'] === true) && ($x === voidString) ? true : false;
                      $m = ($GLOBALS['boredOf'] === $x) ? true : $m;

                      break;

                    case 'default':

                      $m = true;

                      break;

                    case 'strict':

                      $m = ($GLOBALS['k'][0] === $x) ? true : false;

                      break;

                    case 'unsure':

                      $m = ($GLOBALS['unsure'] == true) ? true : false;

                      break;

                    default:

                      $m = in_array ($x, $GLOBALS['k']) ? true : false;

                  } // context-driven conditions

                  if ($m == true) {

                    $f = true;

                    switch ($l) {

                      case 'alone':

                        if (count ($b) > 1) {

                          $f = false;

                        } // must be alone in preposition

                        break;

                      case 'final':

                        if (count ($h) > 0) {

                          $f = false;

                        } //  must be at end of preposition

                    } // check validity under indicated circumstance

                    if ($f == true) {

                      $a[] = $y;
                      $z[] = $h;

                      $g = false;
                      $k = true;

                    } // answer is valid and could be concatenated

                    break; // alternative selection loop

                  } // context match

                } // each alternative

              } // leaf node given

            } while ($g); // reparse loop

            if ($k == true) {

              break; // models loop

            } // valid answer found in this model

            else {

              ++ $t;

              $p = $b;

            } // valid answer not found, trying next model

          } // models loop (root model and each follow-up)

        } // prepositions loop (each preposition in query)

        return (count ($a) == 0) ? array (array ('r_unknown'), array ($q[0])) : array ($a, $z);

} list ($a, $z) = p_parse ($p, 1); $x = array_count_values ($a); $u = count ($a) - $x['r_unknown'];

/*
 *
 *      it needs collecting responses matching both forward and reverse patterns in any points:
 *      the match on forward and reverse patterns is done "in parallel" by p_parse, but the initial
 *      direction of preference is a guess, so it needs trying to use both possible values for the
 *      $d argument, i.e. +1 and -1, and then pick the best result
 *
 */

list ($x, $t) = p_parse ($p, -1);
$y = array_count_values ($x);
$v = count ($x) - $y['r_unknown'];

if ($v > $u) {

        $a = $x;
        $z = $t;
        $u = $v;

}

/*
 *
 *      gradually joining adjacent prepositions in all combinations, until the original query is
 *      reconstructed entirely (entry '/' of the resulting array): this operation is a preparation
 *      for a loop where the amount of 'r_unknown' responses obtained in the above straight run
 *      is compared to that of runs of each of the combinations computed by 'p_join', attempting
 *      to find a more convenient interpretation of conjuctions
 *
 */

function p_join ($q, $j = array ()) {

        $k = count ($q);

        if ($k > 2) {

                $i = 0;

                while ($i < $k - 1) {

                        $m = array (array_merge ($q[$i], $q[$i + 1]));

                        if ($i > 0) {

                                $l = array_slice ($q, 0, $i);
                                $m = array_merge ($l, $m);

                        }

                        if ($i < $k - 2) {

                                $r = array_slice ($q, $i + 2);
                                $m = array_merge ($m, $r);

                        }

                        $h = array ();

                        foreach ($m as $n) $h[] = count ($n);

                        $h = implode (',', $h);

                        if (!isset ($j[$h])) {

                                $j[$h] = $m;
                                $j = array_merge ($j, p_join ($m));

                        }

                        ++ $i;

                }

        }

        else if ($k == 2) {

                $j['/'] = array_merge ($j, array (array_merge ($q[0], $q[1])));

        }

        else if ($k == 1) {

                $j['/'] = $q;

        }

        ksort ($j);

        return ($j);

} $b = p_join ($p);

/*
 *
 *      interpreting conjunctions:
 *
 *      select the combination that maximizes differentiation and minimizes uncertainity,
 *      as above, using two initial direction guesses
 *
 */

foreach ($b as $c) {

        list ($x, $t) = p_parse ($c, 1);
        $y = array_count_values ($x);
        $v = count ($x) - $y['r_unknown'];

        if ($v > $u) {

                $a = $x;
                $z = $t;
                $u = $v;

        }

        list ($x, $t) = p_parse ($c, -1);
        $y = array_count_values ($x);
        $v = count ($x) - $y['r_unknown'];

        if ($v > $u) {

                $a = $x;
                $z = $t;
                $u = $v;

        }

}

/*
 *
 *      translating responses, gathering explanations, extending context records
 *
 */

$f = array_reverse ($f);
$k = array_reverse ($k);

$i = 0;
$r = voidString;
$v = false;

$exp = readFrom ('serverbot/explanations');
$yea = readFrom ('serverbot/affirmatives');
$nay = readFrom ('serverbot/negatives');

foreach ($a as $b) {

        if ($b != 'r_unknown') {

                $v = true;
                $q = $z[$i];

                if ($b[0] == '/') {

                        $r = r_connect (substr ($b, 1));

                }

                else {

                        eval ("{$b}();");

                }

        }

        ++ $i;

}

foreach ($a as $b) {

        if ($b[0] != '/') {

                $k[] = $b;

        }

}

$f = serialize (array_slice (array_reverse ($f), 0, 7));
$k = implode (',', array_slice (array_reverse ($k), 0, 7));

/*
 *
 *      postline output connection
 *
 */

if ($v == true) {

        logwr (ucfirst ($r), lw_persistent);

        writeTo ('serverbot/follow-ups', $f);
        writeTo ('serverbot/context', $k);
        writeTo ('serverbot/explanations', $exp);
        writeTo ('serverbot/affirmatives', $yea);
        writeTo ('serverbot/negatives', $nay);

}

define ('postline/serverbot.php', true); } ?>
