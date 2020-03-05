<?php



/*************************************************************************************************

            Postlïne, PHP community engine, version 2006.1.30/r

            This program is free software; you can redistribute it and/or modify
            it under the terms of the GNU General Public License as published by
            the free Software Foundation; either version 2 of the License, or
            (at your option) any later version.

                                                  Written by Alessandro Ghignola
                               Copyright (C) 2003-2008 Home Sweet Pixel software

*************************************************************************************************/



/*
 *
 * this is a command-line script, to be executed from the chat input line:
 *
 * from_cli is defined only when the script is launched because its name has been typed into
 * the chat prompt/command line, and ONLY if the member was acknowledged (is logged in, and
 * has a precise identity given by $nick, and a logical id $id). This is very important.
 *
 */

if (!defined ("from_cli")) {

  exit ("NO STANDALONE EXECUTION");

}

$todo = strtolower ($speech);

/*
 *
 * get target nickname and arguments
 *
 */

$target = (strpos ($todo, chr (32) . "all") === false)

        ? ucfirst (strFromTo ($todo, "&quot;", "&quot;"))
        : "<all>";

$myself = (strpos ($todo, chr (32) . "me") === false) ? (($nick == $target) ? true : false) : true;
$random = (strpos ($todo, chr (32) . "random") === false) ? false : true;
$target = ($myself) ? $nick : $target;

/*
 *
 * script duties
 *
 */

if (empty ($target)) {

  /*
    if neither double-quoted member nickname nor the "all" keyword (not quoted) were specified,
    report intended syntax
  */

  $speech = ($is_admin === false)

        ? "/unpomelize ($nick): syntax is: /unpomelize [\"nickname\" | me] [random]"
        : "/unpomelize ($nick): syntax is: /unpomelize [\"nickname\" | me | all] [random]";

}

else {

  /*
    executing pomelize request
  */

  if (($myself) || ($is_admin) || ($is_mod)) {

    lockSession ();

    if ($target == "<all>") {

      set ("stats/counters", "counter>pomelizer", "", "<counter>pomelizer<state>off");

      $speech = "Game over! Community has been globally un-pomelized by $nick... talk freely...";

    }

    else {

      $record = get ("stats/sessions", "nick>$target", "");
      $record_exists = empty ($record) ? false : true;

      if ($record_exists) {

        set ("stats/sessions", "nick>$target", "pomelized", "no");

        $speech = ($myself)

          ? "$target self-unpomelized!..."
          : "$target unpomelized by $nick!...";

      }

      else {

        $speech = "/unpomelize ($nick): member \"$target\" doesn't appear to be logged in.";

      }

    } // all members versus specific member

  }

  else {

    $speech = "/unpomelize ($nick): only mods and admins may unpomelize"
            . chr (32) . "other members, but you could unpomelize yourself...";

  }

} // show syntax versus arguments given



?>
