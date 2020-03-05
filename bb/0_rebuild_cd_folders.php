<html>
<head>

<style type="text/css">
<!--

  body {

    margin: 5px;
    background-color: black;
    color: lightgrey;
    scrollbar-face-color: black;
    scrollbar-shadow-color: black;
    scrollbar-highlight-color: black;
    scrollbar-3dlight-color: black;
    scrollbar-darkshadow-color: black;
    scrollbar-track-color: #e4e0e8;
    scrollbar-arrow-color: #e4e0e8;

  }

  pre {

    font: 15px 'courier new', 'bitstream vera sans mono', monospace;
    letter-spacing: -1px;

  }

//-->
</style>

</head>
<body>

<pre>CD FOLDERS BUILDER (0_rebuild_cd_folders.php)</pre>

<pre>
TO BE USED WHEN:

- after rolling back to an outdated backup of the 'cd' folder, or of
  some files within it, virtual directories may be out of synch with
  respect to 'real' directories (those holding the real files): such
  problems could be solved by manually deleting the affected virtual
  directories (i.e. 'dir_gifs', 'dir_jpgs', etc...) and then running
  this script to rebuild them from scratch, by analyzing files saved
  into the real directories of the c-disk; if the 'balance' file was
  also outdated, deleting all files from the 'cd' folder (except for
  the 'smileys' list) before running this script, would also rebuild
  the 'balance' file, which holds allocated space counters

This script will rebuild any folders of the c-disk virtual directory
that may have been lost, starting from the files found in the folder
that corresponds to the lost virtual directory.
</pre>

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

require ("settings.php");
require ("sessions.php");
require ("suitcase.php");

/*
 *
 * ask confirmation
 *
 */

if (isset ($_POST["submit"])) {

  echo ("<pre>RUNNING...</pre>");

}
else {

  unlockSession ();

  die (

      "<pre>"
    . "<form action=0_rebuild_cd_folders.php enctype=multipart/form-data method=post>"
    . "<input type=submit name=submit value=\"LAUNCH C-DISK FOLDERS BUILDER\">"
    . "</form>"
    . "</pre>"

  );

}

/*
 *
 * checking overall board locker
 *
 */

if (@file_exists ("widgets/sync/system_locker") == false) {

  unlockSession ();
  die ("<pre>BOARD IS NOT LOCKED, SO QUITTING</pre>");

}

/*
 *
 * this script is generally restricted to administrators
 *
 */

if ($is_admin == false) {

  unlockSession ();
  die ("<pre>ADMINISTRATION RIGHTS REQUIRED</pre>");

}

/*
 *
 * check every virtual directory of the c-disk:
 * if one is empty, analyze files in the corresponding directory and rebuild the virtual one
 *
 */

lockSession ();

$conversion_run = true;
$cd_clustused = 0;
$cd_filesused = 0;

foreach ($cd_filetypes as $t) {

  list ($classification, $extension) = explode ("/", $t);

  $isimage = ($classification == "image") ? true : false;
  $existing_records = all ("cd/dir_{$extension}s", makeProper);

  if (count ($existing_records) > 0) {

    echo ("<pre>PASSED: cd/dir_{$extension}s</pre>");

    $conversion_run = false;

  }
  else {

    $void = true;

    if ($dh = @opendir ("./cd/{$extension}s")) {

      while (($file = @readdir ($dh)) !== false) {

	list ($file_name, $file_extension) = explode (".", $file);

	if ($file_extension == $extension) {

	  list ($owner, $filename) = explode ("/", base62Decode ($file_name));

	  $filesize = wFilesize ("./cd/{$extension}s/$file");
	  $fileclusters = (int) ($filesize / $cd_clustersize) + 1;
	  $filesize_kb = (int) ($filesize / 1024) + 1;

	  if ($isimage) {

	    list ($w, $h) = @getimagesize ("./cd/{$extension}s/$file");

	  }
	  else {

	    $w = "N/";
	    $h = "A";

	  }

	  $date = @filemtime ("./cd/{$extension}s/$file");
	  $datestring = gmdate ("M d, Y H:i", $date);

	  set (

	      "cd/dir_{$extension}s",

	      "",
	      "",

	      "<file>cd/{$extension}s/$file"
	    . "<owner>$owner"
	    . "<stored>$date"
	    .

	  (($isimage)

	    ? "<width>$w"
	    . "<height>$h"
	    : ""

	    )

	    . "<size>$filesize"

	  );

	  echo (

	      "<pre>"
	    . "\tLISTING $owner/$filename, $filesize_kb KB, $fileclusters clusters\n"
	    . "\tIMAGE RESOLUTION: $w x $h\n"
	    . "\tFILE STORED: $datestring"
	    . "</pre>"

	  );

	  $cd_clustused += $fileclusters;
	  $cd_filesused ++;

	  $void = false;

	}

      }

      closedir ($dh);

    }

    if ($void) {

      echo ("<pre>VOID: cd/dir_{$extension}s</pre>");

    }
    else {

      echo ("<pre>CREATING: cd/dir_{$extension}s</pre>");

    }

  }

}

/*
 *
 * conversion runs are acknowledged by the fact that no directories were existing,
 * and this is taken as a signal that the whole c-disk balance must be reconstructed
 *
 */

if ($conversion_run) {

  writeTo ("cd/balance", voidString);

  set ("cd/balance", "", "", "<count>clust<value>$cd_clustused");
  set ("cd/balance", "", "", "<count>files<value>$cd_filesused");

  echo ("<pre>CREATING BALANCE COUNTERS:</pre>");
  echo ("<pre>\tTOTAL USED CLUSTERS: $cd_clustused</pre>");
  echo ("<pre>\tTOTAL UPLOADED FILES: $cd_filesused</pre>");

}

/*
 *
 * done, releasing database lock to flush the cache and let the board resume normal operation
 *
 */

unlockSession ();
echo ("<pre>DONE</pre>");



?>
