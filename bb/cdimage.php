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
require ("suitcase.php");
require ("sessions.php");
require ("template.php");

/*
 *
 * get image file name
 *
 */

$img = fget ("name", 1000, "");

/*
 *
 * build image presentation's html output
 *
 */

if (@file_exists ($img)) {

  /*
    determining which folder contains this file, basing on file name:
    - becase it looks for an image, this can be gifs, jpgs or pngs;
    - at the same time, it will insulate the base-62 encoded name.
  */

  $f_n = strFromTo ($img, "cd/gifs/", ".");
  $f_d = "gifs";

  if (empty ($f_n)) {

    $f_n = strFromTo ($img, "cd/jpgs/", ".");
    $f_d = "jpgs";

  }

  if (empty ($f_n)) {

    $f_n = strFromTo ($img, "cd/pngs/", ".");
    $f_d = "pngs";

  }

  /*
    decode file name, build remote URL corresponding to this file
  */

  $f_n = base62Decode ($f_n);
  $f_u = $sitedomain . $img;

  /*
    retrieve image record:
    from there, retrieve width, height and owner
  */

  $i_r = get ("cd/dir_$f_d", "file>$img", "");
  $i_w = valueOf ($i_r, "width");
  $i_h = valueOf ($i_r, "height");
  $f_o = valueOf ($i_r, "owner");

  /*
    check if a smiley keyword is associated with this file,
    for later informing about it...
  */

  $f_k = get ("cd/smileys", "image>$img", "keyword");
  $f_k = (empty ($f_k)) ? "none" : $f_k;

  /*
    - assemble frame title (used for informations' frame within template)
    - create image tag (replaces [IMG] in template)
  */

  $ttl = "cd/$f_d/" . $f_n . "." . substr ($f_d, 0, 3);
  $img = "<iframe style=\"border:0px;width:100%;height:100%\" src=imagelet.php?i=$img></iframe>";

  /*
    assemble informations' frame
  */

  $inf = "public URL: <a target=_blank href=$f_u>$f_u</a><br>"
       . "saved by <a target=pst href=profile.php?nick=" . base62Encode ($f_o) . ">$f_o</a> "
       . "on " . gmdate ("F d, Y H:i", valueOf ($i_r, "stored")) . " (emote: $f_k)<br>"
       . "dimensions: {$i_w} x {$i_h} pixels, " . (intval (valueOf ($i_r, "size") / 1024)) . " KiB";

}
else {

  /*
    warning when the file has been deleted from the corresponding folder,
    although, evidently, not from the c-disk's corresponding directory file:
    so, this would actually represent a database inconsistence.
  */

  $ttl = "cd/?";
  $inf = "no such image (anymore?)";
  $img = "<iframe style=\"border:0px;width:100%;height:100%\" src=imagelet.php?i=dummy></iframe>";

}

/*
 *
 * template initialization
 *
 */

$inf = ($hii == "yes") ? voidString : makeframe ("image informations", $inf, false);

$cdimage =

         str_replace (

           array (

             "[IMG]",
             "[TTL]",
             "[INF]",
             "[PERMALINK]"

           ),

           array (

             $img,
             makeframe ($ttl, false, false),
             $inf,
             $permalink

           ),

           $cdimage

         );

/*
 *
 * saving navigation tracking informations for recovery of central frame page
 * upon showing or hiding the chat frame (via cookie: this also works for guests)
 *
 */

include ("setrfcookie.php");

/*
 *
 * saving navigation tracking informations for the online members list links
 * (unless no-spy flag checked)
 *
 */

if (($login == true) && ($nspy != "yes")) {

  $this_uri = substr ($_SERVER["REQUEST_URI"], 0, 100);

  if (getlsp ($id) != $this_uri) {

    setlsp ($id, $this_uri);

  }

}

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($cdimage));



?>
