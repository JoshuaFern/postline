<?php if (!defined ('postline/suitcase.php')) {

/*************************************************************************************************

            HSP Postline -- common functions suitcase

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

$proceed = (defined ('going')) ? true : die ('[ postline common functions ]');

/*
 *
 *      includes
 *
 */

require ('settings.php');
require ('widgets/overall/primer.php');
require ('widgets/base62.php');
require ('widgets/dbengine.php');
require ('widgets/errors.php');
require ('widgets/locker.php');
require ('widgets/stopwtch.php');
require ('widgets/strings.php');

/*
 *
 *      set locker handlers (implementing correlations between 'dbengine.php' and 'locker.php'):
 *
 *      if the session locker is in use, it might be common practice to invalidate the read-write
 *      cache upon entering a locked frame so that accessed files can be read again after locking
 *      and conditions that lead to locking be checked while nothing else changes in the relevant
 *      files; upon unlocking the session it should be also common to flush data written to files
 *      in the cache, to ensure flushing occurs within the locked frame
 *
 */

$onSessionLock[] = 'invalidateCache();';
$onSessionUnlock[] = 'flushCache();';

/*
 *
 * critical error message output:
 * dies printing a "fancy" bright orange page with the specified report, for better visibility.
 *
 */

$errorTemplate = 'widgets/layout/html/errors.html';     // assumed for right frames

if (defined ("left_frame")) $errorTemplate = 'widgets/layout/html/errors_l.html';
if (defined ("input_frame")) $errorTemplate = 'widgets/layout/html/errors_i.html';
if (defined ("bottom_frame")) $errorTemplate = 'widgets/layout/html/errors_d.html';

/*
 *
 *      input validation
 *      ----------------
 *
 *      'halt_if_forbidden' checks a block of data for presence of possible XSS injection:
 *      it is used throughout anything that a user may submit as data, including any files
 *      uploaded to the c-disk, including avatar images and especially compressed archives
 *      having extension: ZIP, 7Z, GZ, RAR, each of which is allowed in the c-disk; so, it
 *      is basically executed on anything that's received as user input.
 *
 *      To give you an idea of what all the following stuff is about, here are samples of
 *      filtered-out protocol aliases like 'javascript:' that could collect your cookies:
 *      ---------------------------------------------------------------------------------
 *
 *      <a target=pan href="jAvAsCrIp&#116:alert(document.cookie)"
 *      <a target=pan href="javascrip&#x74&#58 alert(document.cookie)"
 *      <a target=pan href="java&#115&#99&#114&#105&#112&#116&#58 alert(document.cookie)"
 *      <a href="java&#83&#67&#82&#73&#80&#84:alert(document.cookie)">
 *
 *      it's astounding that such absurd methods to trigger javascripts are held perfectly
 *      valid by browsers, but that's sadly true; now the above examples would only pop an
 *      alert showing all of your cookies pertaining the same domain of the bulletin board
 *      system. Also note that the login cookie (domain_postline_login), would be included
 *      in the said alert. Attackers willing to steal those cookies, though, could do that
 *      by simply tailing document.cookie to some tracking URL of their sites: all victims
 *      are requested to do is clicking a link similar to those of the above samples. Once
 *      in posses of a copy of the login cookie of the viewer, an attacker may then hijack
 *      the viewer's account. And if the hijacked accounts include those of administrators
 *      the attacker could then login as them and do whatever he/she wants. But is it ONLY
 *      concerning links? No, it concerns archives as well. On many past versions of MSIE,
 *      which makes it an observed behavior whatsoever, an archive file can be "faked", by
 *      just composing a given HTML page as a text file and then changing the extension of
 *      the file, e.g. to 'ZIP'. When submitted (without filtering) to the c-disk such ZIP
 *      would be accepted, but if directly linked in a message (href=cd/zips/filename.zip)
 *      it would then evaluate as what it is (at least on older versions of MSIE): an HTML
 *      page. And its scripts, still served by the board's domain, would work perfectly!
 *
 *      necessary thank you's for filters testing and bug reports:
 *      ----------------------------------------------------------
 *
 *              Hello :)
 *              Shadowlord
 *              Raptorjedi
 *
 *      additional notes:
 *      -----------------
 *
 *            - the most vicious cause of false positives is undoubtedly constituted by malformed
 *              html entities that can work in lack of their intended terminal semicolon; filters
 *              forbidding them will need to match on strings as short as '&#9' (three bytes) and
 *              so 'halt_if_forbidden' is expected to occasionally, but not rarely enough, report
 *              such combinations appearing randomly in binary data (the probability is 1:2^24 or
 *              1:16777216, leading to one such match in random submitted data every 16 megabytes)
 *
 *            - also see the $disallowed_strings array of 'settings.php' and its reference notes,
 *              particularly to know why certain precautions against numeric HTML entities, text
 *              formatters and C-like CSS comment blocks were taken
 *
 *            - $pending_file can be set to either null or to the name of a file that needs to
 *              be deleted before the function safely interrupts execution of a calling script
 *
 *            - aside of XSS injections, SQL injections are believed to be impossible in Postline
 *              because of the use of its own relational engine (dbengine.php), restricting query
 *              formulation to a limited set of possible queries, while accepting virtual folders
 *              and files' names formed almost exclusively by characters in Perl's word range and
 *              encoding entire files' contents in base64
 *
 */

$em['no_html_entities_allowed'] = 'HTML ENTITIES ARE NOT ALLOWED';
$ex['no_html_entities_allowed'] =

        "Sorry, you have encountered, voluntarily or not, a filter intended to prevent "
      . "javascript injections; any HTML entities (e.g. &amp;#97;) trigger this filter "
      . "because they could be used to obfuscate certain keywords inducing the browser "
      . "to execute malicious active content (e.g. arbitrary javascripts). "
      . "If this filter prevented you from submitting legit content, please accept our "
      . "apologies for this inconvenience, understanding that the lack of such content "
      . "filtering may lead to severe problems. Should you find a way to bypass this "
      . "filter and inject a working javascript or equivalently dangerous active contents, "
      . "you are kindly pleased to report your findings. Thank you.";

$em['forbidden_string'] = 'FORBIDDEN KEYWORD IN TEXT';
$ex['forbidden_string'] =

          "Sorry, you have encountered, voluntarily or not, a filter intended to prevent "
        . "javascript injections; certain keywords are not accepted no matter where they "
        . "appear in submitted data. If you found a way to bypass this filter, obtaining "
        . "working javascripts or equivalently dangerous active contents, you are kindly "
        . "pleased to report your findings. If this filter prevented you from submitting "
        . "legit content, please accept our apologies. Thank you.";

function halt_if_forbidden ($data, $pending_file) {

        global $disallowed_strings;

        /*
         *
         *      determine if there's a pending file to delete
         *
         */

        $null_name = ($pending_file == voidString) ? true : false;

        /*
         *
         *      scan for all known encodings of numeric html entities:
         *      error messages refer to any entities, but literals like &euro; are in fact allowed
         *
         */

        if (preg_match ('/(?i)\&\#x?[1234567890abcdef]{1,7};?/', $data)) {

                if ($null_name == false) {

                        if (@file_exists ($pending_file)) {

                                @unlink ($pending_file);

                        } // the indicated pending file in fact existed

                } // there was a pending file to delete

                die (because ('no_html_entities_allowed'));

        } // been matching one or more html entities (in fact only numeric entities are dangerous)

        /*
         *
         *      turn uppercase letters to lowercase, strip text formatters and CSS comment blocks
         *      out of the data stream (to contrast obfuscation of forbidden strings), then begin
         *      scanning for all given forbidden strings in plain text
         *
         */

        $data = preg_replace ('/[\\x00-\\x1f]/', voidString, strtolower ($data));
        $data = preg_replace ('/\/\*(.*?)\*\//', voidString, $data);

        foreach ($disallowed_strings as $s) {

                $a_match = (strpos ($data, $s) === false) ? false : true;

                if ($a_match) {

                        if ($null_name == false) {

                                if (@file_exists ($pending_file)) {

                                        @unlink ($pending_file);

                                } // the indicated pending file in fact existed

                        } // there was a pending file to delete

                        die (because ('forbidden_string'));

                } // been encountering a forbidden string

        } // each forbidden string

}

/*
 *
 *      this "patch" solves the glitch pointed out by Shadowlord (but first met by Raptorjedi):
 *      it concerns the use of HTML tags containing an odd number of quotes, i.e. some property
 *      for the tag which has an opening doublequote, but no closing doublequote - the glitch
 *      is seriously annoying because, apart from making all later messages following the "bad"
 *      message appear as parts of the "bad" message itself, it COULD *NOT* EVEN BE CORRECTED,
 *      because browser themselves may get confused on meeting such a malformed tag within the
 *      edit form's "textarea"... I and RJ met it before, but we did not realize what was the
 *      cause, until SL met it again and er... brought attention on the problem by starting a
 *      completely messed up thread; personally, I didn't imagine browsers were so fragile! :)
 *
 */

function filter_unclosed_quotes_in_tags ($m, $quotechar) {

        $n = 0;

        /*
         *
         *    looping for each character $c in string $m
         *
         */

        while (1) {

                $c = $m[$n];

                if ($c == voidString) {

                        /*
                         *
                         *      end of string
                         *
                         */

                        break;

                }

                if ($c == '<') {

                        /*
                         *
                         *      upon meeting an open HTML tag (in posts, a less than string that's
                         *      not part of an HTML tag, or that doesn't match an *allowed* tag,
                         *      would be escaped as &lt;) loop through the rest of the string until
                         *      either the the end of the tag, or the end of the string, setting up
                         *      counter $q to count any quoting characters laying within the tag...
                         *
                         */

                        $q = 0;

                        while (1) {

                                $c = $m[$n];

                                if (($c == '>') || ($c == voidString)) {

                                        /*
                                         *
                                         *      end of tag, or end of string
                                         *
                                         */

                                        break;

                                }

                                else
                                if ($c == $quotechar) {

                                        /*
                                         *
                                         *      doublequote found, increasing counter
                                         *
                                         */

                                        ++ $q;

                                }

                                ++ $n;

                        } // each character in tag

                        /*
                         *
                         *      after reaching the end of the tag (or the end of the string if the
                         *      tag wasn't closed by a greater than sign), check if counter $q has
                         *      an odd value (i.e. $q modulus 2 is 1), and if it is, it means some
                         *      doublequoted property value within the tag was left "open", due to
                         *      a forgotten "closing doublequote" (or other quoting character)...
                         */

                        if ($q % 2) {

                                /*
                                 *
                                 *      ...in which case, split the string in correspondence of the
                                 *      end of the tag, creating two fragments $a and $b; then, re-
                                 *      assemble the string, forcing a doublequote between the said
                                 *      fragments...
                                 *
                                 */

                                $a = substr ($m, 0, $n);
                                $b = substr ($m, $n);
                                $m = $a . $quotechar . $b;

                                /*
                                 *
                                 *      now, if the inmost "while" loop terminated because the end
                                 *      of the string was met, also terminate the outmost "while"
                                 *      to return $m as the result of this function; else, let the
                                 *      outmost loop go on until another tag is eventually met, so
                                 *      it could scan all other tags for unmatched quotes as well
                                 *
                                 */

                                if ($c == voidString) {

                                        break;

                                } // unclosed property at end of string

                        } // been processing an unclosed property

                } // found beginning of unescaped (true) html tag

                ++ $n;

        } // each character in string

        return ($m);

}

/*
 *
 *      Field GET ('fget')
 *
 *      function that retrieves an argument, of one or more lines, and replaces any codes that may
 *      disturb processing and/or archives management: ALL TEXTUAL USER INPUT MUST PASS FROM HERE,
 *      for many security reasons
 *
 */

function fget ($key, $maxlen, $cr_replacement) {

        /*
         *
         *      inspect both GET and POST arguments to get the value, then:
         *
         *            - eventually decode from base62, if $maxlen given as its negative counterpart
         *            - otherwise, eventually strip escaping slashes
         *
         */

        $value = (isset ($_GET[$key]))

                ? $_GET[$key]
                : ((isset ($_POST[$key])) ? $_POST[$key] : '');

        if ($maxlen >= 0) {

                $value = wStripslashes ($value);

        }

        else {

                $value = base62Decode ($value);
                $maxlen = - $maxlen;

        }

        /*
         *
         *      break processing if any forbidden strings are matched
         *
         */

        halt_if_forbidden ($value, voidString);

        /*
         *
         *      filter any and all text formatters which are not: blank space, \r, \n, \t
         *      then "standardize" end-of-line notation to Unix notation, and replace all
         *      such occurrences with the indicated replacement string ($cr_replacement):
         *      blank spaces or unwanted formatters are stripped from both ends in step 1
         *
         */

        $value = trim (preg_replace ('/[\\x00-\\x08\\x0b\\x0c\\x0e-\\x1f]/', chr (32), $value));
        $value = str_replace (array ("\r\n", "\r"), "\n", $value);
        $value = str_replace ("\n", $cr_replacement, $value);

        /*
         *
         *      apply the above patching function to problematic tags that may be missing quotes
         *
         */

        $value = filter_unclosed_quotes_in_tags ($value, '"');
        $value = filter_unclosed_quotes_in_tags ($value, "'");

        /*
         *
         *      truncate string to given maximum length
         *
         */

        $value = substr ($value, 0, $maxlen);

        /*
         *
         *      escape database meta-characters - this is EXTREMELY important, because the database
         *      uses some sort of pseudo-XML mark-up, and especially "greater than" and "less than"
         *      have to be absolutely escaped, otherwise the whole database would be put in EXTREME
         *      danger: database fields and proper records are terminated by a "less than" sign!
         *
         */

        $value = str_replace

                (

                        array ('<', '>', '"'),
                        array ('&lt;', '&gt;', '&quot;'),

                        $value

                );

        return ($value);

}

/*
 *
 *      search words indexing and lookups
 *
 *      for better performance and compactness, this archive doesn't follow the "standard" given
 *      by set/get operations; not that they're slow, but words' listings might grow pretty big,
 *      and optimized handling might be convenient, in terms of performance
 *
 */

function posts_page_of ($word) {

        global $bs_search_posts;

        /*
         *
         *      this function makes out a page number from the first 3 letters of a word:
         *      it's used to determine (as much randomly as possible) the dictionary page
         *      where the word's matchlist will be stored, and so equally distribute the
         *      dictionary's data load on all its pages; a few prime numbers are used as
         *      multipliers for basic randomization
         *
         */

        return

        (

        (41 * ord ($word[0]) + 37 * ord ($word[1]) + 31 * ord ($word[2])) % $bs_search_posts

        );

}

function logs_page_of ($word) {

        global $bs_search_logs;

        /*
         *
         *      this function makes out a page number from the first 3 letters of a word:
         *      it's used to determine (as much randomly as possible) the dictionary page
         *      where the word's matchlist will be stored, and so equally distribute the
         *      dictionary's data load on all its pages; a few prime numbers are used as
         *      multipliers for basic randomization
         *
         */

        return

        (

        (41 * ord ($word[0]) + 37 * ord ($word[1]) + 31 * ord ($word[2])) % $bs_search_logs

        );

}

function posts_by_page ($a, $b) {

        /*
         *
         *      this is a helper for 'wordlist_of':
         *      a custom callback to sort the list by dictionary page
         *
         */

        $i = posts_page_of ($a);
        $j = posts_page_of ($b);

        if ($i == $j) {

                return (0);

        }

        else {

                return (($i > $j) ? 1 : -1);

        }

}

function logs_by_page ($a, $b) {

        /*
         *
         *      this is a helper for 'wordlist_of':
         *      a custom callback to sort the list by dictionary page
         *
         */

        $i = logs_page_of ($a);
        $j = logs_page_of ($b);

        if ($i == $j) {

                return (0);

        }

        else {

                return (($i > $j) ? 1 : -1);

        }

}

function wordlist_of ($db, $message, $dofilter, $dosort) {

        global $common_words;

        /*
         *
         *      the 'wordlist_of' function separates the words of a message, and returns a list
         *      where every word appears only in one exemplary (as of array_unique); on request
         *      ($dosort = false) it will eventually avoid filtering and/or sorting the list by
         *      page number of the search dictionary; $db indicates which callback function (as
         *      declared above) must be used when sorting ('posts' or 'logs')
         *
         */

        /*
         *
         *      convert message to all lowercase: search will be caseless, of course
         *
         */

        $message = strtolower ($message);

        /*
         *
         *      filter out all tags: only index what's actually visible; however, mark the former
         *      presence of a tag with a blank space, so it will still count as a word separator
         *
         */

        $message = preg_replace ('/\&lt\;.+?\&gt\;/', blank, $message);

        /*
         *
         *      split by words, only considering parts of text of contiguous alphanumeric chars;
         *      make out an array with unique exemplaries of all words
         *
         */

        $message = preg_replace ('/[^a-z0-9]/', blank, $message);
        $words = wArrayUnique (preg_split ('/\x20+/', $message));

        /*
         *
         *      since this is not exactly Google, simplify and filter the above array,
         *      leaving out:
         *
         *            - words that are numbers outside the ranges 1..31 and 1970...2070;
         *            - alphabetic words whose length is < 3 or > 14;
         *            - words that appear in the $common_words array;
         *            - words, of any length, that contain digits but are not numbers (codes);
         *            - silly words including letters repeated 3 or more times (i.e. 'WEEEELCOME')
         *
         *      make the $wordlist array ordered by numeric progressive index, in the meantime
         *
         */

        $wordlist = array ();

        foreach ($words as $thisword) {

                $v = true;

                if ($dofilter) {

                        if (is_numeric ($thisword)) {

                                $thisword = intval ($thisword);

                                if (($thisword < 1) || ($thisword > 2070)) {

                                        $v = false;

                                } // word is a number outside the range [1-2070]

                                else
                                if (($thisword > 31) && ($thisword < 1970)) {

                                        $v = false;

                                } // word is a number inside the range ]31-1970[

                                else {

                                        $thisword = strval ($thisword);

                                } // word is a number part of a date, and is accepted for indexing

                        } // word is a number

                        else {

                                $l = strlen ($thisword);

                                if ($l < 3) {

                                        $v = false;

                                } // word is shorter than 3 characters

                                else
                                if ($l > 14) {

                                        $v = false;

                                } // word is longer than 14 characters

                                else
                                if (in_array ($thisword, $common_words)) {

                                        $v = false;

                                } // word is a common word

                                else
                                if (preg_match ('/[0-9]/', $thisword)) {

                                        $v = false;

                                } // word is an alphanumeric code

                                else {

                                        $n = 1;
                                        $c = 1;
                                        $k = $thisword[0];

                                        while ($c < $l) {

                                                if ($thisword[$c] == $k) {

                                                        ++ $n;

                                                        if ($n >= 3) {

                                                                $v = false;

                                                                break;

                                                        } // contiguous repeats >= 3 times

                                                } // letter repeats contiguously

                                                else {

                                                        $k = $thisword[$c];
                                                        $n = 0;

                                                } // new letter (did not repeat)

                                                ++ $c;

                                        } // each letter

                                } // word may hold contiguous repeated letters, three times or more

                        } // word is not a number

                } // apply filter

                if ($v == true) {

                        $wordlist[] = $thisword;

                } // word was not filtered

        } // each word

        /*
         *
         *      sort $wordlist by page-of-dictionary:
         *      the dictionary will be updated, in background processing, one page at a time
         *
         */

        if ($dosort) {

                usort ($wordlist, "{$db}_by_page");

        }

        return ($wordlist);

}

function addmatchlist ($db, $page, $words, $idpp) {

        global $maxresults;

        /*
         *
         *      function that adds a single group of "words-in-page-to-message-id" associations:
         *
         *      $db is the database to use within the 'words' folder: either 'posts' or 'logs';
         *      $page is the page of the index where all the words in $words are to be recorded;
         *      $words is an array of words, with one string per word, and upto 14 characters each;
         *      $idpp is a base62 post-or-phrase ID, left-padded with equals (=) to take 4 chars
         *
         */

        /*
         *
         *      compute the maximum partial wordrecord length as 4 * ($maxresults - 1):
         *      it's partial because it's the maximum length of the part of the record
         *      that may be appended to a 4-char base62 message ID, for the full record
         *      to be effectively 4 * $maxresults
         *
         */

        $m_len = 4 * ($maxresults - 1);

        /*
         *
         *      open the index of that page: if you can't, try creating; if you still can't, fail;
         *      a stopping character ($) may put an end to the file's effective contents, to avoid
         *      having to physically truncate the file when rewriting it in a shorter version
         *
         */

        $hFile = @fopen ("words/{$db}_index/page_$page.txt", 'rb+');
        $error = ($hFile === false) ? true : false;

        if ($error) {

                $hFile = @fopen ("words/{$db}_index/page_$page.txt", 'wb+');
                $error = ($hFile === false) ? true : false;

                if ($error) {

                        return;

                } // creation also failed

                $index = voidString;

        } // opening failed

        else {

                $index = fread ($hFile, wFilesize ("words/{$db}_index/page_$page.txt"));
                $stopperPosition = strpos ($index, '$');
                $stopperFound = ($stopper === false) ? false : true;

                if ($stopperFound) {

                        $index = substr ($index, 0, $stopperPosition);

                } // stopper was found and been truncating memory image of file

        } // opening not failed

        /*
         *
         *      avoid 8-kB write truncations on incomplete concurrent write access to the file
         *
         */

        set_file_buffer ($hFile, 0);

        /*
         *
         *      an index file is sequential, but it's handled so that it can be only partially
         *      written back after some IDs are inserted in a word's occurrences list, rather
         *      than having, every time, to write back the whole file; the "trick" is:
         *
         *            - in most cases, words will be already present in the index, so the file
         *              is written starting from the beginning of the first word which record
         *              was changed;
         *
         *            - among those cases a few words will be more frequently used than several
         *              other "rare" words so whenever a word's record is to be changed, the
         *              record is MOVED from its former position to the end of the index file
         *
         *      this might lead to a theoretical situation where, in most calls, this function
         *      will have to write back only a relatively small part of the index file, and more
         *      specifically, part of the end of the file; the $blockstart limit, starting at
         *      some arbitrarily high value, will be set to the beginning of the first record
         *      that needs to be saved back (i.e. $blockstart is initialized as overall minimum)
         *
         */

        $blockstart = 1e+9;

        /*
         *
         *      process the given list of words...
         *
         */

        foreach ($words as $word) {

                /*
                 *
                 *      add the match to the word record: if a record doesn't exist, append a new
                 *      record; indices are organized in a pseudo-XML form: I felt like it could
                 *      become useful in the future
                 *
                 */

                $w_beg = strpos ($index, "<$word>");
                $wBegFound = ($w_beg === false) ? false : true;

                if ($wBegFound) {

                        $w_end = strpos ($index, "</$word>\r\n", $w_beg);
                        $wEndFound = ($w_end === false) ? false : true;

                        if ($wEndFound) {

                                $w_len = strlen ($word);
                                $l_beg = $w_beg + $w_len + 2;
                                $l_len = $w_end - $l_beg;

                                /*
                                 *
                                 *      this will remove any records in excess of ($maxresults - 1)
                                 *      for the new full record, once this word's message ID is
                                 *      prepended to the record, to hold $maxresults matches
                                 *
                                 */

                                if ($l_len > $m_len) {

                                        $l_len = $m_len;

                                }

                                /*
                                 *
                                 *      insulating match list
                                 *
                                 */

                                $w_lst = substr ($index, $l_beg, $l_len);

                                /*
                                 *
                                 *      this search is to be performed to avoid duplicating matches
                                 *      within the same record: it can't happen for posts because
                                 *      the 'wordlist_of' function already removes duplicated words
                                 *      by an 'array_unique', but it can happen within chat logs,
                                 *      because one wordlist per line is created, and more than one
                                 *      list may refer to the same record (which, for that case,
                                 *      corresponds to a certain minute of a certain day); these
                                 *      duplicates, for common but unfiltered words, are rather
                                 *      frequent and worth removing for the sake of having slightly
                                 *      shorter index files
                                 *
                                 */

                                if ($db == 'posts') {

                                        $matchFound = false;

                                } // currently indexing messages

                                else {

                                        /*
                                         *
                                         *      searching the ID match, be sure to consider a valid
                                         *      match only one that begins at a 4-char boundary, to
                                         *      prevent mismatching because of a couple IDs that,
                                         *      concatenated, could incidentally match the ID code
                                         *      you were looking for
                                         *
                                         */

                                        $from = 0;

                                        do {

                                                $i = strpos ($w_lst, $idpp, $from);
                                                $iFound = ($i === false) ? false : true;

                                                if ($iFound === false) {

                                                        break;

                                                } // no match found

                                                $rest = $i % 4;         // offset of match
                                                $from = $i + 1;         // prepares next iteration

                                                $keepGoing = ($rest == 0) ? false : true;

                                        } while ($keepGoing); // stop only matching 4-char boundary

                                        $matchFound = ($iFound === false) ? false : true;
                                        $matchFound = ($rest == 0) ? $matchFound : false;

                                } // currently indexing chat logs

                                /*
                                 *
                                 *      now, only add this match if no previous copies of it
                                 *      were found
                                 *
                                 */

                                if ($matchFound == false) {

                                        $index =

                                                substr ($index, 0, $w_beg)
                                              . substr ($index, $w_end + $w_len + 5)
                                              . "<$word>$idpp$w_lst</$word>\r\n";

                                        if ($w_beg < $blockstart) {

                                                $blockstart = $w_beg;

                                        } // been recording new overall minimum

                                } // match was not found duplicated in same wordrecord

                        } // end of wordrecord found

                } // beginning of wordrecord found

                else {

                        $blockstart = 0;
                        $index = "<$word>$idpp</$word>\r\n" . $index;

                } // existing wordrecord not found, been adding new record on top of file

        } // each word

        /*
         *
         *      write index back:
         *      it's a break-safe operation, as the write is unbuffered and no truncation is done
         *
         */

        if ($blockstart < 1e+9) {

                fseek ($hFile, $blockstart);
                fwrite ($hFile, substr ($index, $blockstart) . '$');

        } // overall minimum was changed, so there's changes to write back

        /*
         *
         *      close index file
         *
         */

        fclose ($hFile);

}

function clrmatchlist ($db, $page, $words, $idpp) {

        /*
         *
         *      function that clears a single group of "words-in-page-to-message-id" associations:
         *
         *      $db is the database to use within the 'words' folder: either 'posts' or 'logs';
         *      $page is the page of the index where all the words in $words are to be recorded;
         *      $words is an array of words, with one string per word, and upto 14 characters each;
         *      $idpp is a base62 post-or-phrase ID, left-padded with equals (=) to take 4 chars
         *
         */

        /*
         *
         *      open the index of that page: if you can't, fail;
         *      a stopping character ($) may put an end to the file's effective contents, to avoid
         *      having to physically truncate the file when rewriting it in a shorter version
         *
         */

        $hFile = @fopen ("words/{$db}_index/page_$page.txt", 'rb+');
        $error = ($hFile === false) ? true : false;

        if ($error) {

                return;

        } // opening failed

        else {

                $index = fread ($hFile, wFilesize ("words/{$db}_index/page_$page.txt"));
                $stopperPosition = strpos ($index, '$');
                $stopperFound = ($stopper === false) ? false : true;

                if ($stopperFound) {

                        $index = substr ($index, 0, $stopperPosition);

                } // stopper was found and been truncating memory image of file

        } // opening not failed

        /*
         *
         *      avoid 8-kB write truncations on incomplete concurrent write access to the file
         *
         */

        set_file_buffer ($hFile, 0);

        /*
         *
         *      set start-of-writeback marker, as for the above function,
         *      although no records will be moved while clearing occurrences from them...
         *
         */

        $blockstart = 1e+9;

        /*
         *
         *      process the given list of words...
         *
         */

        foreach ($words as $word) {

                /*
                 *
                 *      remove the match from the word record:
                 *      then, if the record is entirely void, remove the record itself
                 *
                 */

                $w_beg = strpos ($index, "<$word>");
                $wBegFound = ($w_beg === false) ? false : true;

                if ($wBegFound) {

                        $w_end = strpos ($index, "</$word>\r\n", $w_beg);
                        $wEndFound = ($w_end === false) ? false : true;

                        if ($wEndFound) {

                                $w_len = strlen ($word);
                                $l_beg = $w_beg + $w_len + 2;
                                $w_lst = substr ($index, $l_beg, $w_end - $l_beg);

                                /*
                                 *
                                 *      searching the ID match, be sure to consider a valid match
                                 *      only one that begins at a 4-char boundary, so to prevent
                                 *      mismatching because of a couple IDs that, concatenated,
                                 *      could incidentally match the ID code you were looking for
                                 *
                                 */

                                $from = 0;

                                do {

                                        $i = strpos ($w_lst, $idpp, $from);
                                        $iFound = ($i === false) ? false : true;

                                        if ($iFound === false) {

                                                break;

                                        } // no match found

                                        $rest = $i % 4;         // offset of match
                                        $from = $i + 1;         // prepares next iteration

                                        $keepGoing = ($rest == 0) ? false : true;

                                } while ($keepGoing); // stop only matching 4-char boundary

                                $matchFound = ($iFound === false) ? false : true;
                                $matchFound = ($rest == 0) ? $matchFound : false;

                                if ($matchFound) {

                                        $w_lst = substr ($w_lst, 0, $i) . substr ($w_lst, $i + 4);

                                        if ($w_lst == voidString) {

                                                $index =

                                                        substr ($index, 0, $w_beg)
                                                      . substr ($index, $w_end + $w_len + 5);

                                        } // record empty after match removal

                                        else {

                                                $index =

                                                        substr ($index, 0, $w_beg)
                                                      . "<$word>$w_lst</$word>\r\n"
                                                      . substr ($index, $w_end + $w_len + 5);

                                        } // record not empty after match removal

                                        if ($w_beg < $blockstart) {

                                                $blockstart = $w_beg;

                                        } // been recording new overall minimum

                                } // match found and removed

                        } // end of wordrecord found

                } // beginning of wordrecord found

        } // each word

        /*
         *
         *      write index back:
         *      it's a break-safe operation, as the write is unbuffered and no truncation is done
         *
         */

        if ($blockstart < 1e+9) {

                fseek ($hFile, $blockstart);
                fwrite ($hFile, substr ($index, $blockstart) . '$');

        } // overall minimum was changed, so there's changes to write back

        /*
         *
         *      close index file
         *
         */

        fclose ($hFile);

}

function set_occurrences_of ($db, $wordlist, $id) {

        /*
         *
         *      upon posting, indexs a message, given its wordlist and its numeric ID:
         *
         *            - $db is the archive to use within the 'words' folder: 'posts' or 'logs';
         *            - $wordlist is a list of words returned by 'wordlist_of';
         *            - $id is a message's decimal ID, or a logfile epoch
         *
         *      this function will append the list of words as jobs to given archive's LIFO list
         *
         */

        /*
         *
         *      assemble the list of jobs to be saved to the LIFO list corresponding to $db:
         *      job record is formed by the word (padded with '=' upto 14 characters), followed
         *      by a '+' sign (signalling word has to be added to the corresponding index page),
         *      and the base-62 conversion of $id, left-padded upto 4 characters
         *
         */

        $s = voidString;
        $i = '+' . str_pad (convertToBase62 (intval ($id)), 4, '=', STR_PAD_LEFT);

        foreach ($wordlist as $w) {

                $s .= str_pad ($w, 14, '=') . $i;

        }

        /*
         *
         *      append list to LIFO
         *
         */

        $hFile = @fopen ("words/{$db}_lifo.txt", 'ab');
        $error = ($hFile === false) ? true : false;

        if ($error == false) {

                set_file_buffer ($hFile, 0);
                fwrite ($hFile, $s);
                fclose ($hFile);

        }

}

function clear_occurrences_of ($db, $wordlist, $id) {

        /*
         *
         *      upon editing or deleting, un-indexs message, given its wordlist and its numeric ID:
         *
         *            - $db is the archive to use within the 'words' folder: 'posts' or 'logs';
         *            - $wordlist is a list of words returned by 'wordlist_of';
         *            - $id is a message's decimal ID, or a logfile epoch
         *
         *      this function will append the list of words as jobs to given archive's LIFO list:
         *      normally, the 'logs' database will never need un-indexing, so the $db argument to
         *      this function might always be 'posts', because you DON'T edit or delete a sentence
         *      written into a chat log; however the $db argument is provided to improve coherence
         *      and for possible future use...
         *
         */

        /*
         *
         *      assemble the list of jobs to be saved to the LIFO list corresponding to $db:
         *      job record is formed by the word (padded with '=' upto 14 characters), followed
         *      by a '-' sign (signalling word has to be removed from the corresponding index),
         *      and the base-62 conversion of $id, left-padded upto 4 characters
         *
         */

        $s = voidString;
        $i = '-' . str_pad (convertToBase62 (intval ($id)), 4, '=', STR_PAD_LEFT);

        foreach ($wordlist as $w) {

                $s .= str_pad ($w, 14, '=') . $i;

        }

        /*
         *
         *      append list to LIFO
         *
         */

        $hFile = @fopen ("words/{$db}_lifo.txt", 'ab');
        $error = ($hFile === false) ? true : false;

        if ($error == false) {

                set_file_buffer ($hFile, 0);
                fwrite ($hFile, $s);
                fclose ($hFile);

        }

}

function process_jobs ($db) {

        global $search_lifo_jpp;

        /*
         *
         *      to be called by pquit() to perform background transations towards dictionaries
         *      and indices
         *
         *      for 'reindex_posts.php' and 'reindex_logs.php', it must return wether some records
         *      were processed or not, by returning true (some records were processed) or false
         *      (the list was empty), because those scripts need to know when to stop calling this
         *      function while being sure the list has finally been emptied and all words indexed
         *
         *            - note: reindexing scripts were not provided with versions 2006 through 2009
         *              of Postline, but they may be back in future versions, if found necessary
         *
         */

        /*
         *
         *      parse given database LIFO, process its last records, remove processed records by
         *      truncating; start by checking, before eventually locking the session, if there's
         *      anything to process
         *
         */

        $lifosize = wFilesize ("words/{$db}_lifo.txt");

        if ($lifosize == 0) {

                return (false);

        }

        /*
         *
         *      lock the session now, to eventually truncate the list, undisturbed
         *
         */

        lockSession ();

        /*
         *
         *      check size again to see if, before the lock,
         *      no other script emptied the list in the meantime...
         *
         */

        $lifosize = wFilesize ("words/{$db}_lifo.txt");

        if ($lifosize == 0) {

                return (false);

        }

        /*
         *
         *      I wonder,
         *
         *      could the size of a file which is saved in spans of 19 bytes,
         *      and eventually truncated by spans of 19 bytes, be something
         *      different from a multiple of 19 bytes?
         *
         *      well, in certain test runs, it seemed it could; maybe I wasn't flushing?
         *
         */

        $jlen = 14 + 1 + 4;     // job length = word length + flag + encoded message ID length

        if ($lifosize % $jlen) {

                $lifosize = ((int) ($lifosize / $jlen)) * $jlen;

        } // if no multiple of $jlen - see above comments

        /*
         *
         *      open search indexing LIFO list, and process a span of it: failing to open causes
         *      the list to be unaffected, but the function still returns true to signal pending
         *      jobs to callers
         *
         */

        $hFile = @fopen ("words/{$db}_lifo.txt", 'rb+');
        $error = ($hFile === false) ? true : false;

        if ($error == false) {

                /*
                 *
                 *      read upto one span from the end of the list
                 *
                 *            - span size (in words) is $search_lifo_jpp
                 *            - don't trust SEEK_END: calculate it basing on the known $lifosize
                 *
                 */

                fseek ($hFile, $lifosize - ($jlen * $search_lifo_jpp));
                $jobs = fread ($hFile, $jlen * $search_lifo_jpp);

                /*
                 *
                 *      count the number of words read,
                 *      minus 1 to make out an index to last word's record
                 *
                 */

                $jnum = (int) (strlen ($jobs) / $jlen) - 1;

                /*
                 *
                 *      explode data ($word, $flag, $idpp) from last record of this span ($jnow);
                 *      compute number of dictionary page to be updated considering that last word
                 *
                 */

                $jnow = substr ($jobs, $jnum * $jlen, $jlen);
                $word = rtrim (substr ($jnow, 0, 14), '=');
                $flag = substr ($jnow, 14, 1);
                $idpp = substr ($jnow, 15, 4);
                $page = ($db == 'posts') ? posts_page_of ($word) : logs_page_of ($word);

                /*
                 *
                 *      create a wordlist, scanning the span in reverse until:
                 *
                 *            a) there's no more words in the given span;
                 *            b) $flag changes;
                 *            c) $idpp changes;
                 *            d) $page changes
                 *
                 *      meanwhile, count the words in the list ($jcnt), because an equal amount
                 *      of records will have to be deleted from the LIFO list, after indexing
                 *      those words
                 *
                 */

                $list = array ($word);  // insert last word as first element of $list array
                $jcnt = 1;              // count that first element

                while ($jnum) {

                        /*
                         *
                         *      read previous job record
                         *
                         */

                        $jnum --;
                        $jnow = substr ($jobs, $jnum * $jlen, $jlen);

                        /*
                         *
                         *      check conditions b and c
                         *
                         */

                        if (substr ($jnow, 14, 1) != $flag) {

                                break;

                        }

                        if (substr ($jnow, 15, 4) != $idpp) {

                                break;

                        }

                        /*
                         *
                         *      check condition d
                         *
                         */

                        $word = rtrim (substr ($jnow, 0, 14), '=');
                        $test = ($db == 'posts') ? posts_page_of ($word) : logs_page_of ($word);

                        if ($test != $page) {

                                break;

                        }

                        /*
                         *
                         *      if all conditions match, add word to list, and increase counter
                         *
                         */

                        $list[$jcnt] = $word;

                        ++ $jcnt;

                } // each job in processed span

                /*
                 *
                 *      index the words in list: because of the above conditions, this process will
                 *      target only one page of the dictionary; there's one further case for $flag:
                 *      a forward slash which means this was a sequence of dummy records left there
                 *      upon deleting a list of jobs in consequence of a message edit ('edit.php');
                 *      in such cases, this processor needs doing nothing, really, but only shorten
                 *      the LIFO list to remove the slashed-out sequence
                 *
                 */

                if ($flag == '+') {

                        addmatchlist ($db, $page, $list, $idpp);

                }

                if ($flag == '-') {

                        clrmatchlist ($db, $page, $list, $idpp);

                }

                /*
                 *
                 *      compute the size of the LIFO list after deleting records from its tail:
                 *      the list will be simply truncated (which is a rather quick operation,
                 *      and gives the main reason why I'm using a LIFO, in place of a FIFO)
                 *
                 */

                $lifosize -= $jlen * $jcnt;

                ftruncate ($hFile, $lifosize);
                fclose ($hFile);

        } // no error opening LIFO jobs list file

        return (true);

}

function where_occurs ($db, $word) {

        /*
         *
         *      upon searching, finds which messages contain the given word and returns an array of
         *      base-62 IDs; $db determines where to match (either 'posts' or 'logs')
         *
         */

        /*
         *
         *      fetch index page holding corresponding word
         *
         */

        $page = ($db == 'posts') ? posts_page_of ($word) : logs_page_of ($word);
        $index = @file_get_contents ("words/{$db}_index/page_$page.txt");

        /*
         *
         *      search this word's record, explode matches, return matchlist
         *
         */

        $w_beg = strpos ($index, "<$word>");
        $wBegFound = ($w_beg === false) ? false : true;

        if ($wBegFound == false) {

                /*
                 *
                 *      no wordrecord, no matches
                 *
                 */

                return (array ());

        }

        else {

                /*
                 *
                 *      determine beginning and end of record's contents,
                 *      insulate the string of message IDs from the matching record
                 *
                 */

                $w_end = strpos ($index, "</$word>\r\n", $w_beg);
                $wEndFound = ($w_end === false) ? false : true;

                if ($wEndFound == false) {

                        /*
                         *
                         *      missing wordrecord's end tag (corrupted index page record),
                         *      interpreted as no matches
                         *
                         */

                        return (array ());

                }

                else {

                        $w_beg += strlen ($word) + 2;
                        $matchlist = substr ($index, $w_beg, $w_end - $w_beg);

                        /*
                         *
                         *      split the string in fragments of 4 characters, being base-62
                         *      encodes of message IDs where the word occurs, and return the array
                         *
                         */

                        preg_match_all ("/.{4}/", $matchlist, $matches);

                        return ($matches[0]);

                } // also matched the end of the wordrecord

        } // matched a wordrecord

}

/*
 *
 *      server messages and logs
 *
 *      persistent notices will be stored in chat logs as well, temporary notices will only show
 *      in the chat frame, and be lost afterwards: generally, persistent notices might concern
 *      some permanent change to the database, which might be worth logging for later investigation
 *      in case troubles arised...
 *
 */

define ('lw_persistent' , 1);
define ('lw_temporary'  , 2);

function logwr ($message, $logtype) {

        global $epc;                  // 'settings.php'
        global $servername;           // 'settings.php'
        global $sitename;             // 'settings.php'
        global $plversion;            // 'settings.php'
        global $trimlinesafter;       // 'template.php'
        global $time_offset;          // 'settings.php'

        /*
         *
         *      add server notice to the public chat frame:
         *      $logtype can be either lw_persistent (recorded in chat logs) or lw_temporary
         *
         */

        lockSession ();

        /*
         *
         *      read and insulate existing text,
         *      concatenate message to be logged
         *
         */

        $epcstamp = gmdate ('H:i', $epc);
        $toremind =

                  $epcstamp
                . ',' . blank
                . '<a class=se>'
                . $servername
                . '</a>'
                . '&gt;' . blank
                . $message
                . "<br>\n";

        $textdata = readFrom ('frespych/conversation');

        /*
         *
         *      trim logfile to max lines,
         *      store in persistent logs whatever disappears from chat frame
         *
         */

        $count  = 0;
        $dotrim = false;
        $scan   = strlen ($textdata) - 1;

        while ($scan > 0) {

                if ($textdata[$scan] == "\n") {

                        ++ $count;

                        if ($count >= $trimlinesafter) {

                                $dotrim = true;

                                break;

                        }

                }

                -- $scan;

        }

        if ($dotrim) {

                /*
                 *
                 *      chop the line from the beginning of existing text ($textdata)
                 *
                 */

                $textdata = substr ($textdata, $scan);

        } // there was a line to trim from the top of the conversation

        /*
         *
         *      output updated conversation to public chat
         *
         */

        writeTo ('frespych/conversation', $textdata . $toremind);

        /*
         *
         *      log if necessary
         *
         */

        if ($logtype == lw_persistent) {

                /*
                 *
                 *      remove any HTML tags, and break long uninterrupted lines,
                 *      because the logs are not supposed to have horizontal scrollbars
                 *
                 */

                $toremind = preg_replace ('/\<.*?\>/', voidString, $toremind);
                $toremind = breakUnspaced ($toremind, $maxcharsperfrag, blank);
                $toremind = ltrim ($toremind, "\n");

                /*
                 *
                 *      determine the name of today's chat log file, based on current date:
                 *      yes, this will cause a "trail" of the past day to appear in the
                 *      subsequent day's logfile, but I don't mind: it's normally a very
                 *      small part of the log anyway...
                 *
                 */

                $year = gmdate ('Y', $epc);
                $date = gmdate ('Y_m_d', $epc);
                $file = 'logs/' . $year . '/' . $date . '.html';

                /*
                 *
                 *      save this line (append if file exists, else create a new log),
                 *      remember to mark the spot with an anchor (in hour.minute format)
                 *
                 */

                list ($hour, $minute) = explode (':', strBefore ($toremind, ','));

                $hFile = @fopen ($file, 'rb+');
                $error = ($hFile === false) ? true : false;

                if ($error == false) {

                        fseek ($hFile, 0, SEEK_END);
                        fwrite ($hFile, "<a name=$hour$minute>" . $toremind . '</a><br>');
                        fclose ($hFile);

                } // been appending to existing logfile

                else {

                        $hFile = @fopen ($file, 'wb');
                        $error = ($hFile === false) ? true : false;

                        if ($error == false) {

                                $log_head = str_replace

                                        (

                                                array

                                                        (

                                                                "[DATE]",
                                                                "[SITENAME]",
                                                                "[PLVERSION]"

                                                        ),

                                                array

                                                        (

                                                                gmdate ('F d, Y', $epc),
                                                                $sitename,
                                                                $plversion

                                                        ),

                                                @file_get_contents ('layout/html/chat_log_header.html')

                                        )

                                        . "<a name=$hour$minute>" . $toremind . '</a><br>';

                                fwrite ($hFile, $log_head);
                                fclose ($hFile);

                        } // been (successfully) creating a new logfile

                } // failed to open existing logfile in append

                /*
                 *
                 *      file created alright?
                 *      if not, try creating year folder, and THEN retry creating
                 *
                 */

                $create_year_folder = (@file_exists ($file)) ? false : true;

                if ($create_year_folder) {

                        @mkdir ('logs/' . gmdate ('Y', $epc));

                        $hFile = @fopen ($file, 'wb');
                        $error = ($hFile === false) ? true : false;

                        if ($error == false) {

                                $log_head = str_replace

                                        (

                                                array

                                                        (

                                                                "[DATE]",
                                                                "[SITENAME]",
                                                                "[PLVERSION]"

                                                        ),

                                                array

                                                        (

                                                                gmdate ('F d, Y', $epc),
                                                                $sitename,
                                                                $plversion

                                                        ),

                                                @file_get_contents ('layout/html/chat_log_header.html')

                                        )

                                        . "<a name=$hour$minute>" . $toremind . '</a><br>';

                                fwrite ($hFile, $log_head);
                                fclose ($hFile);

                        } // been (successfully) creating a new logfile

                } // been creating a new logfile in a new yearly folder

                /*
                 *
                 *      providing log creation or update succeeded,
                 *      index what's been transferred to the log ($toremind), for searching
                 *
                 */

                if ($error == false) {

                        /*
                         *
                         *      any valid words to index?
                         *      if yes, compute epoch and store occurrences in dictionary
                         *
                         */

                        $words = wordlist_of ('logs', $message, true, true);

                        if (count ($words) > 0) {

                                list ($m, $d, $y) = explode ('/', gmdate ('m/d/Y', $epc));

                                $e = gmmktime (20, 05, 00, 05, 05, 2003, 0) + $time_offset;
                                $e = gmmktime ($hour, $minute, 0, $m, $d, $y, 0) - $e;
                                $e = (int) ($e / 60);

                                if ($e >= 0) {

                                        set_occurrences_of ('logs', $words, $e);

                                }

                        } // the line of text to be logged generated a non-void wordlist

                } // there was no error creating or updating the relevant logfile

        } // the line of text from the conversation had to be logged

}

/*
 *
 *      functions controlling message hints' generation,
 *      and the recent discussions' archive (recdisc)
 *
 *      for some reason, presumably performance or compactness, this particular archive doesn't
 *      follow the same format of most other database files saved by 'dbengine'; it lacks field
 *      names and has improper records formed by a greater-than-delimited list of fields in the
 *      exact following order:
 *
 *          forum-name > thread-title > msg-id > author > epoc > hint > forum-id > thread-id \n
 *
 *      this archive is then managed as a FIFO list, sized by $recdiscrecords; all this implies
 *      that this archive needs specific processing to be saved and to stay up-to-date; there's
 *      also this strange 'cutcuts' function which simply removes leading and trailing "relics"
 *      of replacement entities, having been "broken" by the fact that a "hint" is a portion of
 *      the message's body, clipped straight from the middle of a post; this same kind of hint,
 *      and 'cutcuts', are used to make search results' hints; however, albeit it's possible to
 *      call 'cutcuts' alone, it might be convienient to replace single calls to 'cutcuts' with
 *      equivalent calls to 'shorten', which also cares for truncating a given string before an
 *      eventually incomplete word is left hanging at the end of the string... I gave up making
 *      'shorten' any more comprehensible, though :P
 *
 */

function cutcuts ($h) {

        $h = preg_replace ('/^(quot\;|uot\;|ot\;|t\;)/', voidString, $h);       // leading &quot;
        $h = preg_replace ('/(\&quot|\&quo|\&qu|\&q)$/', voidString, $h);       // trailing &quot;

        $h = preg_replace ('/^(lt\;|gt\;)/', voidString, $h);           // leading &lt; or &gt;
        $h = preg_replace ('/(\&lt|\&l|\&gt|\&g)$/', voidString, $h);   // trailing &lt; or &gt;

        return (trim ($h, '&;'));       // leading and trailing '&;' (often, it replaces newlines)

}

function shorten ($text, $max) {

        $T = $text;
        $t = cutcuts (substr ($T, 0, $max));
        $r = ($t == $T) ? $t : rtrim (preg_replace ('/(\s+\S+)$/', '', $t), ' .,:;?!') . '...';

        return (($r == '...') ? $t . '...' : $r);

}

function addrecdisc ($f, $t, $m, $nick, $hint, $fid, $tid) {

        global $epc;
        global $recdiscrecords;

        /*
         *
         *      adds an entry to the recent discussions' archive
         *
         *      $f = forum name string
         *      $t = thread title string
         *      $m = message ID
         *      $nick = author's nickname
         *      $hint = message hint string (as it is, cutcuts will be applied below)
         *      $fid = forum ID
         *      $tid = thread ID
         *
         */

        $hint = cutcuts (str_replace ('&;', blank, $hint));     // blank-out any newlines

        /*
         *
         *      building array of message IDs representing recorded recent posts so far:
         *
         *            - load current recent discussions archive, build an array ($rd_a) out of its
         *              newline-delimited records, append a new record, assembling the arguments
         *              provided to this function, implode and write the resulting array back
         *
         */

        lockSession ();

        $rd_a = all ('forums/recdisc', asIs);                   // get archived records
        $rd_a[] = "$f>$t>$m>$nick>$epc>$hint>$fid>$tid";        // add new record

        /*
         *
         *      ah, yes, there's a maximum amount of records to be held in the recent dicussions'
         *      archive, and it's also important, because there must be the possibility to mark
         *      all of them as read, by storing IDs of read messages in the VRD table, which is
         *      managed with functions declared later; if one or more records remained there in
         *      excess of $recdiscrecords (as declared in 'settings.php'), the corresponding posts
         *      would never get to be marked as read, and continuously highlight their thread and
         *      forum entries as holding unread posts... which would be very annoying; and well,
         *      the VRD table is a random-access archive with a fixed amount of fields per record,
         *      and holding one record per member: it couldn't hold more than $recdiscrecords IDs
         *
         */

        $rd_a = array_slice ($rd_a, - $recdiscrecords, $recdiscrecords);

        /*
         *
         *      implode and write-back
         *
         */

        asm ('forums/recdisc', $rd_a, asIs);

}

/*
 *
 *      forum restrictions' checks:
 *      these are very important!
 *
 *      only admins may ACCESS (as in posting, editing, deleting etc) locked forums,
 *      but locked forums may be SEEN and browsed by everyone; admins and moderators
 *      may SEE and ACCESS "trashed" forums but regular members don't even SEE them,
 *      nor must be given any hints about their existence: that way, a trashed forum
 *      makes a good "secret discussion place"; something written in a trashed forum
 *      does NOT show in the recdisc archive, or in searchs, and error messages that
 *      concern it will not be of the "access denied" kind but deny existence of the
 *      hidden forum
 *
 */

function may_access ($forum_record) {

        global $is_admin;
        global $is_mod;

        /*
         *
         *      $is_admin and $is_mod are globally declared in "settings.php", both with the
         *      default value of false; they will be set to true by later code in this same
         *      script, upon verifying that a session exists (or upon building a session),
         *      and attributing appropriate access rights depending on its 'rank' field (see
         *      the $auth field of 'profile.php', and the arrays $admin_ranks and $mod_ranks)
         *
         */

        $is_forum_locked = (valueOf ($forum_record, 'islocked') == 'yes') ? true : false;
        $is_forum_hidden = (valueOf ($forum_record, 'istrashed') == 'yes') ? true : false;

        if (($is_forum_locked == true) && ($is_admin == false)) {

                return (false);

        }

        if (($is_forum_hidden == true) && ($is_admin == false) && ($is_mod == false)) {

                return (false);

        }

        return (true);

}

function may_see ($forum_record) {

        global $is_admin;
        global $is_mod;

        /*
         *
         *      $is_admin and $is_mod are globally declared in "settings.php", both with the
         *      default value of false; they will be set to true by later code in this same
         *      script, upon verifying that a session exists (or upon building a session),
         *      and attributing appropriate access rights depending on its 'rank' field (see
         *      the $auth field of 'profile.php', and the arrays $admin_ranks and $mod_ranks)
         *
         */

        $is_forum_hidden = (valueOf ($forum_record, 'istrashed') == 'yes') ? true : false;

        if (($is_forum_hidden == true) && ($is_admin == false) && ($is_mod == false)) {

                return (false);

        }

        return (true);

}

/*
 *
 *      links processing: depends on the preference to have the left frame follow the navigation,
 *      when active it will cause, in most cases, both frames to be reloaded, so that in the left
 *      frame the most common operation is shown; this is accomplished by providing a link that
 *      targets the 'pag' frame, the container of the "central frameset"; the "central frameset",
 *      on itself, is composed of the left and right frames, respectively called 'pst' and 'pag'
 *
 */

function link_to_intro ($target) {

        global $lffn;   // preference: Left Frame Follows Navigation
        global $aclf;   // preference: Always Collapse Left Frame

        /*
         *
         *      $r is set to what's to be loaded into the right frame (the intro page)
         *
         */

        $r = 'intro.php';

        /*
         *
         *      either if left frame must follow, or if it's to be collapsed at every page load,
         *      the script 'intro.php' must be loaded by loading 'index.php' in the parent frame
         *      of this link (which is assumed to be the 'pag' frame, the central sub-frameset)
         *
         */

        if (($lffn == 'yes') || ($aclf == 'yes')) {

                /*
                 *
                 *      if left frame follows navigation, the appropriate page to load in the left
                 *      frame along with 'intro' is 'side.php?si=intro'; otherwise, it means this
                 *      piece of code was entered because 'aclf' was set to 'yes', meaning that the
                 *      left frame must be always collapsed: will so be the browser's conventional
                 *      blank page refered as 'about:blank', because if the frame is collapsed, it
                 *      wouldn't make sense to load something there (once collapsed, the left frame
                 *      is completely invisible)
                 *
                 */

                $r = base62Encode ($r);
                $l = ($lffn == 'yes')

                        ? base62Encode ('side.php?si=intro')
                        : base62Encode ('about:blank');

                return ("target=\"_parent\" href=\"index.php?l=$l&amp;r=$r\"");

        }

        /*
         *
         *      if none of the above (no lffn, no aclf) the page is loaded in the current frame,
         *      unless differently stated via the $target argument to this function, which is
         *      taken into account only if it's not a void string
         *
         */

        $target = ($target == voidString) ? voidString : "target=\"$target\"" . blank;

        return ($target . "href=\"$r\"");

}

function link_to_forums ($target) {

        global $lffn;   // preference: Left Frame Follows Navigation
        global $aclf;   // preference: Always Collapse Left Frame

        /*
         *
         *      $r is set to what's to be loaded into the right frame (the forums index)
         *
         */

        $r = 'forums.php';

        if (($lffn == 'yes') || ($aclf == 'yes')) {

                /*
                 *
                 *      if left frame follows navigation, the appropriate page to load in the left
                 *      frame along with 'intro' is 'recdisc.php'; otherwise, it means this
                 *      piece of code was entered because 'aclf' was set to 'yes', meaning that the
                 *      left frame must be always collapsed: will so be the browser's conventional
                 *      blank page refered as 'about:blank', because if the frame is collapsed, it
                 *      wouldn't make sense to load something there (once collapsed, the left frame
                 *      is completely invisible)
                 *
                 */

                $r = base62Encode ($r);
                $l = ($lffn == 'yes')

                        ? base62Encode ('recdisc.php')
                        : base62Encode ('about:blank');

                return ("target=\"_parent\" href=\"index.php?l=$l&amp;r=$r\"");

        }

        /*
         *
         *      if none of the above (no lffn, no aclf) the page is loaded in the current frame,
         *      unless differently stated via the $target argument to this function, which is
         *      taken into account only if it's not a void string
         *
         */

        $target = ($target == voidString) ? voidString : "target=\"$target\"" . blank;

        return ($target . "href=\"$r\"");

}

function link_to_threads ($target, $f, $p) {

        global $lffn;   // preference: Left Frame Follows Navigation
        global $aclf;   // preference: Always Collapse Left Frame

        /*
         *
         *      $r is set to what's to be loaded into the right frame (the list of threads in
         *      forum $f) with an eventual page number appended as an argument for 'threads.php'
         *
         */

        $r = "threads.php?f=$f" . (($p == voidString) ? voidString : "&amp;p=$p");

        if (($lffn == 'yes') || ($aclf == 'yes')) {

                /*
                 *
                 *      if left frame follows navigation, the appropriate page to load in the left
                 *      frame along with a page holding the posts of a thread is 'postform.php'
                 *      with the $f argument passed to present a form to post a new thread in that
                 *      forum; otherwise, it means this piece of code was entered because 'aclf'
                 *      was set to 'yes', meaning that the left frame must be always collapsed: it
                 *      will so be the browser's conventional blank page refered as 'about:blank',
                 *      because if the frame is collapsed, it wouldn't make sense to load something
                 *      there (collapsed, the left frame is completely invisible)
                 *
                 */

                $r = base62Encode ($r);
                $l = ($lffn == 'yes')

                        ? base62Encode ("postform.php?f=$f")
                        : base62Encode ('about:blank');

                return ("target=\"_parent\" href=\"index.php?l=$l&amp;r=$r\"");

        }

        /*
         *
         *      if none of the above (no lffn, no aclf) the page is loaded in the current frame,
         *      unless differently stated via the $target argument to this function, which is
         *      taken into account only if it's not a void string
         *
         */

        $target = ($target == voidString) ? voidString : "target=\"$target\"" . blank;

        return ($target . "href=\"$r\"");

}

function link_to_posts ($target, $t, $p, $m, $disamb) {

        global $lffn;   // preference: Left Frame Follows Navigation
        global $aclf;   // preference: Always Collapse Left Frame
        global $epc;    // current epoch, used for URL disambiguation

        /*
         *
         *      note about the disambiguation code:
         *
         *      it's not a real argument, and no Postline script uses it, it's completely ignored,
         *      but its purpose is that of making a browser believe it's going to load a different
         *      page, and force it to effectively make a request to the server; it is necessary to
         *      pass a value of 'true' as $disamb when this function is supposed to assemble a link
         *      to review edited messages: if the URL wasn't disambiguated, certain browsers would
         *      believe to be requested to re-load a page that's already loaded in the given frame,
         *      which would most probably have the old version of the message still stored under a
         *      same exact URL; disambiguating allows, in short, to see changes made to the edited
         *      message: without disambiguation, browsers wouldn't reload the same URL in the same
         *      frame, believing it to be completely useless...
         *
         */

        $r  = "posts.php?t=$t";                               // basic URI of the given thread
        $r .= (empty ($p)) ? '' : "&amp;p=$p";                // page within the thread, if given
        $r .= ($disamb == false) ? '' : "&amp;disamb=$epc";   // disambiguation code, if necessary
        $r .= (empty ($m)) ? '' : "#$m";                      // message ID (anchor name) if given

        if (($lffn == 'yes') || ($aclf == 'yes')) {

                /*
                 *
                 *      if left frame follows navigation, the appropriate page to load in the left
                 *      frame along with a page holding the posts of a thread is 'postform.php'
                 *      with the $t argument passed to present a form to post a new message in that
                 *      thread; otherwise, it means this piece of code was entered because 'aclf'
                 *      was set to 'yes', meaning that the left frame must be always collapsed: it
                 *      will so be the browser's conventional blank page refered as 'about:blank',
                 *      because if the frame is collapsed, it wouldn't make sense to load something
                 *      there (collapsed, the left frame is completely invisible)
                 *
                 */

                $r = base62Encode ($r);
                $l = ($lffn == 'yes')

                        ? base62Encode ("postform.php?t=$t")
                        : base62Encode ('about:blank');

                return ("target=\"_parent\" href=\"index.php?l=$l&amp;r=$r\"");

        }

        /*
         *
         *      if none of the above (no lffn, no aclf) the page is loaded in the current frame,
         *      unless differently stated via the $target argument to this function, which is
         *      taken into account only if it's not a void string
         *
         */

        $target = ($target == voidString) ? voidString : "target=\"$target\"" . blank;

        return ($target . "href=\"$r\"");

}

/*
 *
 *      Templates processing: visible PHP scripts have intended templates for their HTML output.
 *      The layout is saved to an html file in the 'layout/html' folder, having the same name of
 *      the PHP script it belongs to. That html file is read by the script, and its contents are
 *      saved in a keyed array under the 'value' key. Another entry of the same array, under the
 *      key 'inner_variables', holds the result of executing the 'process_template' function for
 *      the template file's contents. That result is an array, as well, holding two keys labeled
 *      again 'value' and 'inner_variables', for each and every variable marked in the template.
 *      These variables may be organized in a tree-like structure, later called 'hive', which is
 *      recursively split into value + inner_variables couples by the same 'process_template'.
 *      Ahem... in short, the following example clearly explains what to do with this function:
 *
 *          $em['N/A'] = 'TEMPLATE FILE NOT AVAILABLE';
 *          $ex['N/A'] = 'Could not access the html template intended for this script!';
 *
 *              $html = file_get_contents ('layout/html/SCRIPTNAME.html') or die (because ('N/A'));
 *              $html = array ('value' => $html, 'inner_variables' => process_template ($html));
 *
 *      the final $html array is uniformly made of value + inner_variables pairs, where the root
 *      of the tree is the whole template file, as a string (value) and as an array of variables
 *      found within it (inner_variables). But then, each variable has an initial content as its
 *      'value' key, and optionally an array of further 'inner_variables', hrm... see:
 *
 *              $html['value'] = WHOLE FILE CONTENTS
 *              $html['inner_variables']['H_LIST']['value'] = CONTENTS OF H_LIST HTML PART
 *              $html['inner_variables']['H_LIST']['inner_variables']['P_COLOR']['value'] =
 *                      initial value of the P_COLOR property within the H_LIST part
 *
 *      The subsequent function 'process_hive' would then replace the inner variables of a given
 *      variable (and of the whole template if $html is passed as its first argument) with their
 *      corresponding contents, and create the HTML output if used on the root node, e.g.
 *
 *              $html_output = process_hive
 *
 *                      (
 *
 *                              $html,
 *
 *                              array
 *
 *                                      (
 *
 *                                              'H_CLOCK'     => gmdate ('g:i A'),
 *                                              'H_LIST'      => $h_list
 *
 *                                      )
 *
 *                      );
 *
 *      where $h_list might have been assembled as a concatenation of strings made out of a same
 *      pattern, or sub-template, or "way to dispose some properties inside an HTML model called
 *      H_LIST", as in the following example where the sub-template is called $t_list_entry:
 *
 *          $t_list_entry = $html['inner_variables']['H_LIST']['inner_variables']['T_LIST_ENTRY'];
 *          $h_list =
 *
 *              process_hive ($t_list_entry, array ('P_NAME' => 'George', 'P_COLOR' => 'red'))
 *            . process_hive ($t_list_entry, array ('P_NAME' => 'Michael', 'P_COLOR' => 'grey'))
 *            . process_hive ($t_list_entry, array ('P_NAME' => 'Adam', 'P_COLOR' => 'black'));
 *
 *      and so on. The above example only builds an H_LIST as the concatenation of three strings
 *      based on sub-template 'T_LIST_ENTRY' of 'H_LIST', but of course that should be made by a
 *      loop concatenating any number of entries. In practice, 'H_LIST' marks the spot where the
 *      list will be rendered, while 'T_LIST_ENTRY' defines the appearence of a list entry where
 *      the two properties, as strings, corresponding to 'P_NAME' and 'P_COLOR', will be in turn
 *      replaced by the relevant data about each entry. What might really clarify this should be
 *      looking at html files in the 'layout/html' folder which names correspond to a PHP script
 *      (e.g. 'layout/html/downline.html') of the parent Postline folder: in files you'll easily
 *      understand how inner variables are marked. This markup is designed to be compatible with
 *      HTML and CSS, where variable names appear as harmless comments, consequentially allowing
 *      template designers to see how the layout looks like by using a "dummy" initial value for
 *      each variable.
 *
 */

$processed_variables = array ();
$ppt_level = -1;

function process_template ($html) {

        global $processed_variables;
        global $ppt_level;

        /*
         *
         *      this is recursive, but needs to be called for the "root node" only of a template,
         *      and automatically insulates sub-templates' variables building an array of arrays;
         *      notice that it needs each variable to have unique names, throughout the template,
         *      despite the level at which it appears (to allow "forward references" to variables
         *      names); $ppt_level is a global variable that is initialized at minus 1 because it
         *      gets incremented right below to be decremented on return from each function call;
         *      in nested calls (in the loop when this function calls itself) $ppt_level reflects
         *      the nesting level, or depth of the "tree" at that call's node; upon completion it
         *      returns to its initial value of -1, eventually allowing 'process_template' to get
         *      called again, although it shouldn't be necessary unless two or more templates may
         *      be processed by a same script; see above notes for usage informations
         *
         */

        $variables = array ();
        $processed_variables[++ $ppt_level] = array ();

        preg_match_all ('/(\<\!\-\-|\/\*\-\-)(\w+)?(\-\-\>|\-\-\*\/)/', $html, $match);

        foreach ($match[2] as $m) {

                $process_this_variable = ($m[0] == '/') ? false : true;
                $process_this_variable = (isset ($processed_variables[$ppt_level][$m]))

                        ? false                         // because already processed at this level
                        : $process_this_variable;       // not yet processed at this level

                if ($process_this_variable) {

                        for ($i = 0; $i <= $ppt_level; ++ $i) {

                                $processed_variables[$i][$m] = true;

                        } // setting flags at all levels to indicate this name was processed

                        $match_value = strFromTo ($html, "<!--$m-->", "<!--/$m-->");
                        $match_value = ($match_value == voidString)

                                ? strFromTo ($html, "/*--$m--*/", "/*--/$m--*/")        // in CSS
                                : $match_value;                                         // in HTML

                        $variables[$m]['value'] = $match_value;
                        $variables[$m]['inner_variables'] = process_template ($match_value);

                } // match tag was of opening type (no initial '/') and was not processed before

        } // each matching pair of either <!--[/](...)--> or /*--[/](...)--*/

        -- $ppt_level;

        return ($variables);

}

function process_hive ($hive, $vals) {

        /*
         *
         *      processes a "hive" of the template array built by 'process_template'; each hive has
         *      a value string, determining the disposition of its contents, and a set of variables
         *      appearing in that string, that will be replaced by entries of the $vals array which
         *      keys match the keys of the hive's 'inner_variables' array; since an initial call to
         *      the 'process_template' function is made to define a compatible array for the entire
         *      template, this function can then be used to generate the HTML output of either that
         *      resulting main template array (from the first call) or of each "sub-template" given
         *      as an inner variable of the main template or of one of its variables; see the notes
         *      introducing this section for more informations (unfortunately I'm constantly trying
         *      to explain this whole templating process since half an hour and I'm still aware the
         *      explanation might sound veeeeery confusing; my apologies)
         *
         */

        $h = $hive['value'];

        if (is_array ($hive['inner_variables'])) {

                foreach ($hive['inner_variables'] as $m => $iv) {

                        $value_given = (isset ($vals[$m])) ? true : false;

                        if ($value_given) {

                                $h = strReplaceFromTo ($h, "<!--$m-->", "<!--/$m-->", $vals[$m]);
                                $h = strReplaceFromTo ($h, "/*--$m--*/", "/*--/$m--*/", $vals[$m]);

                        } // value was given, and corresponding part replaced

                        else {

                                $h = strReplaceFromTo ($h, "<!--$m-->", "<!--/$m-->", '');
                                $h = strReplaceFromTo ($h, "/*--$m--*/", "/*--/$m--*/", '');

                        } // value was not given, and corresponding part replaced by a void string

                } // each hive's inner variable, as a matching key and its own inner variables

        } // values were given (allow specifying 'null', for example, to signify there's no values)

        return ($h);

}

/*
 *
 *      major age promotion check clears ignorelist when member identified by $id reachs major age:
 *      function returns true if member is major (or has just been promoted by this function call)
 *
 */

function majoragepromotioncheck ($id) {

        global $bs_members;
        global $epc;
        global $majoragedays;
        global $majorageposts;

        /*
         *
         *      locate DB file holding member record, read the record
         *
         */

        $mpc_db = 'members' . intervalOf (intval ($id), $bs_members);
        $record = get ($mpc_db, "id>$id", wholeRecord);

        /*
         *
         *      compute n. of seconds between now and the moment the member registered an account,
         *      compute n. of replies and thread starter messages posted in the forums
         *
         */

        $mj_age = $epc - intval (valueOf ($record, 'reg'));
        $mj_pct = intval (valueOf ($record, 'posts')) + intval (valueOf ($record, 'threads'));

        if (($mj_age >= $majoragedays * 86400) && ($mj_pct >= $majorageposts)) {

                /*
                 *
                 *      if major age conditions have been reached, check if member is already
                 *      ranked as major: if not, promote member, by setting '<major>yes' in
                 *      the account's record, and then clear the ignorelist associated to this
                 *      member; the ignorelist will need to be rebuilt by the member, but from
                 *      now on, Postline will consider accounts ignored by this member as the
                 *      possible targets of a "majority ban"
                 *
                 */

                $major_already = (valueOf ($record, 'major') == 'yes') ? true : false;

                if ($major_already == false) {

                        /*
                         *
                         *      ok to promote:
                         *      lock session and verify survival of target account record
                         *
                         */

                        lockSession ();

                        $record = get ($mpc_db, "id>$id", wholeRecord);
                        $exists = ($record == voidString) ? false : true;

                        if ($exists == false) {

                                /*
                                 *
                                 *      something happened to the member account while session was
                                 *      not locked: the record can't be read anymore (was deleted?
                                 *      member resigned?)
                                 *
                                 */

                                return (false);

                        } // member couldn't be promoted because account no longer exists

                        else {

                                /*
                                 *
                                 *      set major age flag, clear ignore list
                                 *
                                 */

                                set ($mpc_db, "id>$id", 'major', 'yes');
                                set ($mpc_db, "id>$id", 'ignorelist', deleteField);

                                /*
                                 *
                                 *      announce this nice event into persistent chat logs
                                 *
                                 */

                                $mpc_nick = valueOf ($record, 'nick');
                                logwr ("&quot;$mpc_nick&quot; reached major age!", lw_persistent);

                        } // member promoted successfully

                } // member reached major age and was not yet promoted, so it's been promoted

                return (true);

        } // major age reached (both posts count and registration date checked)

        return (false);

}

/*
 *
 *      updating ignorecounts on deletion/resign/banning of a major member: this function must
 *      update all ignored members accounts to reflect the absence of the major member that no
 *      longer possesses an account for the boards; it can be called, however, for a non-major
 *      member as well: the function will however determine "major age" status before updating
 *      any ignorecounts of ignored accounts; note that "majority ban" will not be released if
 *      the ignorecount drops below the threshold... it can only be reversed by administrators
 *
 */

function clear_mm_ignorelist ($id) {

        global $bs_members;

        /*
         *
         *      locate DB file holding member record, get major age flag from it:
         *      existence of a 'yes' flag in the 'major' field ensures record exists and is major
         *
         */

        $cmi_db = 'members' . intervalOf ($id, $bs_members);
        $cmi_mj = get ($cmi_db, "id>$id", 'major');
        $i_list = wExplode (';', get ($cmi_db, "id>$id", 'ignorelist'));

        if (($cmi_mj == 'yes') && (count ($i_list) > 0)) {

                /*
                 *
                 *      member is effectively major and ignorelist contains one or more ignored
                 *      members: lock session, verify survival of target record conditions
                 *
                 */

                $cmi_mj = get ($cmi_db, "id>$id", 'major');
                $i_list = wExplode (';', get ($cmi_db, "id>$id", 'ignorelist'));

                if (($cmi_mj == 'yes') && (count ($i_list) > 0)) {

                        /*
                         *
                         *      update each ignored member record by decreasing the corresponding
                         *      ignore count: older ignore lists may contain nicknames that no
                         *      longer exists, so this code will check for such a situation; if it
                         *      happens, those ignorelist entries are ignored...
                         *
                         */

                        foreach ($i_list as $inick) {

                                $iid = get ('members/bynick', "nick>$inick", 'id');
                                $iid_exists = ($iid == voidString) ? false : true;

                                if ($iid_exists) {

                                        $idb = 'members' . intervalOf ($iid, $bs_members);
                                        $iic = intval (get ($idb, "id>$iid", 'ignorecount')) - 1;

                                        if ($iic > 0) {

                                                /*
                                                 *
                                                 *      well, yes, it should never happen that an
                                                 *      ignorecount drops into negative values, but
                                                 *      to improve safety do not allow such an
                                                 *      inconsistence to survive in the database:
                                                 *      write new ignorecount only if it's > 0...
                                                 *
                                                 */

                                                set ($idb, "id>$iid", 'ignorecount', $iic);

                                        } // decremented ignorecount was positive and was updated

                                        else {

                                                /*
                                                 *
                                                 *      ...otherwise, normalize it to zero,
                                                 *      or even better: delete the field completely
                                                 *
                                                 */

                                                 set ($idb, "id>$iid", 'ignorecount', deleteField);

                                        } // decremented ignorecount was negative and was deleted

                                } // ignored member's account ID exists (the record isn't checked)

                        } // each ignored nickname

                        /*
                         *
                         *      clear the ignorelist:
                         *
                         *      most probably, this function was called just before entirely
                         *      deleting the record corresponding to this account; however,
                         *      it is not the responsibility of this function to delete that
                         *      record, and the function can't assume it will be deleted in
                         *      any cases, so it updates the record with a void ignorelist to
                         *      make it consistent with modified ignorecounts of the formerly
                         *      ignored members
                         *
                         */

                        set ($cmi_db, "id>$id", 'ignorelist', deleteField);

                } // record still exists after locking, and still holds a list of ignored nicknames

        } // record existed and held a list of ignored nicknames before locking the session

}

/*
 *
 *      dispatching internal communications (intercom calls triggered by posts in hidden forums):
 *      called with $m as the ID of the message to dispatch
 *
 */

function dispatch_intercom_call ($m) {

        global $nick;
        global $bs_members;

        lockSession ();

        /*
         *
         *      begin by including the community manager (account #1) in the recipients' array
         *
         */

        $mnick = get ('members' . intervalOf (1, $bs_members), 'id>1', 'nick');
        $intercom_recipients = array ($mnick);

        /*
         *
         *      add all the admins:
         *
         *      note this list may not always be up-to-date: it is generated every 24 hours
         *      and only in consequence of a request for the page 'authlist.php' to be made
         *      by any visitors, but it's likely to happen often enough for the (non-vital)
         *      purpose of this function...
         *
         */

        $admins = all ('members/adm_list', asIs);
        $intercom_recipients = array_merge ($intercom_recipients, $admins);

        /*
         *
         *      add all the moderators:
         *      same considerations apply as for the admins list
         *
         */

        $moders = all ('members/mod_list', asIs);
        $intercom_recipients = array_merge ($intercom_recipients, $moders);

        /*
         *
         *      dispatch call to all recipients (that appear to still exist in the database),
         *      except to the member that actually triggered the call (given by global $nick)
         *
         */

        $intercom_recipients = wArrayDiff ($intercom_recipients, array ($nick));

        foreach ($intercom_recipients as $r) {

                $id = get ('members/bynick', "nick>$r", 'id');
                $id_exists = ($id == voidString) ? false : true;

                if ($id_exists) {

                        $id = intval ($id);
                        set ('members' . intervalOf ($id, $bs_members), "id>$id", 'intercom', strval ($m));

                } // recipient still exists

        } // each recipient of an intercom call

}

/*
 *
 *      get/set VRD (Viewed Recent Discussions) record for given member ID:
 *
 *      the VRD table is a random-access archive holding one record per member and upto a maximum
 *      of $vrdtablerecords per member record; $vrdtablerecords is declared in 'settings.php' and
 *      might be slightly greater than $recdiscrecords, to allow all records in the actual recent
 *      discussions' archive to be marked as read: it might be slightly greater as it should take
 *      eventual deleted messages into account, since deleted message IDs can be removed from the
 *      recent discussions' archive, but not from the VRD table of each member (it would take too
 *      much time to do that on each and every message deletion): as such, the difference between
 *      $vrdtablerecords and $recdiscrecords is the number of messages that is possible to delete
 *      before the whole "unread message marking" mechanism begins to show transient malfunctions
 *
 *      note:
 *      a random-access archive might present no risk of concurrent execution problems,
 *      so locking the session is completely unnecessary while updating these tables...
 *
 */

function getvrd ($id) {

        global $vrdtablerecords;

        $id = intval ($id);

        if ($id <= 0) {

                /*
                 *
                 *      it can be generally dangerous to tell a file system to seek to a negative
                 *      position, so the function checks $id to be positive and not null before
                 *      doing its duties: if it's negative or null, it does nothing and returns a
                 *      void array
                 *
                 */

                return (array ());

        }

        /*
         *
         *      open VRD table, seek to member record, read record, close file
         *
         */

        $hFile = @fopen ('tables/vrd', 'rb');
        $error = ($hFile === false) ? true : false;

        if ($error) {

                $r = voidString;

        } // file didn't open, probably this table doesn't exist yet, content set to void

        else {

                fseek ($hFile, 4 * ($id - 1) * $vrdtablerecords);
                $r = fread ($hFile, 4 * $vrdtablerecords);
                fclose ($hFile);

        } // VRD table record read successfully

        /*
         *
         *      split record in fragments of 4 characters, each being a base-62 encoded message ID
         *
         */

        preg_match_all ("/.{4}/", $r, $match);

        /*
         *
         *      assemble VRD array to be returned to caller, by including in the array any of the
         *      above 4-digit fragments that is not void (records and IDs are padded by '=' signs)
         *
         */

        $vrd = array ();

        foreach ($match[0] as $mid) {

                $mid = convertFromBase62 (ltrim ($mid, '='));

                if ($mid > 0) {

                        $vrd[] = $mid;

                } // VRD record was not void ($mid != '====')

        } // each VRD record

        return ($vrd);

}

function setvrd ($id, $vrd) {

        global $vrdtablerecords;

        $id = intval ($id);

        if ($id <= 0) {

                /*
                 *
                 *      it can be generally dangerous to tell a file system to seek to a negative
                 *      position, so the function checks $id to be positive and not null before
                 *      doing its duties: if it's negative or null, it does nothing
                 *
                 */

                return (array ());

        }

        /*
         *
         *      clip given array to fit record, removing extra entries from the array's tail;
         *      count the entries and eventually insert padding 0's as message IDs of void entries
         *
         */

        $vrd = array_slice ($vrd, 0, $vrdtablerecords);
        $c = count ($vrd);

        while ($c < $vrdtablerecords) {

                $vrd[] = 0;

                ++ $c;

        }

        /*
         *
         *      open VRD table in read/write mode:
         *      if this fails, try creating, if creation also fails, return and do nothing
         *
         */

        $hFile = @fopen ('tables/vrd', 'rb+');
        $error = ($hFile === false) ? true : false;

        if ($error) {

                $hFile = @fopen ('tables/vrd', 'wb');
                $error = ($hFile === false) ? true : false;

                if ($error) {

                        return;

                }

        }

        /*
         *
         *      assemble VRD table record for member $id, as the string resulting from merging all
         *      message IDs in the $vrd array, being each converted to base 62 and padded with '='
         *      signs on the left if shorter than 4 digits; give resulting record string to $r
         *
         */

        $r = voidString;

        foreach ($vrd as $mid) {

                $r .= str_pad (convertToBase62 (intval ($mid)), 4, '=', STR_PAD_LEFT);

        }

        /*
         *
         *      disable write buffering, seek to member record, write member record, close file
         *
         */

        set_file_buffer ($hFile, 0);
        fseek ($hFile, 4 * ($id - 1) * $vrdtablerecords);
        fwrite ($hFile, $r);
        fclose ($hFile);

}

/*
 *
 *      get/set LSP (Last Seen Page) record for given member ID:
 *
 *      there are 100 characters per record, as a maximum right-frame URI length; the URI is then
 *      taken by looking up the $_SERVER['REQUEST_URI'] variable at the end of each right-frame
 *      script (for e.g. see code near the end of 'forums.php'); the purpose of this tracking is
 *      mostly that of allowing 'onlines.php' to report informations on what each member is doing
 *
 */

function getlsp ($id) {

        $id = intval ($id);

        if ($id <= 0) {

                /*
                 *
                 *      it can be generally dangerous to tell a file system to seek to a negative
                 *      position, so the function checks $id to be positive and not null before
                 *      doing its duties: if it's negative or null, it returns a void string
                 *
                 */

                return (voidString);

        }

        /*
         *
         *      open LSP table, seek to member record, read record, close file
         *
         */

        $hFile = @fopen ('tables/lsp', 'rb');
        $error = ($hFile === false) ? true : false;

        if ($error) {

                return (voidString);

        }

        fseek ($hFile, 100 * ($id - 1));
        $r = fread ($hFile, 100);
        fclose ($hFile);

        /*
         *
         *      trim padding "greater than" signs from the right end of the string, return string
         *
         */

        return (rtrim ($r, '>'));

}

function setlsp ($id, $lsp) {

        $id = intval ($id);

        if ($id <= 0) {

                /*
                 *
                 *      it can be generally dangerous to tell a file system to seek to a negative
                 *      position, so the function checks $id to be positive and not null before
                 *      doing its duties: if it's negative or null, it does nothing
                 *
                 */

                return;

        }

        /*
         *
         *      clip given string to fit record,
         *      append padding "greater than" signs if shorter than 100 characters
         *
         */

        $r = substr ($lsp, 0, 100);
        $r = str_pad ($r, 100, '>');

        /*
         *
         *      open LSP table in read/write mode:
         *      if this fails, try creating, if creation also fails, return and do nothing
         *
         */

        $hFile = @fopen ('tables/lsp', 'rb+');
        $error = ($hFile === false) ? true : false;

        if ($error) {

                $hFile = @fopen ('tables/lsp', 'wb');
                $error = ($hFile === false) ? true : false;

                if ($error) {

                        return;

                }

        }

        /*
         *
         *      disable write buffering, seek to member record, write member record, close file
         *
         */

        set_file_buffer ($hFile, 0);
        fseek ($hFile, 100 * ($id - 1));
        fwrite ($hFile, $r);
        fclose ($hFile);

}

/*
 *
 *      page request security code management (Security Code Table, SCT):
 *
 *      random security codes are added to all requests that must be confirmed, such as form
 *      submissions, to avoid the possibility to induce people to do something that they did
 *      NOT want to really do, by simply letting them click on a link that leads to Postline
 *      scripts that could eventually execute some dangerous request, without the user being
 *      aware of that (if this was triggered by javascripts you wouldn't even need to click)
 *
 *            - in both the following functions, $context is a string added to the file name
 *              of the accessed table file (e.g. 'forms' would select 'tables/sct_forms' for
 *              storing and retrieving codes); this allows having multiple such tables which
 *              prevent conflicts while doing different common things at the same time, like
 *              in case of the problem of version 6.1.30, where chatting after having loaded
 *              a form invalidated the form's code and compelled to submit it twice; yet, it
 *              should be chosen among a very few contexts (actually, only two: one for chat
 *              phrases, 'chats', and the other for all forms, 'forms'), otherwise, creation
 *              of many such tables would eat up valuable server disk space
 *
 *            - be aware these codes are not in any way a "mission critical" safety measure,
 *              although exploiting them is probably quite difficult; they are randomized by
 *              the Mersenne Twister as implemented by PHP, which is likely initialized with
 *              some derivative of the page request's time; the PRNG random number sequences
 *              in question are theoretically predictable, but practically, I presume such a
 *              code would have to be guessed among many alternatives, unless attacker knows
 *              in advance the exact moment in which the page request will be made (which is
 *              likely possible if the request is in some way automated, but... whoa, you've
 *              got to be strongly motivated); so far I'm keeping these to prevent a "naive"
 *              exploit like forging a link, for the future... we'll see if it's worth doing
 *              something better; if the reader wants to improve this, Postline is GPL'ed so
 *              feel absolutely free to
 *
 */

function getcode ($context) {

        global $login;
        global $id;

        /*
         *
         *      check login validity:
         *      return an impossible code if nobody's logged in for this run
         *
         */

        if (($login == false) || ($id < 1)) {

                return (2000000001);

        }

        /*
         *
         *      open SCT, seek to member record, read record, close file
         *
         */

        $hFile = @fopen ('tables/sct_' . $context, 'rb');
        $error = ($hFile === false) ? true : false;

        if ($error) {

                $r = voidString;

        } // file didn't open, probably this table doesn't exist yet, content set to void

        else {

                fseek ($hFile, 10 * ($id - 1));
                $r = fread ($hFile, 10);
                fclose ($hFile);

        } // SCT record read successfully

        /*
         *
         *      return code if present, else return an impossible value for the code
         *
         */

        $code = intval ($r);
        return ((($code >= 1000000000) && ($code <= 2000000000)) ? $code : 2000000001);

}

function setcode ($context) {

        global $login;
        global $id;

        /*
         *
         *      check login validity:
         *
         *      code will be null if an error occurs, because zero is both an impossible value
         *      for the code, and it's different from the impossible value returned by getcode
         *      and so it would *not* match, if there was an error in both setcode and getcode
         *
         */

        if (($login == false) || ($id < 1)) {

                return (0);

        }

        /*
         *
         *      open SCT in read/write mode: if this fails, try creating,
         *      if creation also fails, return zero and do nothing
         *
         */

        $hFile = @fopen ('tables/sct_' . $context, 'rb+');
        $error = ($hFile === false) ? true : false;

        if ($error) {

                $hFile = @fopen ('tables/sct_' . $context, 'wb');
                $error = ($hFile === false) ? true : false;

                if ($error) {

                        return (0);

                }

        }

        /*
         *
         *      extract security code between 1 and 2 billions (such that it's always 10 digits)
         *
         */

        $code = mt_rand (1000000000, 2000000000);

        /*
         *
         *      disable write buffering, seek to member record, write security code, close file
         *
         */

        set_file_buffer ($hFile, 0);
        fseek ($hFile, 10 * ($id - 1));
        fwrite ($hFile, $code);
        fclose ($hFile);

        /*
         *
         *      return the new code, that will be added to forms and expected on forms' submission
         *
         */

        return ($code);

}

/*
 *
 *      kick: temporarily kicks out an IP address
 *
 *      adds the IP to a list of upto 10 temporarily blocked IP addresses, which removes oldest IPs
 *      while new addresses get kicked; the list, mantained in 'stats/counters', will be checked
 *      upon a login or registration attempt made from an IP that appears in the list, so the two
 *      pieces of code checking this list are placed in the 'mstats.php' script where it accepts
 *      the login by means of nickname and password, or withelds registration of new accounts from
 *      kicked IP addresses; scripts that trigger the following function are 'kick.php' (the /kick
 *      command) and the Community Defense Panel (defense.php) when it comes to update the list of
 *      banned members; "kickout delays" are controlled by two values, both of which are influenced
 *      by presets in 'settings.php'; in particular:
 *
 *            - $maxkickdelay determines the maximum amount of time (given in minutes to 'kick.php'
 *              but specified in seconds as $maxkickdelay) that someone could be kicked out for
 *
 *            - $maxkickedips and $ipkickdelay regulate the behavior of this function, in that the
 *              function and its checkpoints in 'mstats.php' would consider a record to be expired
 *              if it was stored (kicked) before $ipkickdelay seconds ago (default = 1 day) and in
 *              that the function complessively allows $maxkickedips records to be recorded in the
 *              relevant list (which resides in 'stats/counters', and might not be allowed to grow
 *              indefinitely longer, or else be moved to a dedicated file)
 *
 */

function kick ($target_ip) {

        global $maxkickedips;
        global $ipkickdelay;
        global $epc;

        /*
         *
         *      get existing kicks as an array of upto $maxkickedips IP addresses
         *
         */

        $kx = wExplode (';', get ('stats/counters', 'counter>kicks', 'list'));
        $ki = array ();
        $kt = array ();

        foreach ($kx as $iprecord) {

                list ($ip, $time) = explode ('@', $iprecord);

                $ki[] = $ip;
                $kt[] = $time;

        } // exploding ip address and time from each ip record

        $already_kicked = (in_array ($target_ip, $ki)) ? true : false;

        if ($already_kicked == false) {

                lockSession ();

                /*
                 *
                 *      if given $ip didn't appear in the array, add it, and then slice the array
                 *      to hold only the latest 10 entries (the oldest entries get removed and so
                 *      automatically unblocked): note that it also repeats the check after locking
                 *      the session to be sure no other script added this address while the current
                 *      running script was also trying to...
                 *
                 */

                $kx = wExplode (';', get ('stats/counters', 'counter>kicks', 'list'));
                $ki = array ();
                $kt = array ();

                foreach ($kx as $iprecord) {

                        list ($ip, $time) = explode ('@', $iprecord);

                        $ki[] = $ip;
                        $kt[] = $time;

                } // exploding ip address and time from each ip record

                $already_kicked = (in_array ($target_ip, $ki)) ? true : false;

                if ($already_kicked == false) {

                        $ki[] = $target_ip;
                        $kt[] = $epc;

                        $kx = array ();
                        $kc = 0;

                        while (($kc < $maxkickedips) && (isset ($ki[$kc]))) {

                                if ($epc - $kt[$kc] < $ipkickdelay) {

                                        $kx[] = implode ('@', array ($ki[$kc], $kt[$kc]));

                                } // ip record did not expire

                                ++ $kc;

                        } // each ip record as long as found and as long as they're less than max.

                        /*
                         *
                         *      write the list back:
                         *      write the whole record, so if it doesn't exist it will create one
                         *
                         */

                        set (

                                'stats/counters', 'counter>kicks', wholeRecord,
                                '<counter>kicks<list>' . implode (';', $kx)

                        );

                } // not already kicked, not even after locking the session

        } // not already kicked

}

/*
 *
 *      delete_pm
 *
 *      this is used to delete a private message, and it's called by 'inbox.php', 'outbox.php' and
 *      'profile.php', hence it has been placed in this common set of functions; furthermore, it's
 *      been declared before 'pquit' because 'pquit' may call it as well, while pruning an account
 *      for inactivity (private messages alone do not count as an activity); returns false in case
 *      the delete has failed, or if the member's session has no authorization to delete the given
 *      message (that is, if the message does not belong to the member whose session called this);
 *      to match these rights $nick must be set to the nickname of the member who is "supposed" to
 *      delete the message, which is normally the same nickname as the member who owns the session
 *      (the global $nick variable); there is only one case where it's different: an administrator
 *      who deletes another member; in that case the argument $nick is forcedly set to that of the
 *      member that's been deleted (forced to resign)
 *
 */

function delete_pm ($nick, $pmid) {

        global $bs_pms;

        /*
         *
         *      delete private message identified by ID $pmid, providing $nick appears in the
         *      list of recipients of that message: $nick needs to be the global $nick string
         *      of the session that calls this function under normal circumstances, but could
         *      be "forced" to a specific value in extraordinary cases (forced resignation)
         *
         */

        lockSession ();

        /*
         *
         *      locate DB file holding message record,
         *      read semicolon-delimited recipients' list and explode it to an array of nicknames
         *
         */

        $pmdb = 'pms' . intervalOf (intval ($pmid), $bs_pms);
        $rcpt = wExplode (';', get ($pmdb, "id>$pmid", 'rcpt'));

        if (in_array ($nick, $rcpt)) {

                /*
                 *
                 *      if member nickname is among recipients, which, as $nick is loaded from the
                 *      global $nick, counts as a check on the right to delete this PM, remove the
                 *      said nickname from the list of recipients: this effectively deletes the PM
                 *      from the point of view of the owner (no longer in the inbox/outbox lists)
                 *
                 */

                $rcpt = wArrayDiff ($rcpt, array ($nick));

                if (count ($rcpt) == 0) {

                        /*
                         *
                         *      if the array is now void because the nick was that of the only
                         *      remaining recipient, remove the whole message record, i.e. delete
                         *      the PM physically and definitely, because nobody owns it anymore:
                         *      this deletes the PM in the point of view of the entire system
                         *
                         */

                        set ($pmdb, "id>$pmid", wholeRecord, deleteRecord);

                }

                else {

                        /*
                         *
                         *      if the array is not yet void (one or more recipients still own this
                         *      same message) write the new recipients list into the message record
                         *
                         */

                        set ($pmdb, "id>$pmid", 'rcpt', implode (';', $rcpt));

                }

                return (true);

        } // given $nick was among recipients

        /*
         *
         *      if member nickname wasn't among recipients, fail
         *
         */

        return (false);

}

/*
 *
 *      pquit:
 *      locked session frame release, final cron jobs
 *
 *      every Postline script, unless in very particular cases, might terminate by echo-ing
 *      the return value of this function; the function returns $page_model after appending
 *      informations to it about the size of the page and the script's execution time; this
 *      function also manages chronologically-planned jobs, like processing LIFO lists that
 *      update the search indexs for posts and chat logs, the pruning of unused sessions in
 *      the sessions and guest sessions table and the pruning of inactive members' accounts
 *
 */

function pquit ($page_model) {

        global $epc, $bs_members;
        global $login, $id, $ip, $nick, $auth, $ntint, $cflag;

        /*
         *
         *      registered members and guest sessions tracking:
         *
         *            - if this client hasn't a logged-in account, mark its IP in guests' list;
         *            - if there isn't a session record stating this member is online, build it
         *
         *      $t evaluates as zero if there's no session record at all, causing the record to
         *      be re-created anyway (unless you believe a session could have expired in 1970)
         *
         */

        global $track_guests;
        global $sessionexpiry;
        global $g_sessionexpiry;

        $create_missing_session = (defined ('disable_session_creation')) ? false : true;

        if ($create_missing_session) {

                if ($login) {

                        $t = intval (get ('stats/sessions', "id>$id", 'beg'));
                        $session_expired = (($t + $sessionexpiry) < $epc) ? true : false;

                        if ($session_expired) {

                                /*
                                 *
                                 *      building a new session record:
                                 *      lock and verify if all conditions still apply
                                 *
                                 */

                                lockSession ();

                                $t = intval (get ('stats/sessions', "id>$id", 'beg'));
                                $session_expired = (($t + $sessionexpiry) < $epc) ? true : false;

                                if ($session_expired) {

                                        set

                                                (

                                                        'stats/sessions', "id>$id", wholeRecord,
                                                        "<id>$id<ip>$ip<nick>$nick<auth>$auth"
                                                      . "<beg>$epc<ntint>$ntint<chatflag>$cflag"

                                                );

                                        $db = 'members' . intervalOf ($id, $bs_members);
                                        $mr = get ($db, "id>$id", wholeRecord);
                                        $ok = ($mr == voidString) ? false: true;

                                        if ($ok) {

                                                $ln = intval (valueOf ($mr, 'logins')) + 1;

                                                $mr = fieldSet ($mr, 'lastlogin', strval ($epc));
                                                $mr = fieldSet ($mr, 'logins', strval ($ln));
                                                $mr = fieldSet ($mr, 'logip', $ip);

                                                set ($db, "id>$id", wholeRecord, $mr);

                                        } // logged-in member record exists

                                } // session still expired after locking

                        } // session results expired before locking

                } // logged-in member session creation

                else
                if ($track_guests) {

                        $t = intval (get ('stats/guests', "ip>$ip", 'beg'));
                        $session_expired = (($t + $g_sessionexpiry) < $epc) ? true : false;

                        if ($session_expired) {

                                /*
                                 *
                                 *      building new guest session record:
                                 *      lock and verify if all conditions still apply
                                 *
                                 */

                                lockSession ();

                                $t = intval (get ('stats/guests', "ip>$ip", 'beg'));
                                $session_expired = (($t + $g_sessionexpiry) < $epc) ? true : false;

                                if ($session_expired) {

                                        set

                                                (

                                                        'stats/guests', "ip>$ip", wholeRecord,
                                                        "<ip>$ip<beg>$epc"

                                                );

                                } // guest session still expired after locking

                        } // guest session results expired before locking

                } // guest session creation

        } // been eventually creating missing sessions for logged-in members and guests

        /*
         *
         *      eventually update session, remove AFK sign, update last recorded chat frame status:
         *      these things are only done for logged-in members, not guests
         *
         */

        global $sessionupdate;

        $update_member_session = (defined ('disable_session_update')) ? false : true;

        if ($update_member_session) {

                if ($login) {

                        $r = get ('stats/sessions', "id>$id", wholeRecord);
                        $t = intval (valueOf ($r, 'beg'));
                        $c = valueOf ($r, 'chatflag');
                        $a = valueOf ($r, 'afk');

                        $do_session_update = ($t + $sessionupdate < $epc) ? true : false;
                        $do_session_update = ($c == $cflag) ? $do_session_update : true;
                        $do_session_update = ($a == 'yes') ? true : $do_session_update;

                        if ($do_session_update) {

                                /*
                                 *
                                 *      updating session record (to keep it from expiring or to
                                 *      reflect changes to session data such as chat flag state
                                 *      or AFK sign): lock and verify if conditions still apply
                                 *
                                 */

                                lockSession ();

                                $r = get ('stats/sessions', "id>$id", wholeRecord);
                                $t = intval (valueOf ($r, 'beg'));
                                $c = valueOf ($r, 'chatflag');
                                $a = valueOf ($r, 'afk');

                                $do_session_update = ($t + $sessionupdate < $epc) ? true : false;
                                $do_session_update = ($c == $cflag) ? $do_session_update : true;
                                $do_session_update = ($a == 'yes') ? true : $do_session_update;

                                if ($do_session_update) {

                                        /*
                                         *      notice that the AFK indication is removed without
                                         *      condition, because if a logged-in member requests
                                         *      a Postline page, it's supposed to mean the member
                                         *      is no longer AFK (away from keyboard)
                                         *
                                         */

                                        $r = fieldSet ($r, 'beg', strval ($epc));
                                        $r = fieldSet ($r, 'chatflag', $cflag);
                                        $r = fieldSet ($r, 'afk', deleteField);

                                        set ('stats/sessions', "id>$id", wholeRecord, $r);

                                } // still needing an update in session data after locking

                        } // session data needs to be updated before locking

                } // page was requested by a logged-in member

        } // been eventually updating session data for logged-in members

        /*
         *
         *      cron_sessions:
         *      will periodically check for expired member sessions and remove them
         *
         */

        global $expirycheck;

        $cron_sessions = @filemtime ('widgets/sync/cron_sessions');
        $cron_sessions = ($cron_sessions === false) ? 0 : $cron_sessions;
        $cron_sessions = ($cron_sessions + $expirycheck < $epc) ? true : false;

        if ($cron_sessions) {

                lockSession ();

                $sessions = all ('stats/sessions', makeProper);

                foreach ($sessions as $s) {

                        $sid = valueOf ($s, 'id');
                        $beg = valueOf ($s, 'beg');

                        if (($beg + $sessionexpiry) < $epc) {

                                /*
                                 *
                                 *      remove expired session from sessions table: this will cause
                                 *      the status line to no longer process this expired record
                                 *
                                 */

                                set ('stats/sessions', "id>$sid", wholeRecord, deleteRecord);

                        } // been removing an expired session record

                } // each session record

                @touch ('widgets/sync/cron_sessions');

        } // been doing cron_sessions

        /*
         *
         *      g_cron_sessions:
         *      will periodically check for expired guest sessions and remove them
         *
         */

        global $g_expirycheck;

        if ($track_guests) {

                $g_cron_sessions = @filemtime ('widgets/sync/cron_sessions');
                $g_cron_sessions = ($g_cron_sessions === false) ? 0 : $g_cron_sessions;
                $g_cron_sessions = ($g_cron_sessions + $g_expirycheck < $epc) ? true : false;

        }

        else {

                $g_cron_sessions = false;

        }

        if ($g_cron_sessions) {

                lockSession ();

                $sessions = all ('stats/guests', makeProper);

                foreach ($sessions as $s) {

                        $sip = valueOf ($s, 'ip');
                        $beg = valueOf ($s, 'beg');

                        if (($beg + $g_sessionexpiry) < $epc) {

                                /*
                                 *
                                 *      remove expired session from sessions table: this will cause
                                 *      the status line to no longer process this expired record
                                 *
                                 */

                                set ('stats/guests', "ip>$sip", wholeRecord, deleteRecord);

                        } // been removing an expired session record

                } // each session record

                @touch ('widgets/sync/g_cron_sessions');

        } // been doing g_cron_sessions

        /*
         *
         *      cron_pruning:
         *      will periodically remove inactive member accounts from the member's database
         *
         */

        global $pruningcheck;
        global $pruneolderthan;
        global $admin_ranks;
        global $mod_ranks;
        global $cd_filetypes;

        if ($pruningcheck > 0) {

                $cron_pruning = @filemtime ('widgets/sync/cron_pruning');
                $cron_pruning = ($cron_pruning === false) ? 0 : $cron_pruning;
                $cron_pruning = ($cron_pruning + $pruningcheck < $epc) ? true : false;

        }

        else {

                $cron_pruning = false;

        }

        if ($cron_pruning) {

                lockSession ();

                /*
                 *
                 *      time for pruning check:
                 *      get all files from members database (all divisions, all intervals)
                 *
                 */

                $blocks = getIntervals ('members');

                /*
                 *
                 *      process one block per run, depending on the request time:
                 *      this will progressively check all blocks, one after the other...
                 *
                 */

                $numblocks = count ($blocks);
                $dopruning = ($numblocks > 0) ? true : false;

                if ($dopruning) {

                        $p = (int) ($epc / $pruningcheck);      // make progressive index
                        $p = $p % $numblocks;                   // clamp to [0-$numblocks]

                        /*
                         *
                         *      note that an interval file's name will be provided by getIntervals
                         *      as it is returned by getFiles (see 'dbengine.php'); interval files
                         *      will so lack the exact folder (division) name from which they were
                         *      taken; the full "virtual" path must be reconstructed to access one
                         *      of these files: this can be done by passing the lower limit of the
                         *      interval to the intervalOf function and appending the return value
                         *      to the folder's name
                         *
                         */

                        list ($lo, $hi) = explode ('-', $blocks[$p]);
                        $block = 'members' . intervalOf (intval ($lo), $bs_members);

                        /*
                         *
                         *      once a target block for this check is selected,
                         *      get list of member accounts from that block and examine each record
                         *
                         */

                        $mlist = all ($block, makeProper);

                        foreach ($mlist as $r) {

                                /*
                                 *
                                 *      get registration ID and message count: both replies and new
                                 *      threads count as posts; note that it checks over a possibly
                                 *      void ID: it shouldn't happen but we once had a couple wrong
                                 *      void records in the first block of member accounts, which
                                 *      caused this piece of code to repeately attempt to erase the
                                 *      non-existing entries having a void ID, in turn causing the
                                 *      log to get "spammed" with bogus reports, and members count
                                 *      to drop to negative values...
                                 *
                                 */

                                $i = valueOf ($r, 'id');
                                $p = intval (valueOf ($r, 'posts'));
                                $t = intval (valueOf ($r, 'threads'));

                                $no_posts = ($p + $t == 0) ? true : false;
                                $no_posts = ($i == voidString) ? false : $no_posts;

                                /*
                                 *
                                 *      if member didn't post anything so far, the account
                                 *      is eligible for pruning, but only after a certain
                                 *      time it's been registered without posting nothing,
                                 *      which is what it's going to check now...
                                 *
                                 */

                                $t = intval (valueOf ($r, 'lastlogin'));
                                $t = ($t == 0) ? intval (valueOf ($r, 'reg')) : $t;

                                $long_time_no_see = ($t + $pruneolderthan < $epc) ? true : false;

                                /*
                                 *
                                 *      lastly, no matter the above conditions,
                                 *      accounts belonging to staff members cannot be pruned
                                 *
                                 */

                                $a = valueOf ($r, 'auth');

                                $in_staff = (in_array ($a, $admin_ranks)) ? true : false;
                                $in_staff = (in_array ($a, $mod_ranks)) ? true : $in_staff;

                                /*
                                 *
                                 *      alright to delete this account?
                                 *
                                 */

                                if (($no_posts) && ($long_time_no_see) && ($in_staff == false)) {

                                        /*
                                         *
                                         *      time out:
                                         *      account will be pruned, build note to write in log
                                         *
                                         */

                                        $k = valueOf ($r, 'nick');
                                        $d = gmdate ('m/d/Y (H:i:s)', $epc);

                                        $log = ".\r\n. $d, pruning account #$i ($k)...\r\n";

                                        /*
                                         *
                                         *      get private messages inbox and outbox for deleting
                                         *      each message; if errors occur while deleting any
                                         *      messages, report it in the log
                                         *
                                         */

                                        $inlist = wExplode (';', valueOf ($r, 'inbox'));
                                        $outlist = wExplode (';', valueOf ($r, 'outbox'));
                                        $privates = array_merge ($inlist, $outlist);

                                        foreach ($privates as $p) {

                                                $pmid = abs (intval ($p));

                                                if (delete_pm ($k, $pmid) == false) {

                                                        $log .= ".\tmismatched private message "
                                                             .  "removal, message id = $pmid\r\n";

                                                } // failing an attempt to delete the given PM

                                        } // each private message

                                        /*
                                         *
                                         *      remove account record, remove nickname record
                                         *
                                         */

                                        set ($block, "id>$i", wholeRecord, deleteRecord);
                                        set ('members/bynick', "id>$i", wholeRecord, deleteRecord);

                                        /*
                                         *
                                         *      increase resigns counter,
                                         *      to balance count of members in hold
                                         *
                                         */

                                        $rc = get ('stats/counters', "counter>resigns", 'count');
                                        $rc = intval ($rc) + 1;

                                        set

                                        (

                                                'stats/counters', 'counter>resigns', wholeRecord,
                                                "<counter>resigns<count>$rc"

                                        );

                                        /*
                                         *
                                         *      invalidate read-write cache to check for successful
                                         *      deletion: check all affected files - nothing should
                                         *      go wrong here or inconsistences could accumulate as
                                         *      more accounts get pruned...
                                         *
                                         */

                                        invalidateCache ();

                                        $deleted_record = get ($block, "id>$i", wholeRecord);
                                        $record_deleted = ($deleted_record == '') ? true : false;

                                        $deleted_nick = get ('members/bynick', "id>$i", 'nick');
                                        $nick_deleted = ($deleted_nick == '') ? true : false;

                                        $rc_b = get ('stats/counters', 'counter>resigns', 'count');
                                        $counter_updated = ($rc == $rc_b) ? true : false;

                                        if ($record_deleted == false) {

                                                $log .= ".\t$block: can't erase entry\r\n";

                                        }

                                        if ($nick_deleted == false) {

                                                $log .= ".\tmembers/bynick: can't erase entry\r\n";

                                        }

                                        if ($counter_updated == false) {

                                                $log .= ".\tresigns counter update failed\r\n";

                                        }

                                        /*
                                         *
                                         *      locate eventual avatar image and delete its file:
                                         *      if you fail deleting the avatar, log this problem
                                         *
                                         */

                                        $avatar = voidString;
                                        $fname = base62Encode ($k);

                                        if (@file_exists ("images/avatars/$fname.gif")) {

                                                $avatar = "images/avatars/$fname.gif";

                                        }

                                        else
                                        if (@file_exists ("images/avatars/$fname.jpg")) {

                                                $avatar = "images/avatars/$fname.jpg";

                                        }

                                        else
                                        if (@file_exists ("images/avatars/$fname.png")) {

                                                $avatar = "images/avatars/$fname.png";

                                        }

                                        $check = ($avatar == voidString) ? true : unlink ($avatar);

                                        if ($check == false) {

                                                $log .= ".\tdeletion failed (file = $avatar)\r\n";

                                        }

                                        /*
                                         *
                                         *      locate eventual photograph and delete it as well:
                                         *      on errors deleting the photograph, log this problem
                                         *
                                         */

                                        $photo = voidString;
                                        $fname = base62Encode ($k);

                                        if (@file_exists ("images/photos/$fname.gif")) {

                                                $photo = "images/photos/$fname.gif";

                                        }

                                        else
                                        if (@file_exists ("images/photos/$fname.jpg")) {

                                                $photo = "images/photos/$fname.jpg";

                                        }

                                        else
                                        if (@file_exists ("images/photos/$fname.png")) {

                                                $photo = "images/photos/$fname.png";

                                        }

                                        $check = ($photo == voidString) ? true : unlink ($photo);

                                        if ($check == false) {

                                                $log .= ".\tdeletion failed (file = $photo)\r\n";

                                        }

                                        /*
                                         *
                                         *      finally look for any possible files that the pruned
                                         *      member may have uploaded to the c-disk, and report
                                         *      their existence as "orphan files" in the log
                                         *
                                         */

                                        foreach ($cd_filetypes as $typestring) {

                                                list (, $type) = explode ('/', $typestring);

                                                $entry = get ("cd/dir_{$type}s", "owner>$k", '');
                                                $entry_exists = ($entry == '') ? false : true;

                                                if ($entry_exists) {

                                                        $log    .= ".\t one or more orphan c-disk "
                                                                .  "files remaining in folder:\r\n"
                                                                .  ".\t- <a target=\"pst\" href=cd"
                                                                .  "isk.php?sub=$fname>cd/$k</a>\r"
                                                                .  "\n.\t  moderators may take the"
                                                                .  "above link to browse or delete"
                                                                .  "orphan files,\r\n.\t  if such "
                                                                .  "files are now irrelevant.\r\n";

                                                        break;

                                                } // append a single warning for each file type

                                        } // each file type

                                        /*
                                         *
                                         *      write the pruning log, either creating it,
                                         *      or appending data to it
                                         *
                                         */

                                        $hFile = @fopen ('logs/prunelog.txt', 'a');
                                        $error = ($hFile === false) ? true : false;

                                        if ($error) {

                                                $hFile = @fopen ('logs/prunelog.txt', 'wb');
                                                $error = ($hFile === false) ? true : false;

                                                $log = '<pre>' . $log;

                                        } // failed to open existing log file, been trying creation

                                        if ($error == false) {

                                                fwrite ($hFile, $log);
                                                fclose ($hFile);

                                        } // succeeded either creating or opening, been writing

                                        /*
                                         *
                                         *      break the outer 'foreach' loop such that only one
                                         *      account gets pruned per page request: the process
                                         *      can be time-consuming
                                         *
                                         */

                                        break;

                                } // account was eligible for pruning, and has been removed

                        } // each member in selected block

                } // at least one block of accounts existed and could be selected

                @touch ('widgets/sync/cron_pruning');

        } // been doing cron_pruning

        /*
         *
         *      processing request to turn on the "afk" sign:
         *      requested by mstats.php, but must be done after ensuring a session record EXISTS
         *
         */

        if ($login) {

                if (defined ('turn_on_afk_sign')) {

                        set ('stats/sessions', "id>$id", 'afk', 'yes');

                } // been processing a request to mark this session AFK

        } // request by logged-in member: this check isn't necessary providing mstats.php checks it

        /*
         *
         *      processing search indexing jobs LIFO lists
         *
         */

        process_jobs ('posts');
        process_jobs ('logs');

        /*
         *
         *      releasing locked session frame, if ever locked:
         *      this will also flush the write cache, making changes to files effective
         *
         */

        unlockSession ();

        /*
         *
         *      post-processing page model to remove templates' unnecessary formatters and comments
         *
         */

        $page_model = strReplaceFromTo ($page_model, '<!--[', ']--->', voidString);
        $page_model = trim (preg_replace ("/\n\s+\n+/", "\n", $page_model));

        /*
         *
         *      including server load time and bandwidth informations
         *
         */

        global $queryCount;

        $q_str = ($queryCount == 1) ? 'query' : 'queries';
        $q_len = ($queryCount == 1) ? 5 : 7;

        $page_size =

                strlen ($page_model)            // byte size of the actual HTML output
              + strlen (strval ($queryCount))   // byte size of queries counter
              + 5                               // 5 = "X.XXX" (reported execution time)
              - 16;                             // considers replaced "[EXECUTION-TIME]" marker

        $byte_size = strlen (strval ($page_size));      // string length of the byte counter itself
        $page_size += $byte_size + 2 + 3 + $q_len;      // count + 2 commas + 3 blanks + 'query/es'
        $page_size += strlen (strval ($page_size)) - $byte_size;        // plus an eventual "carry"

        $page_model = str_replace

                (

                        '[EXECUTION-TIME]',
                        "$page_size, $queryCount $q_str," . blank . stopwatchStop (),

                        $page_model

                );

        /*
         *
         *      return complete page output, prepare compression if accepted by browser
         *
         */

        if (substr_count ($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {

                @ob_start ('ob_gzhandler');

        } return ($page_model);

}

define ('postline/suitcase.php', true); } ?>
