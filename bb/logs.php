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
 * get parameters:
 *
 * 4-digit year to explore, strictly integer,
 * which defaults to current year if missing...
 *
 */

$y_in = intval (fget ("y", 4, ""));
$ynow = intval (gmdate ("Y", $epc)) - 1;
$year = ($y_in < ($ybase - 1)) ? $ynow - ($ybase - 1) : $y_in - ($ybase - 1);

/*
 *
 * setup paging links:
 *
 * start from epoch year (first year of Postline's chat logging),
 * end with current year (the last "page" of this navigation bar)
 *
 */

$years = $ynow  // current year
       - $ybase // minus "epoch" year
       + 1;     // plus 1 because, like page numbers, years are "visually" based at 1

if ($years == 0) {

  $paging = "year:" . chr (32)
          . "<span class=alert>"
          .  $ybase
          . "</span>";

}
else {

  $div = ($years > $pp_years) ? " ... " : chr (32);

  $paging =

        (($year == 0)

          ? "year: <span class=alert>$ybase</span>"                             // already page 1
          : "year: <a class=fl href=logs.php?y=" . ($ybase - 1) . ">$ybase</a>" // link to page 1

        )

          . $div;

  $number = ($year < $pp_years) ? 1 : $year - $pp_years + 1;
  $nlimit = ($number + (2 * $pp_years - 1)  >= $years) ? $years : $number + (2 * $pp_years - 1);

  while ($number < $nlimit) {

    $paging .= chr (32)
            .

          (($year == $number)

            ?  "<a class=alert>"
            :  "<a class=fl href=logs.php?y=" . ($number + $ybase - 1) . ">"

          )

            .  ($number + $ybase) . "</a>";

    $number ++;

  }

  $paging .= $div
          .

        (($year == $years)

          ? "<a class=alert>" . ($years + $ybase) . "</a>"
          : "<a class=fl href=logs.php?y=" . ($years + $ybase - 1) . ">" . ($years + $ybase) . "</a>"

        );

}

/*
 *
 * $m will be the output of the page, initialized with a generic header
 *
 */

$m =     "<div class=td>"
   .     "</div>"

   .     "<center>";

/*
 *
 * retrieve all .html filenames in the logs folder, except "index.html", building array $file
 *
 */

$file = array ();

if ($dh = @opendir ("./logs/" . ($year + $ybase) . "/")) {

  while (($f = @readdir ($dh)) !== false) {

    list ($name, $ext) = explode (".", $f);
    if (($name != "index") && ($ext == "html")) $file[] = $name;

  }

  closedir ($dh);

}

/*
 *
 * sort filenames in reverse order:
 * filenames are in the form yyyy_mm_dd, so this will result in reverse chronological order
 *
 */

rsort ($file);

/*
 *
 * display listing as a calendar
 *
 */

$monthname = array (

        "01" => "January",
        "02" => "February",
        "03" => "March",
        "04" => "April",
        "05" => "May",
        "06" => "June",
        "07" => "July",
        "08" => "August",
        "09" => "September",
        "10" => "October",
        "11" => "November",
        "12" => "December"

);

$dayname = array (

        "sun",
        "mon",
        "tue",
        "wed",
        "thu",
        "fri",
        "sat"

);

$fileno = 0;                            // counts files in array, progressively from 0
$todate = gmdate ("Y_m_d", $epc);       // today's date in yyyy_mm_dd, to mark today's log

while (!empty ($file[$fileno])) {

  $f = $file[$fileno];

  /*
    retrieve date of currently examined file
  */

  list ($year, $month, $day) = explode ("_", $f);

  $literal_month = $monthname[$month] . chr (32) . $year;

  /*
    open month table
  */

  $m .=

    (($fileno == 0)

     ?   ""
     :   "<div class=ld>"
     .   "</div>"

     )

     .   "<table class=ot align=center>"
     .    "<td>"
     .     "<table class=it>"
     .      "<tr>"
     .       "<td colspan=7 align=center>"
     .        "<table align=center>"
     .         "<tr>"
     .          "<td>"
     .           "<img src=layout/images/thread.png width=48 height=48>"
     .          "</td>"
     .          "<td class=tt>"
     .           $literal_month
     .          "</td>"
     .         "</tr>"
     .        "</table>"
     .       "</td>"
     .      "</tr>"
     .      "<tr>";

  /*
    create day-of-week legend, leave <tr> open to prepare for first row of month's table
  */

  $w = intval (gmdate ("w", strtotime ("31 $literal_month")));

  for ($x = 0; $x < 7; $x ++) {

    $m .=    "<td class=dl>"
       .      $dayname[$w]
       .     "</td>";

    $w = ($w) ? $w - 1 : 6;

  }

  /*
    append month's table, in rows of 7 columns each:
    - $dd counts back from 31, the highest possible value of $day
    - $rw, $cl count current row and column, but start as -1, 7 to cause a new <tr> to be streamed
  */

  $dd = 31;
  $rw = -1;
  $cl = 7;

  $file_year = $year;
  $file_month = $month;
  $file_day = $day;

  while ($dd) {

    /*
      end-of-row check
    */

    if ($cl == 7) {

      $m .= "</tr>"
         .  "<tr>";

      $cl = 0;
      $rw ++;

    }

    /*
      list file if date matches this cell,
      else leave this cell void...
    */

    if (($year == $file_year) && ($month == $file_month) && ($dd == $file_day)) {

      /*
        list file size in bytes, or kylobytes if greater than 1024 bytes
      */

      $size = wFilesize ("logs/$year/$f.html");
      $size = ($size >= 1024) ? (int) ($size / 1024) . chr (32) . "k" : $size;

      /*
        append cell:
        width and height are only appended to the <td> tags where appropriate, to save bandwidth
      */

      $m .=  "<td class=dn>"
         .

        (($f == $todate)

         ?    "<a class=to href=logs/$year/$f.html>"
         :    "<a href=logs/$year/$f.html>"

         )

         .     $dd
         .    "</a>"
         .    "<div class=ds>"
         .     $size
         .    "</div>"
         .   "</td>";

      /*
        proceed to next file:
        get date parts in different variables, to see if the file will be part of the same month,
        and when finding a file that's no longer part of this month, the loop will go on to fill
        all remaining cells in the current month's table, but $fileno will keep indicating the
        first file that didn't match current month, so it'll be ready for listing next month
      */

      $fileno ++;
      $f = $file[$fileno];
      list ($file_year, $file_month, $file_day) = explode ("_", $f);

    }
    else {

      /*
        while file date doesn't match, add void cell
      */

      $m .=  "<td class=dn>"
         .    "-"
         .   "</td>";

    }

    /*
      next month: countdown until zero
      next column: will wrap to zero upon reaching 7
    */

    $dd --;
    $cl ++;

  }

  /*
    close month table, leave some space before next month
  */

  $m .=   "</table>"
     .   "</td>"
     .  "</table>";

}

$m .= "</center>"
   .  "<div class=bd>"
   .  "</div>";

/*
 *
 * determine permalink presence (present if no arguments given)
 *
 */

$args_not_given = ((count ($_GET)) || (count ($_POST))) ? false : true;

/*
 *
 * permalink initialization
 *
 */

$permalink = (($enable_permalinks) && (($args_not_given) || ($y_in == $ynow)))

  ? str_replace

      (

        "[PERMALINK]", $perma_url . "/" . $chatlogs_keyword, $permalink_model

      )

  : "";

/*
 *
 * template initialization
 *
 */

$logsmenu =

  str_replace (

    array

      (

        "[M]",
        "[DIR]",
        "[PAGING]",
        "[PERMALINK]"

      ),

    array (

      $m,
      "<a class=fl href=logs.php>chat logs</a>",
      $paging,
      $permalink

    ),

    $logsmenu

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

echo (pquit ($logsmenu));



?>
