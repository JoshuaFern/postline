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



define ("going", true);
define ("left_frame", true);

require ("settings.php");
require ("suitcase.php");
require ("sessions.php");
require ("template.php");

$em['invalid_request'] = 'YOUR REQUEST IS INVALID';
$ex['invalid_request'] =

        "Looks like the destionation of the uploaded file does not refer "
      . "to a path compatible with that of an user-uploaded file.";

/*
 *
 * initialize output accumulators
 *
 */

$form = "";             // page output
$error = false;         // error flag
$showlist = true;       // false when asking delete confirmation
$t_folders = 0;         // total folders in directory listing
$t_files = 0;           // total folders in directory listing
$t_clust = 0;           // total bytes in listed objects
$q_demultiplier = 1;    // disk quota demultiplier (1 = root)
$may_be_sorted = false; // true if it's possibile to sort entities in given directory listing

/*
 *
 * get parameters
 *
 */

$submit = isset ($_POST["submit"]);                     // form submit flag
$keyword = fget ("keyword", $maxkeywordlen, "");        // optional smiley keyword
$dir = fget ("dir", 9, "");                             // c-disk level-1 folder ("gifs"...)
$let = fget ("let", 1, "");                             // c-disk alphabetic folder (A, B, C..)
$sub = fget ("sub", -1000, "");                         // c-disk level-2 folder (1 per member)
$dnl = fget ("dnl", 1000, "");                          // file to download, if requested
$del = fget ("del", 1000, "");                          // file to delete, if requested
$sort = fget ("sort", 4, "");                           // sort per: "name", "date" or "size"

/*
 *
 * default sorting method is by date
 *
 */

$sort = (empty ($sort)) ? "date" : $sort;

/*
 *
 * get upload flood counter
 *
 */

list ($lfid, $lftime) = explode ("+", get ("stats/flooders", "entry>file", "="));
$lftime = intval ($lftime);

/*
 *
 * utility functions
 *
 */

$items = 0;                                             // listed items counter
$cellw = intval ($pw / 2);                              // width of a table cell here
$plist = "declaration only, string will be set later";  // list part backup (see "additem")

function additem ($props, $tt, $link, $icon, $name, $delete_link) {

  global $pw, $list, $plist, $items, $cellw, $charspernameline;

  /*
    adds an item's icon
    - if there's a delete button, create its HTML and move the item's icon a bit to the left
  */

  if (empty ($delete_link)) {

    $style_extra = "";

  }
  else {

    $style_extra = chr (32) . "style=position:relative;left:6px";
    $delete_link = "<a title=\"delete &quot;$name&quot;\" href=cdisk.php?del=$delete_link>"
                 . "<img src=layout/images/del.png width=24 height=24 border=0 style=position:relative;right:6px>"
                 . "</a>";
  }

  /*
    if it's the first item displayed, open the table
  */

  if ($items == 0) {

    $list .= "<table width=$pw>";
    $plist = $list;

  }

  /*
    determine wether the item is displayed on an even or odd table column
  */

  $row = $items % 2;

  if ($row) {

    /*
      display odd column (to the right):
      - no height indication, saves bandwidth and is given in this row's even column
      - append a </tr> after closing the column with </td>
      - save $plist backup after closing </tr>
      - temporarily close table, in case no further items will be added
    */

    $list = $plist
          . "<td width=$cellw align=center valign=top>"
          . "<a $props title=\"$tt\" href=$link>"
          . "<img src=$icon width=48 height=48 border=0$style_extra>"
          . "</a>"
          . $delete_link
          . "<br>"
          . "<a class=cd $props href=$link>"
          . substr ($name, 0, $charspernameline)
          . ((strlen ($name) <= $charspernameline) ? "" : "...")
          . "</a>"
          . "</td>"
          . "</tr>";

    $plist = $list;
    $list .= "</table>";

  }
  else {

    /*
      display even column (to the left):
      - append a void (right-side placeholder) cell after closing the column with </td>
      - save $plist backup after closing </td>, for eventually adding an odd column
      - temporarily close table, in case no further items will be added
    */

    $list = $plist
          . "<tr>"
          . "<td width=$cellw height=78 align=center valign=top>"
          . "<a $props title=\"$tt\" href=$link>"
          . "<img src=$icon width=48 height=48 border=0$style_extra>"
          . "</a>"
          . $delete_link
          . "<br>"
          . "<a class=cd $props href=$link>"
          . substr ($name, 0, $charspernameline)
          . ((strlen ($name) <= $charspernameline) ? "" : "...")
          . "</a>"
          . "</td>";

    $plist = $list;
    $list .= "<td width=$cellw>&nbsp;</td></tr></table>";

  }

  /*
    increase total items counter
  */

  $items ++;

}

function getclass ($dir, $sub) {

  /*
    returns all items found in directory file indicated by cd/$dir/$sub:
    - $l is the length of $sub (typically a member's name), plus 1,
      and is calculated to insulate the encoded file names so they can be decoded:
      because all files listed here must be in the same $sub-folder, $l is a constant.
  */

  $l = strlen ($sub) + 1;

  /*
    retrieve all file records from the diven filetype directory ($dir argument),
    prepare a void array to hold filtered objects' records.
  */

  $file = all ("cd/dir_$dir", makeProper);
  $object = array ();

  /*
    filter out of the list any files whose "owner" doesn't match $sub:
    $object will hold a list of (decoded) filename + (full) object record combinations
  */

  foreach ($file as $f)

    if (valueOf ($f, "owner") == $sub) $object[] = $f;

  /*
    return the array of matching records
  */

  return ($object);

}

function all_between ($dir, $lo, $hi) {

  /*
    this function is used by "today", "yesterday"... queries, and adds all items found in
    directory file indicated by cd/$dir that match the given upload time range (lo < t < hi):
    - retrieve all file records from the diven filetype directory ($dir argument),
      prepare a void array to hold filtered objects' records, which will be returned.
  */

  $file = all ("cd/dir_$dir", makeProper);
  $match = array ();

  /*
    filter out of the list any files whose upload time range doesn't match $lo to $hi:
    $match will hold a list of full object records
  */

  foreach ($file as $f) {

    $t = valueOf ($f, "stored");
    if (($t > $lo) && ($t <= $hi)) $match[] = $f;

  }

  /*
    return the filtered array
  */

  return $match;

}

$nicks = array ();
$nicks_loaded = false;

function all_orphans ($dir) {

  global $nicks;
  global $nicks_loaded;

  /*
    this function is used by "orphans" query, and adds all items found in directory file
    indicated by cd/$dir whose owner doesn't appear to match any actually registered nicknames:
    - for a good start, prepare a keyed array of all registered nicknames, unless already done...
  */

  if (!$nicks_loaded) {

    $nicks_loaded = true;
    $nickname = all ("members/bynick", makeProper);

    foreach ($nickname as $n)

      $nicks[valueOf ($n, "nick")] = true;

  }

  /*
    retrieve all file records from the diven filetype directory ($dir argument),
    prepare a void array to hold filtered objects' records, which will be returned.
  */

  $file = all ("cd/dir_$dir", makeProper);
  $match = array ();

  /*
    filter out of the list any files whose owner matches an existing nickname:
    $match will hold a list of full object records
  */

  foreach ($file as $f)

    if (!$nicks[valueOf ($f, "owner")]) $match[] = $f;

  /*
    return the filtered array
  */

  return $match;

}

function addlist ($file) {

  global $nick, $is_admin, $is_mod;
  global $items, $t_files, $t_clust, $cd_filetypes, $cd_clustersize;

  /*
    given an array of file records to list, lists them by repeately calling "additem"
  */

  $isimage = array ();

  foreach ($cd_filetypes as $t) {

    list ($classification, $extension) = explode ("/", $t);
    $isimage[$extension] = ($classification == "image") ? true : false;

  }

  foreach ($file as $f) {

    /*
      decode file name past the path
    */

    $user = valueOf ($f, "owner");                              // file owner
    $path = valueOf ($f, "file");                               // relative path and name
    $type = strFromTo ($path . "<", ".", "<");                  // insulate file extension
    $tlen = strlen ($type);                                     // get extension length
    $name = base62Decode (substr ($path, 5 + $tlen, - 1 - $tlen)); // decode file name (user/file)
    $name = strFromTo ($name . "<", "/", "<");                  // remove "user/" path from name

    /*
      determine additional link properties and service link,
      prepare icon filename, prepare tooltip text
    */

    $file_size = intval (valueOf ($f, "size"));

    $entity = "icon_{$type}";
    $tt = "$name by $user," . chr (32) . (int) ($file_size / 1024) . chr (32) . "Kb $type";

    if ($isimage[$type]) {

      $props = "target=pan";
      $servlink = "cdimage.php?name=";
      $tt .= "," .chr (32) . valueOf ($f, "width") . "x" . valueOf ($f, "height") . chr (32) . "pixels";

    }
    else {

      $props = "";
      $servlink = "cdisk.php?dnl=";

    }

    $tt .= "," . chr (32) . "stored:" . chr (32) . gmdate ("M\x20d,\x20Y\x20H:i", intval (valueOf ($f, "stored")));

    if (($user == $nick) || ($is_admin) || ($is_mod)) {

      additem ($props, $tt, $servlink . $path, "layout/images/$entity.png", $name, $path);

    }
    else {

      additem ($props, $tt, $servlink . $path, "layout/images/$entity.png", $name, "");

    }

    /*
      also increase total displayed files counter, and count related allocated cdisk space
    */

    $t_clust += (int) ($file_size / $cd_clustersize) + 1;
    $t_files ++;

  }

}

$byname_names = array ();       // this optimizes sorting of file records by name, see below...

function byname ($a, $b) {

  global $byname_names;

  /*
    function to sort file records by file name
  */

  if (empty ($byname_names[$a])) {

    $x_path = valueOf ($a, "file");                                     // relative path and name
    $x_type = strFromTo ($x_path . "<", ".", "<");                      // insulate file extension
    $x_tlen = strlen ($x_type);                                         // get extension length
    $x_name = base62Decode (substr ($x_path, 5 + $x_tlen, - 1 - $x_tlen)); // decode file name
    $byname_names[$a] = $x_name;                                        // opt. redundant checks

  }
  else {

    $x_name = $byname_names[$a];

  }

  if (empty ($byname_names[$b])) {

    $y_path = valueOf ($b, "file");                                    // relative path and name
    $y_type = strFromTo ($y_path . "<", ".", "<");                      // insulate file extension
    $y_tlen = strlen ($y_type);                                         // get extension length
    $y_name = base62Decode (substr ($y_path, 5 + $y_tlen, - 1 - $y_tlen)); // decode file name
    $byname_names[$b] = $y_name;                                        // opt. redundant checks

  }
  else {

    $y_name = $byname_names[$b];

  }

  return strcmp ($x_name, $y_name);

}

function bydate ($a, $b) {

  /*
    function to sort file records by upload date (most recent to least recent)
  */

  $x = intval (valueOf ($a, "stored"));
  $y = intval (valueOf ($b, "stored"));

  return $y - $x;

}

function bysize ($a, $b) {

  /*
    function to sort file records by file size (bigger to smaller)
  */

  $x = intval (valueOf ($a, "size"));
  $y = intval (valueOf ($b, "size"));

  return $y - $x;

}

/*
 *
 * switch between delete, archive download, or file upload request:
 * they could be done at the same time, but it's not supposed to happen.
 *
 * download requests are considered valid for any files which have a record in one of the
 * c-disk's virtual directories (cd/dir_...), but in practice, ?dnl=... links are given
 * only for files that aren't images: in fact, images are handled throught "cdimage.php";
 * all the rest gets downloaded throught the following code, which purpose is mostly that
 * of attributing a humanly-readable real name to the downloaded files (because they're
 * always being stored with a base-62 encoded name in the real server directories forming
 * the c-disk, for reasons of security and convenience, and it would be uncomfortable to
 * have client-side browsers save them with such names).
 *
 */

if (substr ($dnl, 0, 3) == "cd/") {

  /*
    download request:
    insulate doctype from name, gather file record in corresponding c-disk directory,
    check if an effective record is available: checks on what's being requested for
    download are important to avoid security holes throught which someone could convince
    the script to download sensible data from the server; this kind of check is done very
    tightly in Postline. Put simply, only what's been successfully uploaded via this same
    script (see code for upload requests) is made available for download, since the given
    dnl argument must be found associated to a record in one for the virtual directories:
    as a preamble, valid upload requests can originally be triggered only by a file being
    uploaded from the client's computer (call to "is_uploaded_file" must return true).
    Also, the file path from $dnl is checked to see if it effectively begins with "cd/".
    All this, to mean: beware when making changes to cdisk.php; keep these holes sealed.
  */

  list (, $doctype) = explode ("/", $dnl);
  $record = get ("cd/dir_$doctype", "file>$dnl", "");

  if (empty ($record)) {

    $error = true;

    $form = "File not found in c-disk.//The file you've been attempting to download "
          . "appears to be not registered, or no longer registered, in the c-disk's "
          . "tables of contents. Perhaps someone else has deleted it.";

  }
  else {

    $type = strFromTo ($dnl . "<", ".", "<");                           // insulate file extension
    $tlen = strlen ($type);                                             // get extension length
    $encn = substr ($dnl, 5 + $tlen, - 1 - $tlen);                      // get encoded file name
    list ($owner, $label) = explode ("/", base62Decode ($encn));        // separate owner and name

    /*
      prepare download details
    */

    $name = "$label.$type";                                             // client-side file name
    $mime = $cd_mimetypes[$type];                                       // reported MIME type
    $date = gmdate ("D, d M Y H:i:s") . chr (32) . "GMT";               // today's date

    $conf = fget ("submit", 3, "");

    if ($conf == "yes") {

      /*
        download confirmed: prepare for streaming
      */

      @ini_set ("zlib.output_compression", "Off");                      // in case it was on

      header ("Pragma: public");                                        // write in public cache
      header ("Last-Modified: $date");                                  // always today's date
      header ("Cache-Control: no-store, no-cache, must-revalidate");    // HTTP/1.1
      header ("Cache-Control: pre-check=0, post-check=0, max-age=0");   // HTTP/1.1
      header ("Content-Type: $mime; name=\"$name\"");                   // set content type
      header ("Content-Disposition: inline; filename=\"$name\"");       // appears to be working

      /*
        Content-Length may be found assuming the server sends a chunked transfer,
        where the entire file is sent in a single chunk: seems some Apaches do that
      */

      $contentLength = wFilesize ($dnl);
   // $contentLength = strlen (base_convert (strval ($contentLength), 10, 16)) + $contentLength + 9;

      header ("Content-Length:" . $contentLength);      // if not set well, eventually comment-out

      /*
        lock and update downloads counter
      */

      lockSession ();

      set

      (

        'cd/counters', "f>$dnl", wholeRecord,

        "<f>$dnl<n>" . strval (

          intval (

            get ('cd/counters', "f>$dnl", 'n')

          ) + 1

        )

      );

      unlockSession ();

      /*
        append file contents to script output
      */

      die (file_get_contents ($dnl));                                   // die, echoing the file

    }
    else {

      if (empty ($conf)) {

        /*
          output confirmation request for download
        */

        $showlist = false;

        $s = intval (valueOf ($record, "size"));
        $k = (int) ($s / 1024) + 1;

        $connection_speed =
          array (

            57600,
            115200,
            1000000

          );

        foreach ($connection_speed as $b) {

          $B = (int) ($b / 9) - 768;
          $t = (int) ($s / $B) + 1;

          $t_min = (int) ($t / 60);
          $t_sec = sprintf ("%02d", $t % 60);

          $t_table[] = "$t_min' $t_sec'' @ " . ((int) $b / 1000) . " Kbps";

        }

        $stored = intval (valueOf ($record, "stored"));
        $stored = gmdate ("D, d M Y", $stored);

        $downloads = intval (get ('cd/counters', "f>$dnl", 'n'));
        $downloads = ($downloads == 1) ? "1 time" : (($downloads) ? "$downloads times" : "never");

        $list =

          makeframe (

            "$name ($k KiB)",

            "owner: $owner<br>"
          . "stored: $stored<br>"
          . "downloaded: $downloads"
          . "<hr>"
          . implode ("<br>", $t_table)
          . "<hr>"
          . "Download will begin as you confirm it by clicking below.", true

          )

          . "<table width=$pw>"
          . "<tr>"
          .  "<td height=24 class=inv align=center>"
          .   "OK TO DOWNLOAD?"
          .  "</td>"
          . "</tr>"
          . $bridge
          . "<form action=cdisk.php enctype=multipart/form-data method=post>"
          . "<input type=hidden name=dnl value=\"$dnl\">"
          . "<tr>"
          .  "<td>"
          .   "<input class=su style=width:{$pw}px type=submit name=submit value=\"yes, download now\">"
          .  "</td>"
          . "</tr>"
          . $bridge
          . "</form>"
          . "<form action=javascript:history.go(-1)>"
          . "<tr>"
          .  "<td>"
          .   "<input class=ky style=width:{$pw}px type=submit name=submit value=\"no, download later\">"
          .  "</td>"
          . "</tr>"
          . $shadow
          . "</form>"
          . "</table>"
          .

          makeframe (

            "In case of problems...",

            "Should you experience troubles using the above button, like obtaining "
          . "a shorter file or a file not matching the expected type, you might use the "
          . "following direct link instead: the disadvantage of using this direct link "
          . "is that the file will have an encoded name, rather than the intended name.", true

          )

          . "<table width=$pw>"
          . "<tr>"
          .  "<td height=24 class=inv align=center>"
          .   "DIRECT LINK"
          .  "</td>"
          . "</tr>"
          . $bridge
          . "<form action=\"$dnl\">"
          . "<tr>"
          .  "<td>"
          .   "<input class=su style=width:{$pw}px type=submit name=submit value=\"direct download\">"
          .  "</td>"
          . "</tr>"
          . $shadow
          . "</form>"
          . "</table>";

        /*
          set permalink
        */

        if ($enable_permalinks) {

          $permalink = str_replace

            (

              "[PERMALINK]", $perma_url . "/" . $doctype . "/" . $encn, $permalink_model

            );

        }

      }

    }

  }

}

elseif (substr ($del, 0, 3) == "cd/") {

  /*
    delete request:
    - user must be logged in for acknoledgement.
  */

  if ($login) {

    /*
      insulate doctype from name, gather file record in corresponding c-disk directory:
      if no record is readable, the file was apparently already deleted
    */

    list (, $doctype) = explode ("/", $del);
    $record = get ("cd/dir_$doctype", "file>$del", "");

    if (empty ($record)) {

      $error = true;

      $form = "File not found in c-disk.//The file you've been attempting to delete "
            . "appears to be not registered, or no longer registered, in the c-disk's "
            . "tables of contents. Perhaps someone else has deleted it in the meantime?";

    }
    else {

      /*
        retrieve file owner, set directory listing to that folder
      */

      $owner = valueOf ($record, "owner");

      $dir = $doctype;
      $sub = $owner;

      /*
        deletion authorization check:
        - either the user owns this file, or it's at least a moderator
      */

      if (($owner == $nick) || ($is_admin) || ($is_mod)) {

        /*
          delete confirmation
        */

        $conf = fget ("submit", 3, "");
        $code = intval (fget ("code", 1000, ""));

        if (($conf == "yes") && ($code == getcode ("pst"))) {

          /*
            obtain exclusive write access: database manipulation ahead
          */

          lockSession ();

          /*
            try to delete the file (@unlink) and report eventual failure
          */

          if (@unlink ($del)) {

            /*
              if deletion succeeded, also remove file data from the database,
              i.e. from the c-disk's directory file, and eventually from smileys' directory
            */

            set ("cd/dir_$doctype", "file>$del", "", "");

            $smiley_record = get ("cd/smileys.php", "image>$del", "");
            $smiley_exists = (empty ($smiley_record)) ? false : true;

            if ($smiley_exists) {

              set ("cd/smileys", "image>$del", "", "");

            }

            /*
              decrease c-disk balance counters to free space
            */

            $file_size = valueOf ($record, "size");
            $file_size_round = (int) ($file_size / $cd_clustersize) + 1;

            $cd_clustused = get ("cd/balance", "count>clust", "value") - $file_size_round;
            $cd_filesused = get ("cd/balance", "count>files", "value") - 1;

            if ($cd_clustused < 0) $cd_clustused = 0;
            if ($cd_filesused < 0) $cd_filesused = 0;

            set ("cd/balance", "count>clust", "", "<count>clust<value>$cd_clustused");
            set ("cd/balance", "count>files", "", "<count>files<value>$cd_filesused");

            $error = true; // not really, just to show the message
            $form  = "File has been deleted.";

          }
          else {

            $error = true;
            $form  = "Error deleting file!//The error occured while unlinking the file from "
                   . "the corresponding server's directory. May be a trouble concerning "
                   . "authorizations for that directory: please inform the manager.";

          }

        }
        else {

          if (empty ($conf)) {

            $showlist = false;

            $type = strFromTo ($del . "<", ".", "<");                   // insulate file extension
            $tlen = strlen ($type);                                     // get extension length
            $encn = substr ($del, 5 + $tlen, - 1 - $tlen);              // get encoded file name
            list ($owner, $label) = explode ("/", base62Decode ($encn)); // separate owner and name

            $code = setcode ("pst");

            $list =

              makeframe (

                "delete request", false, false

              )

              .

              makeframe (

                "please confirm deletion of...",

                breakUnspaced ("cd/$type"."s/$encn.$type", $charspernameline, chr (32))
              . "<hr>"
              . "uploaded by: $owner<br>"
              . "label: &#171;$label&#187;"
              . "<hr>"
              . "If you confirm this operation, the file you requested to delete "
              . "will be removed from the c-disk and it will NOT be recoverable. "
              . "Are you sure you want to proceed?", true

              )

              . "<table width=$pw>"
              . "<tr>"
              .  "<td height=24 class=inv align=center>"
              .   "OK TO DELETE?"
              .  "</td>"
              . "</tr>"
              . $bridge
              . "<form action=cdisk.php enctype=multipart/form-data method=post>"
              . "<input type=hidden name=code value=$code>"
              . "<input type=hidden name=del value=\"$del\">"
              . "<input type=hidden name=dir value=\"$dir\">"
              . "<input type=hidden name=sub value=\"" . base62Encode ($sub) . "\">"
              . "<tr>"
              .  "<td>"
              .   "<input class=su style=width:{$pw}px type=submit name=submit value=\"yes, delete it\">"
              .  "</td>"
              . "</tr>"
              . $bridge
              . "<tr>"
              .  "<td>"
              .   "<input class=ky style=width:{$pw}px type=submit name=submit value=\"no, don't delete\">"
              .  "</td>"
              . "</tr>"
              . $shadow
              . "</form>"
              . "</table>";

          }

        }

      }
      else {

        $error = true;

        $form = "Missing authorization!//You cannot delete that file. The owner of that "
              . "file is &quot;$owner&quot;, not you. You may delete only your own files "
              . "from the c-disk, unless you are a moderator or an administrator...";

      }

    }

  }
  else {

    $error = true;
    $form = "You are not logged in.//<a class=pk href=mstats.php>go to login panel</a>";

  }

}

else {

  /*
    upload request:
    - a necessary "thank you" to our member Shadowlord, for the help given to
      fix this part of Postline's code, which previously allowed several exploits.
  */

  if ($submit) {

    /*
      get POST method file informations:
      - file_size holds the size in bytes, typically <= 2 Mb for HTTP POST security limits;
      - file_name holds the true name of the file, client-side, without the path;
      - file_file holds the location where file data was temporarily stored, server-side.
    */

    $file_size = (!empty ($_FILES["file"]["size"])) ? $_FILES["file"]["size"] : 0;
    $file_name = (!empty ($_FILES["file"]["name"])) ? $_FILES["file"]["name"] : "";
    $file_file = ($_FILES["file"]["tmp_name"] != "none") ? $_FILES["file"]["tmp_name"] : "";

    $file_given = (empty ($file_file)) ? false : true;
    $name_given = (empty ($file_name)) ? false : true;
    $size_given = ($file_size > 0) ? is_numeric ($file_size) : false;

    /*
      security check: "is_uploaded_file" returns TRUE if the file was uploaded via HTTP POST:
      this is useful to help ensure that a malicious user hasn't tried to trick this script
      into working on files upon which it shouldn't be working - for instance, /etc/passwd.
    */

    if ($file_given) {
      if (!is_uploaded_file ($file_file)) {

        unlockSession ();
        die (because ('invalid_request'));

      }
    }

    /*
      upload request:
      - user must be logged in for acknowledgement.
    */

    if ($login) {

      /*
        anti-flood check
      */

      if (($lfid != $id) || ($epc > ($lftime + $antifileflood))) {

        /*
          both client-side and server-side name must be non-void for a valid upload request;
          also, file size must be numeric
        */

        if (($file_given) && ($name_given) && ($size_given)) {

          /*
            other than the HTTP file transfer limits (typically 2 Mb),
            the c-disk may have tighter limits for a file's maximum size.
          */

          if ($file_size <= $cd_maxfilesize) {

            /*
              trim blanks, strip slashes and force the file name to all lowercase
            */

            $file_name = strtolower (trim (wStripslashes ($file_name)));

            /*
              separate the file name into every part that's delimited by a dot:
              the last of those parts must be the extension, which must be checked.
            */

            $parts = explode (".", $file_name);
            $noofparts = count ($parts);

            if ($noofparts > 1) {

              /*
                assign $file_type to the last dot-delimited part of the file name;
                remove last part from the $parts array, then assemble file name without ext.
              */

              $file_type = $parts[$noofparts - 1];
              $file_name = implode (".", array_slice ($parts, 0, -1));

              /*
                translate synonyms of known extensions to "universal" extensions
              */

              switch ($file_type) {

                case "jpeg":
                case "pjpeg":

                  $file_type = "jpg";
                  break;

                case "7zip":

                  $file_type = "7z";
                  break;

                case "gzip":

                  $file_type = "gz";

              }

              /*
                check if this extension is allowed on the c-disk
              */

              $allowed = false;
              $types = array ();

              foreach ($cd_filetypes as $t) {

                list ($classification, $extension) = explode ("/", $t);

                if ($extension == $file_type) {

                  $allowed = true;
                  $isimage = ($classification == "image") ? true : false;

                }

                $types[] = strtoupper ($extension);

              }

              if ($allowed) {

                /*
                  escape less than and greater than signs from name (names are shown)
                  and cut any remnants of &lt;/&gt; from scaled-down version of name,
                  because its length cannot exceed $charsperfilename anyway...
                */

                $file_name = str_replace ("<", "&lt;", $file_name);
                $file_name = str_replace (">", "&gt;", $file_name);
                $file_name = cutcuts (substr ($file_name, 0, $charsperfilename));

                /*
                  file name can't be void: because extensions aren't shown in listings,
                  it would turn to a nameless icon, and it'd look rather weird, although
                  this wouldn't really cause malfunctions, and isn't exploitable.
                */

                if (!empty ($file_name)) {

                  /*
                    if it's an image file,
                    get its size in pixels and also ensure it appears well-formed
                  */

                  if ($isimage) {

                    list ($image_width, $image_height, $image_type) = @getimagesize ($file_file);

                    switch ($image_type) {

                      case 1:
                        $valid_image = ($file_type == "gif") ? true : false;
                        break;

                      case 2:
                      case 9:
                      case 10:
                      case 11:
                      case 12:
                        $valid_image = ($file_type == "jpg") ? true : false;
                        break;

                      case 3:
                        $valid_image = ($file_type == "png") ? true : false;
                        break;

                      default:
                        $valid_image = false;

                    }

                    $image_area = $image_width * $image_height;
                    $valid_image = ($image_area > 0) ? $valid_image : false;

                  }
                  else {

                    $valid_image = true;

                  }

                  if ($valid_image) {

                    /*
                      check file name from $_FILES for illegal strings:
                      it was not retrieved via "fget", so it was not automatically checked
                    */

                    halt_if_forbidden ($file_name, $file_file);

                    /*
                      all checks ok - going to save the file:
                      obtain exclusive write access, database manipulation ahead
                    */

                    lockSession ();

                    /*
                      update anti-flood counter: record user ID and file upload time
                      - yeah, don't care if another user overwrites this, I don't mind,
                        as long as it protects from spamming done by a single user; besides,
                        multiple accounts of same person can be relatively easy to spot...
                    */

                    set ("stats/flooders", "entry>file", "", "<entry>file<=>$id+$epc");

                    /*
                      assemble final file name, which is formed by:
                      cd/(filetype directory)/(base62-encode of (username/filename.extension))
                    */

                    $doctype = $file_type . "s";
                    $final_name = "cd/$doctype/" . base62Encode ("$nick/" . $file_name) . ".$file_type";

                    /*
                      verify if a file with that name was ever already saved by this user;
                      if it was, warn the user that the file must be deleted before being
                      saved again to the c-disk: this is a simplification on the matter of
                      updating c-disk balance counters.
                    */

                    if (@file_exists ($final_name) == false) {

                      /*
                        get c-disk balance counters to see if there's enough room for the file
                      */

                      $file_size_round = (int) ($file_size / $cd_clustersize) + 1;

                      $cd_clustused = get ("cd/balance", "count>clust", "value") + $file_size_round;
                      $cd_filesused = get ("cd/balance", "count>files", "value") + 1;

                      if ($cd_clustused <= $cd_disksize) {

                        /*
                          enough c-disk space is available, and checks are over
                          copy the temporary file to its final destination, reporting errors
                        */

                        if (@copy ($file_file, $final_name) == false) {

                          $error = true;

                          $form = "Error storing file...//Couldn't save "
                                . "&quot;$final_name&quot;. There may be troubles "
                                . "with access rights for that directory or file, or "
                                . "some other problems concerning files' management "
                                . "in the server's PHP environment.";

                        }
                        else {

                          /*
                            file was copied successfully:
                            update c-disk balance counters to reflect disk space allocation
                          */

                          set ("cd/balance", "count>clust", "", "<count>clust<value>$cd_clustused");
                          set ("cd/balance", "count>files", "", "<count>files<value>$cd_filesused");

                          /*
                            build directory record for this file, and save it
                          */

                          $dir_record = "<file>$final_name<owner>$nick<stored>$epc";

                          if ($isimage) {

                            $dir_record .= "<width>$image_width";
                            $dir_record .= "<height>$image_height";

                          }

                          $dir_record .= "<size>$file_size";

                          set ("cd/dir_$doctype", "", "", $dir_record);

                          /*
                            if a keyword was given, check file for smiley limitations,
                            and eventually save smiley reference record
                          */

                          $keyword_given = (empty ($keyword)) ? false : true;

                          if ($keyword_given) {

                            /*
                              replace text formatters inside the keyword with underscores
                              - note: formatters not being in the middle of the string were
                                already trimmed by the "fget" call that gotten the keyword,
                                along with formatters different from blanks and tabs...
                            */

                            $keyword = strtr ($keyword, "\x20\t", str_repeat (chr (95), 2));

                            if (strlen ($keyword) >= 2) {

                              /*
                                smiley keywords must begin with either a colon or semicolon
                              */

                              if (($keyword[0] == ":") || ($keyword[0] == ";")) {

                                /*
                                  smileys must be images
                                */

                                if ($isimage) {

                                  /*
                                    check smiley image file size limit
                                  */

                                  if ($file_size <= $smileybytesize) {

                                    /*
                                      check smiley image dimensions limits
                                    */

                                    if (($image_width <= $smileypixelsize) && ($image_height <= $smileypixelsize)) {

                                      /*
                                        check if keyword wasn't previously assigned
                                      */

                                      $existing_keyword = get ("cd/smileys", "keyword>$keyword", "");

                                      if (empty ($existing_keyword)) {

                                        /*
                                          check if there's room for another smiley in the list
                                        */

                                        $smileys = all ("cd/smileys", makeProper);
                                        $n = count ($smileys);

                                        if ($n < $allowedsmileys) {

                                          /*
                                            this particular check looks for conflicts with
                                            existing smileys' keywords, either containing
                                            the newly purposed keyword, or being contained
                                            within the newly purposed keyword: it's necessary
                                            because smileys are parsed by simple str_replace's
                                          */

                                          $embedded_in = "";
                                          $embeds_what = "";

                                          foreach ($smileys as $s) {

                                            $k = valueOf ($s, "keyword");

                                            if (strpos ($k, $keyword) !== false) {

                                              $embedded_in = $k;
                                              break;

                                            }

                                            if (strpos ($keyword, $k) !== false) {

                                              $embeds_what = $k;
                                              break;

                                            }

                                          }

                                          if ((empty ($embedded_in)) && (empty ($embeds_what))) {

                                            /*
                                              if there were no such conflicts,
                                              store new smiley reference record
                                            */

                                            $smiley_record = "<keyword>$keyword<image>$final_name<provider>$nick";
                                            set ("cd/smileys", "", "", $smiley_record);

                                          }
                                          else {

                                            /*
                                              else, explain what happened in particular
                                            */

                                            $error = true;
                                            $form = "Invalid smiley keyword!";

                                            if (empty ($embedded_in)) {

                                              $form .= "//The keyword &quot;$keyword&quot; you have "
                                                    .  "chosen, conflicts with an existing keyword, "
                                                    .  "namely &quot;$embeds_what&quot;, because it "
                                                    .  "CONTAINS the existing keyword.";

                                            }
                                            else {

                                              $form .= "//The keyword &quot;$keyword&quot; you have "
                                                    .  "chosen, conflicts with an existing keyword, "
                                                    .  "namely &quot;$embedded_in&quot;, because it "
                                                    .  "is CONTAINED within the existing keyword.";

                                            }

                                          }

                                        }
                                        else {

                                          $error = true;

                                          $form = "Too many smileys!//The limit is a maximum of "
                                                . $allowedsmileys . " smileys complessively. You "
                                                . "may wait for someone else to delete a smiley, "
                                                . "ask the manager to increase the number of allowed "
                                                . "smileys, or delete some of your own ones, if any.";

                                        }

                                      }
                                      else {

                                        $error = true;

                                        $form = "Smiley keyword conflict!//The file's been uploaded, "
                                              . "but it cannot be associated with &quot;$keyword&quot; "
                                              . "because a smiley is already associated with that keyword. "
                                              . "Please reupload the same file using a different keyword.";

                                      }

                                    }
                                    else {

                                      $error = true;

                                      $form = "That smiley's too large...//There is a $smileypixelsize"
                                            . "x" . "$smileypixelsize pixels limit for smileys' size: "
                                            . "however, the image has been uploaded to the c-disk, only "
                                            . "it has not been associated with the given keyword, "
                                            . "&quot;$keyword&quot;. You may retry after having resized "
                                            . "the image to fit the above limit, and having deleted the "
                                            . "successfully uploaded image file.";

                                    }

                                  }
                                  else {

                                    $error = true;

                                    $form = "That smiley's too big...//There is a $smileybytesize-"
                                          . "byte limit for smiley's image files' size: however, the "
                                          . "image has been uploaded to the c-disk, only it has not "
                                          . "been associated with the given keyword, &quot;$keyword"
                                          . "&quot;. You may retry after having optimized the image "
                                          . "to shorten its file enough.";

                                  }

                                }
                                else {

                                  $error = true;

                                  $form = "Smiley must be an image!//A smiley is a (usually tiny) "
                                        . "picture indicating an emotion in forum messages. But the "
                                        . "keyword used to insert one of them (yours would have been "
                                        . "associated with &quot;$keyword&quot;) must be associated "
                                        . "with an image file.";

                                }

                              }
                              else {

                                $error = true;

                                $form = "Invalid smiley keyword!//To avoid conflicting with "
                                      . "regular text, smiley keywords must ALL begin with a "
                                      . "colon OR a semicolon, followed by upto another "
                                      . ($maxkeywordlen - 1) . " characters: for keywords "
                                      . "such as &quot;8-)&quot;, there's a diffuse convention, "
                                      . "a colon and a word, so the above example could "
                                      . "be registered as &quot;:cool&quot;.";

                              }

                            }
                            else {

                              $error = true;

                              $form = "Invalid smiley keyword!//Must be formed by at least 2 "
                                    . "characters, the first of which is a colon OR a semicolon.";

                            }

                          }

                          /*
                            on successful upload, show updated directory listing
                          */

                          $dir = $doctype;
                          $sub = $nick;

                        }

                      }
                      else {

                        $error = true;

                        $form = "C-DISK IS FULL!//Sorry, there's not enough free space on the "
                              . "c-disk to store that file. You may ask the community manager "
                              . "to expand the disk, or delete some of your largest files, if "
                              . "any, or ask other members to delete something of their own.";

                      }

                    }
                    else {

                      /*
                        show relevant directory listing along with the "file already exists"
                        warning,so the delete button is already presented to the file's owner
                      */

                      $dir = $doctype;
                      $sub = $nick;

                      $error = true;

                      $form = "File already exists!//In your personal folder on the c-disk "
                            . "for this category of files, there already is a file with that "
                            . "exact name. You first need deleting the existing file, then "
                            . "try again uploading it. Use the X button next to the icon to "
                            . "delete the file.";

                    }

                  }
                  else {

                    $error = true;
                    $form = "Invalid image file!";

                  }

                }
                else {

                  $error = true;

                  $form = "Void file name?!//It would be legal for a c-disk file to have a name "
                        . "that's only formed by the file's extension, but it would result in a "
                        . "nameless icon, and... well, I'm not sure people would like this...";

                }

              }
              else {

                $error = true;

                $form = "Format not allowed!//The file you tried to upload "
                      . "doesn't have any of the following extensions:"
                      . "<ul><li>"
                      . implode ("</li><li>", $types)
                      . "</li></ul>"
                      . "...which are the only possible file formats "
                      . "for this board's c-disk.";

              }

            }
            else {

              $error = true;

              $form = "Invalid file name!//The file you tried to upload "
                    . "seems to have no extension.";

            }

          }
          else {

            $error = true;

            $form = "File is too big!//The file you tried to upload "
                  . "exceeds " . (int) ($cd_maxfilesize / 1024) . " Kbytes, "
                  . "which is the maximum allowed size. Try to compress it better.";

          }

        }
        else {

          $error = true;

          $form = "No file specified!//Please fill the file name box "
                . "at the bottom of this frame, by browsing a file from your computer "
                . "or entering the full path to the file in the corresponding text field. "
                . "The file must not exceed " . (int) ($cd_maxfilesize / 1024) . " Kbytes "
                . "and it must be of an allowed type.";

        }

      }
      else {

        /*
          target directory listing, in the output, to root folder:
          just to make the error message stand out better (fewer icons)
        */

        $dir = "";
        $sub = "";

        $error = true;

        $form = "Too early!//You're loading multiple files on the c-disk too quickly. "
              . "You have to wait " . $antifileflood . " seconds "
              . "between each upload: this is an anti-flooding precaution.";

      }

    }
    else {

      $error = true;
      $form = "You are not logged in.//<a class=pk href=mstats.php>go to login panel</a>";

    }

    /*
      discard the temporary file: no matter if the upload was successful or not,
      this attempt is made every time an upload form is submitted, otherwise our
      temporary files would end up clobbering the server's temporary folder...
    */

    if ($file_given) {

      @unlink ($file_file);

    }

  }

}

/*
 *
 * output error messages/panel header
 *
 */

if ($showlist) {

  if ($error) {

    /*
      error report
    */

    list ($frame_title, $frame_contents) = explode ("//", $form);

    $list =

        makeframe (

          $frame_title, false, false

        )

        .

      (($frame_contents)

        ?

        makeframe (

          "error details", $frame_contents, true

        )

        : "");

  }
  else {

    /*
      normal header, with eventual flood warning
    */

    if (($lfid != $id) || ($epc > $lftime + $antifileflood)) {

      $list =

          makeframe (

              "cd"
            . ((empty ($dir)) ? "" : "/$dir")
            . ((empty ($let)) ? "" : "/$let")
            . ((empty ($sub)) ? "" : "/$sub"),

              false, false

          );

    }
    else {

      $list =

          makeframe (

              "cd"
            . ((empty ($dir)) ? "" : "/$dir")
            . ((empty ($let)) ? "" : "/$let")
            . ((empty ($sub)) ? "" : "/$sub"),

              "flood timeout: " . (($lftime + $antifileflood) - $epc) . " sec.", true

          );

    }

  }

  /*
    prepare marker for future totals report
  */

  $list .= "[TOTALS]";

}

/*
 *
 * generate c-disk directory listing for specified location
 *
 */

if ($showlist) {

  $dir_given = (empty ($dir)) ? false : true;

  if ($dir_given) {

    /*
      listing an effective (filetype) directory
    */

    $sub_given = (empty ($sub)) ? false : true;

    if ($sub_given) {

      /*
        listing a member's personal $sub-folder within a certain filetype directory
      */

      additem ("", "up to c-disk root", "cdisk.php", "layout/images/folder.png", "/", "");
      additem ("", "up one level (to &quot;$dir/" . $sub[0] . "&quot;)", "cdisk.php?dir=$dir&amp;let=" . $sub[0] , "layout/images/folder.png", "..", "");

      $t_folders = 2;

      $types = count ($cd_filetypes);

      $q_demultiplier = intval (get ("stats/counters", "counter>signups", "count"))
                      - intval (get ("stats/counters", "counter>resigns", "count"));

      $q_demultiplier = ($q_demultiplier >= $types) ? $q_demultiplier / $types : $q_demultiplier;
      $q_demultiplier = (float) $q_demultiplier;

      /*
        check if querying a real directory or orphan files
      */

      if ($dir != "orphans") {

        /*
          list only files matching this file type, and this member as the owner
        */

        $file = getclass ($dir, $sub);

      }
      else {

        /*
          list only orphan files mathing this (former) owner
        */

        $orphan = array ();

        foreach ($cd_filetypes as $t) {

          list ($classification, $extension) = explode ("/", $t);
          $orphan = array_merge ($orphan, all_orphans ($extension . "s"));

        }

        $file = array ();

        foreach ($orphan as $o) {

          if (valueOf ($o, "owner") == $sub) $file[] = $o;

        }

      }

      /*
        sort files as indicated
      */

      switch ($sort) {

        case "date":

          usort ($file, "bydate");
          break;

        case "name":

          usort ($file, "byname");
          break;

        case "size":

          usort ($file, "bysize");

      }

      addlist ($file);

      $may_be_sorted = true;

    }
    else {

      /*
        listing either an alphabetical list of initials for members who have files of this type,
        or the alphabetical list of members within one of the above folders marked by initials:
        organizing initials highly reduces listings when members' list gets rather long...
        - note: all these folders are virtual, they don't exist on the server.
      */

      $let_given = (empty ($let)) ? false : true;

      if ($let_given) {

        /*
          cdup icons when listing members folders
        */

        additem ("", "up to c-disk root", "cdisk.php", "layout/images/folder.png", "/", "");
        additem ("", "up one level (to &quot;$dir&quot;)", "cdisk.php?dir=$dir", "layout/images/folder.png", "..", "");

      }
      else {

        /*
          cdup icons when listing initial folders
        */

        additem ("", "up to c-disk root", "cdisk.php", "layout/images/folder.png", "/", "");
        additem ("", "up one level (to c-disk root)", "cdisk.php", "layout/images/folder.png", "..", "");

      }

      /*
        in any cases, that's 2 folders so far
      */

      $t_folders = 2;

      /*
        $dir can be an effective file-type folder (a real folder of the cd directory server-side)
        or an alias for a virtual folder executing pre-defined queries ("today", "last_week"...)
      */

      $iseffective = false;

      foreach ($cd_filetypes as $t) {

        list ($classification, $extension) = explode ("/", $t);

        if ($extension . "s" == $dir) {

          $iseffective = true;
          break;

        }

      }

      if (($iseffective) || ($dir == "orphans")) {

        if ($iseffective) {

          /*
            $dir is effective:
            - get all files from the corresponding directory file
          */

          $file = all ("cd/dir_$dir", makeProper);

        }
        else {

          /*
            $dir is evidently the "orphans" query:
            - get all files from all effective directories that classify as orphans
          */

          $file = array ();

          foreach ($cd_filetypes as $t) {

            list ($classification, $extension) = explode ("/", $t);
            $file = array_merge ($file, all_orphans ($extension . "s"));

          }

        }

        /*
          initialize a void array of member nicknames ($owner) who appear to have at least 1 file
          initialize a void array of counters ($objects) to report how many files each member has
        */

        $owner = array ();
        $objects = array ();

        /*
          if $let is not provided, it's an alphabetical listing of initials,
          else it's going to list the "contents" of one of those initial-marked folders
        */

        if (empty ($let)) {

          foreach ($file as $f) {

            /*
              get initial of file owner, use it as a key to the $owner array, because
              there must be no redundant initials even if more records correspond to an initial
            */

            $i = substr (valueOf ($f, "owner"), 0, 1);

            $owner[$i] = $i;
            $objects[$i] ++;

          }

        }
        else {

          foreach ($file as $f) {

            /*
              get entire nickname of file owner, use it as a key to the $owner array, because
              there must be no redundant nicknames even if more records correspond to a nickname:
              apart from this, only consider nicknames matching the given initial for listing...
            */

            $n = valueOf ($f, "owner");

            if ($n[0] == $let) {

              $owner[$n] = $n;
              $objects[$n] ++;

            }

          }

        }

        /*
          sort either initials or nicknames alphabetically
        */

        sort ($owner);

        foreach ($owner as $o) {

          /*
            get count of files within a given "virtual" folder,
            and show it out creating a tooltip for its icon...
          */

          $n = $objects[$o];
          $tt = "contains $n file" . (($n > 1) ? "s" : "");

          /*
            when listing initials, provide a link to "enter" the initial-marked folder,
            else provide a link to enter the $sub-folder corresponding to the given nickname
          */

          if (empty ($let)) {

            additem ("", $tt, "cdisk.php?dir=$dir&amp;let=$o", "layout/images/folder.png", $o, "");

          }
          else {

            additem ("", $tt, "cdisk.php?dir=$dir&amp;sub=" . base62Encode ($o), "layout/images/folder.png", $o, "");

          }

          /*
            also increase total displayed folders counter
          */

          $t_folders ++;

        }

      }

      if (($dir == "today") || ($dir == "last_week")) {

        /*
          $dir is an alias for a predefined chronological query:
          - check which, and set the upload time ranges for filtering...
        */

        switch ($dir) {

          case "today":

            $epc_lo = $epc - 86400;
            $epc_hi = $epc;
            break;

          case "last_week":

            $epc_lo = $epc - 7 * 86400;
            $epc_hi = $epc;

        }

        /*
          process all c-disk directories, one per time to avoid hogging too much memory,
          and keep only filtered results in memory (they might be much less than the whole).
        */

        $file = array ();

        foreach ($cd_filetypes as $t) {

          list ($classification, $extension) = explode ("/", $t);
          $file = array_merge ($file, all_between ($extension . "s", $epc_lo, $epc_hi));

        }

        /*
          sort files as indicated
        */

        switch ($sort) {

          case "date":

            usort ($file, "bydate");
            break;

          case "name":

            usort ($file, "byname");
            break;

          case "size":

            usort ($file, "bysize");

        }

        /*
          generate listing
        */

        addlist ($file);

        $may_be_sorted = true;

      }

      /*
        if $dir doesn't match any of the above conditions, the listing will remain void,
        apart from the cdup links, but it's not a serious issue to worry about...
      */

    }

  }
  else {

    $sub_given = (empty ($sub)) ? false : true;

    if ($sub_given) {

      /*
        this is a member's personal folder: it's a virtual folder querying for all files,
        of any types, that have been uploaded by the same member; because it's typically
        accessed from a button in someone's profile, the ".." link points to that profile.
      */

      $nlab = ($sub == $nick) ? "your" : "$sub's";

      additem ("", "up to c-disk root", "cdisk.php", "layout/images/folder.png", "/", "");
      additem ("", "return to $nlab profile", "profile.php?nick=" . base62Encode ($sub), "layout/images/cdback.png", $sub, "");

      $t_folders = 2;

      /*
        list files matching $sub as the owner, found in all c-disk effective directories
      */

      $file = array ();
      $types = 0;

      foreach ($cd_filetypes as $t) {

        list ($classification, $extension) = explode ("/", $t);
        $file = array_merge ($file, getclass ($extension . "s", $sub));

        $types ++;

      }

      $q_demultiplier = intval (get ("stats/counters", "counter>signups", "count"))
                      - intval (get ("stats/counters", "counter>resigns", "count"));

      $q_demultiplier = ($q_demultiplier >= $types) ? $q_demultiplier / $types : $q_demultiplier;
      $q_demultiplier = (float) $q_demultiplier;

      /*
        sort files as indicated
      */

      switch ($sort) {

        case "date":

          usort ($file, "bydate");
          break;

        case "name":

          usort ($file, "byname");
          break;

        case "size":

          usort ($file, "bysize");

      }

      addlist ($file);

      $may_be_sorted = true;

    }
    else {

      /*
        this... heh, this is the c-disk's "root directory",
        and holds links to all its directories (one per file type),
        plus the virtual folders for pre-defined queries...
      */

      $t_folders = 4;

      foreach ($cd_filetypes as $t) {

        list ($classification, $extension) = explode ("/", $t);

        additem (

          "",
          "c-disk folder for .$extension {$classification}s",
          "cdisk.php?dir={$extension}s",
          "layout/images/{$classification}s.png",
          "{$extension}s",
          ""

        );

        $t_folders ++;

      }

      additem ("", "files uploaded within LAST 24 HOURS", "cdisk.php?dir=today", "layout/images/chrono.png", "today", "");
      additem ("", "files uploaded within LAST 7 DAYS", "cdisk.php?dir=last_week", "layout/images/chrono.png", "last_week", "");
      additem ("", "files whose owners have now left the community", "cdisk.php?dir=orphans", "layout/images/orphans.png", "orphans", "");

      $t_files = intval (get ("cd/balance", "count>files", "value"));
      $t_clust = intval (get ("cd/balance", "count>clust", "value"));

    }

  }

  /*
    prepend totals (where I left their marker):
    - 440 is the width, in pixels, of "layout/images/votebar1.gif", used to fill the bar,
      which was originally designed to be used in polls, but which may fit here as well.
  */

  $quota_percent = (int) ((100 * $t_clust * $q_demultiplier) / $cd_disksize);

  $quota_indicator = (int) (($pw * $t_clust * $q_demultiplier) / $cd_disksize) - 440;
  $quota_indicator = ($quota_indicator > ($pw - 440)) ? $pw - 440 : $quota_indicator;

  $list =

      str_replace (

          "[TOTALS]",

          "<table width=$pw style=margin-bottom:7px>"
        .  "<td class=inv height=24 align=center>"
        .   number_format ($t_files) . chr (32) . "file" . (($t_files != 1) ? "s" : "")
        .   chr (32) . "(" . number_format ((int) ($t_clust / (1024 / $cd_clustersize))) . chr (32) . "K)"
        .   chr (32) . "+" . chr (32) . number_format ($t_folders) . chr (32) . "dir" . (($t_folders != 1) ? "s" : "")
        .  "</td>"
        . "</table>"
        . "<table width=$pw style=margin-bottom:7px>"
        .  "<td>"
        .   "<table width=$pw class=rf style=\""
        .   "background-image:url(layout/images/votebar1.gif);"
        .   "background-position:$quota_indicator"."px 0px;"
        .   "background-repeat:repeat-y\">"
        .    "<td style=color:black>"
        .     "&nbsp;E"
        .    "</td>"
        .    "<td align=center style=color:black>"
        .     "used quota: $quota_percent % est."
        .    "</td>"
        .    "<td align=right style=color:black>"
        .     "F&nbsp;"
        .    "</td>"
        .   "</table>"
        .  "</td>"
        . "</table>",

          $list

      );

  /*
    append sort method select, if applicable
  */

  if ($may_be_sorted) {

    $form = "<table width=$pw>"
          . "<td height=24 class=inv align=center>Sort objects by:</td>"
          . "</table>"
          . $inset_bridge
          . $opcart
          . "<form name=resort action=cdisk.php enctype=multipart/form-data method=get>"
          . "<input type=hidden name=dir value=\"$dir\">"
          . "<input type=hidden name=let value=$let>"
          . "<input type=hidden name=sub value=" . base62Encode ($sub) . ">"
          . "<td class=in align=center style=padding:2px>"
          . "<input type=radio name=sort value=date" . (($sort == "date") ? chr (32) . "checked" : "") . " onClick=document.resort.submit()> date &nbsp;"
          . "<input type=radio name=sort value=name" . (($sort == "name") ? chr (32) . "checked" : "") . " onClick=document.resort.submit()> name &nbsp;"
          . "<input type=radio name=sort value=size" . (($sort == "size") ? chr (32) . "checked" : "") . " onClick=document.resort.submit()> size"
          . "</td>"
          . "</form>"
          . $clcart
          . $inset_shadow;

  }
  else {

    $form = "";

  }

  /*
    append upload form:
    ...unless this user isn't logged in:
    it this case it's useless to show the form...
  */

  if ($login) {

    /*
      compute width of fields considering 80 pixels for field labels
    */

    $fw = $pw - 80;

    /*
      generate file upload form HTML
    */

    $form .= "<table width=$pw>"
          .   "<td height=24 class=inv align=center>"
          .    "Save to community disk:"
          .   "</td>"
          .  "</table>"
          .  $inset_bridge
          .  $opcart
          .  "<form action=cdisk.php enctype=multipart/form-data method=post>"
          .  "<input type=hidden name=MAX_FILE_SIZE value=$cd_maxfilesize>"
          .  "<tr>"
          .   "<td class=fs>"
          .    "<span title=\"FILE NAME: pick a file from your PC (upto " . ((int)($cd_maxfilesize / 1024)) . " Kbytes)\">"
          .     "<input type=file name=file style=width:".($iw-2)."px size=".(intval($pw/11)).">"
          .    "</span>"
          .   "</td>"
          .  "</tr>"
          .  $clcart
          .  $inset_bridge
          .  $opcart
          .  $fspace
          .  "<tr>"
          .   "<td width=80 align=right class=in>"
          .    "as smiley:&nbsp;&nbsp;"
          .   "</td>"
          .   "<td class=in valign=middle>"
          .    "<span title=\"optional SMILEY KEYWORD\">"
          .     "<input class=mf style=width:{$fw}px type=text name=keyword value=\"\" maxlength=$maxkeywordlen>"
          .    "</span>"
          .   "</td>"
          .  "</tr>"
          .  $fspace
          .  $clcart
          .  $inset_bridge
          .  "<table width=$pw>"
          .   "<tr>"
          .    "<td>"
          .     "<input class=su style=width:{$pw}px type=submit name=submit value=\"store file\">"
          .    "</td>"
          .   "</tr>"
          .   $shadow
          .  "</form>"
          .  "</table>";

  }

}

/*
 *
 * template initialization
 *
 */

$cdisk = str_replace

  (

    array (

      "[LIST]",
      "[FORM]",
      "[PERMALINK]"

    ),

    array (

      $list,
      $form,
      $permalink

    ),

    $cdisk

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page upon showing or
 * hiding the chat frame, and for the online members list links (unless no-spy flag checked).
 *
 */

include ("setlfcookie.php");

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($cdisk));



?>
