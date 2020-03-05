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

/*
 *
 * setup auxiliary variables and flags
 *
 */

$msg = "Welcome to " . ucfirst ($sitename) . "!";       // default greeting message

$genw = "";                     // no general warnings
$update_cli = "";               // CLI update javascript, set only upon loggin in from this panel
$cnf = "";                      // reg. confirm code: set to string "must" while awaiting code
$nol = false;                   // true on registration denial: banned and override pw. is wrong
$wlf = false;                   // true on permanent nickname ban (login denial)
$reg = false;                   // true after successful registration: withelds reg. form
$foc = "login.nick";            // auto-focused field in forms: defaults to login nickname

/*
 *
 * define fingerprint fields:
 * it's a javascript inspecting some typical attributes in an attempt to spot duplicated accounts
 *
 */

$fingerprint_fields =
    "\n"
  . "<script type=text/javascript>\n"
  . "<!--\n"
  . "function fp(){"
  .   "var bw=0;"
  .   "var bh=0;"
  .   "var tz=0;"
  .   "if(parseInt(navigator.appVersion)>3){"
  .     "if(navigator.appName.indexOf(\"Microsoft\")!=-1){"
  .       "bw=parent.document.documentElement.clientWidth;"
  .       "bh=parent.document.documentElement.clientHeight;"
  .       "if(!bw)bw=parent.document.body.clientWidth;"
  .       "if(!bh)bh=parent.document.body.clientHeight;"
  .     "}"
  .     "else{"
  .       "bw=top.innerWidth;"
  .       "bh=top.innerHeight;"
  .     "}"
  .   "}"
  .   "nD=new Date();"
  .   "tz=nD.getTimezoneOffset();"
  .   "document.write('<input type=hidden name=bw value='+bw+'>');"
  .   "document.write('<input type=hidden name=bh value='+bh+'>');"
  .   "document.write('<input type=hidden name=sw value='+screen.width+'>');"
  .   "document.write('<input type=hidden name=sh value='+screen.height+'>');"
  .   "document.write('<input type=hidden name=aw value='+screen.availWidth+'>');"
  .   "document.write('<input type=hidden name=ah value='+screen.availHeight+'>');"
  .   "document.write('<input type=hidden name=cd value='+screen.colorDepth+'>');"
  .   "document.write('<input type=hidden name=tz value='+tz+'>');"
  .   "document.write('<input type=hidden name=mt value='+navigator.mimeTypes.length+'>');"
  .   "document.write('<input type=hidden name=pi value='+navigator.plugins.length+'>');"
  . "}\n"
  . "//-->\n"
  . "</script>\n";

/*
 *
 * Get "submit" argument to determine if it's a login or registration request:
 * there cannot be other possibilities for $submit, it goes void otherwise.
 *
 */

$submit = fget ("submit", 8, "");
$submit = (($submit == "login") || ($submit == "register")) ? $submit : "";

/*
 *
 * retrieve fingerprint fields after submission (either login or registration requests)
 *
 * the only difference between login and registration is the field the IP is stored within:
 * registration IP becomes a "reference IP", saved as field "RI" in member's account record,
 * while the login IP gets updated at every new login of the same member (record field "LI").
 *
 */

if (!empty ($submit)) {

  $fingerprint_ri = $ip;

  $fingerprint_ua = substr (md5 ($_SERVER["HTTP_USER_AGENT"]), -5, 5);
  $fingerprint_al = substr (md5 ($_SERVER["HTTP_ACCEPT_LANGUAGE"]), -5, 5);

  /*
    fingerprint's textual fields must be kept limited in size (10 characters suffice)
    to avoid malicious use of javascripts to overload the server's disk space: it's
    also important to gather them via "fget", filtering out exploitable characters
    that could disrupt the database file's structure (they're three: "<", ">", "*")
  */

  $fingerprint_bw = fget ("bw", 10, "");
  $fingerprint_bh = fget ("bh", 10, "");
  $fingerprint_sw = fget ("sw", 10, "");
  $fingerprint_sh = fget ("sh", 10, "");
  $fingerprint_aw = fget ("aw", 10, "");
  $fingerprint_ah = fget ("ah", 10, "");
  $fingerprint_cd = fget ("cd", 10, "");
  $fingerprint_tz = fget ("tz", 10, "");
  $fingerprint_mt = fget ("mt", 10, "");
  $fingerprint_pi = fget ("pi", 10, "");

}

/*
 *
 * check for login requests (only if not already logged in)
 *
 * note: most of the values loaded in consequence of a login have no use in later code, but they
 * are loaded for completeness and possible future use in this script, and to provide a "howto"
 * piece of code which is supposed to be duplicated in the "sessions.php" script, which will
 * perform an automatic login whenever the member enters with a "remember me" cookie: there,
 * updating general data and flags ($auth, $is_admin, $ilist, etc...) is in fact important, since
 * all other scripts will rely on the values set by "suitcase.php".
 *
 * for instance, the $login flag is globally asserted by "settings.php" as false on entry to
 * Postline scripts, but it might have been set to true by "suitcase.php" if the above occured.
 *
 */

if (($login == false) && ($submit == "login")) {

  /*
    retrieve nickname and password from login form fields
  */

  $nick = ucfirst (strtolower (fget ("nick", 20, "")));
  $pass = strtolower (fget ("pass", 20, ""));

  if ((empty ($nick)) || (empty ($pass))) {

    /*
      set flag to witheld registration form (so the user concentrates on login),
      output error message if one of the fields is left void
    */

    $reg = true;
    $foc = "login.nick";
    $msg = "Login error!//Fill nick and password in login form, and try again.";

  }
  else {

    /*
      obtain exclusive write access:
      database manipulation ahead...
    */

    lockSession ();

    /*
      see if there's an existing account for the chosen nickname:
      deny login request if there isn't one ($id from query is empty or null)...
    */

    $id = get ("members/bynick", "nick>$nick", "id");

    if (empty ($id)) {

      /*
        set flag to witheld registration form (so the user concentrates on login),
        output error message
      */

      $reg = true;
      $foc = "login.nick";
      $msg = "Login error!//Could not find a member record matching: $nick.";

    }
    else {

      /*
        if the request is for logging in with the management account,
        allow uppercase letters (and distinguish them from lowercase) in the password
      */

      $pass = ($id == 1) ? fget ("pass", 20, "") : $pass;

      /*
        locate nickname's account record, read it
      */

      $db = "members" . intervalOf (intval ($id), $bs_members);
      $record = get ($db, "id>$id", "");

      if (empty ($record)) {

        /*
          a void record corresponding to a registered nickname is something weird:
          it might be an inconsistence in the database, so watch out for it...
        */

        $reg = true;
        $foc = "login.nick";
        $msg = "Login error!//$nick: nickname exists, but couldn't read its record.";

      }
      else {

        /*
          check banned nickname status:
          $bml is the Banned Members list as from the Community Defense panel ("cdp.php");
          $ncn is the all-lowercase version of the login nickname (matching lowercase of $bml);
          $aut is the authorization field (the "rank") of the account: can't be banned if admin.
        */

        $bml = strtolower (readFrom ("stats/bml"));
        $bml = wExplode ("\n", $bml);
        $ncn = strtolower ($nick);
        $aut = valueOf ($record, "auth");

        /*
          set some humanly-easy-to-read flags to make later check more comprehensible
        */

        $in_banlist = in_array ($ncn, $bml);
        $is_an_admin = in_array ($aut, $admin_ranks);

        /*
          login is granted if it's not in the banlist, or if the nickname is that of an admin
        */

        if (($in_banlist == false) || ($is_an_admin)) {

          /*
            one last check:
            retrieve "kick delay" ($kd), which refers to the Un*x epoch since when the member
            may be allowed to login again; if the current epoch is below $kd, deny the login...
          */

          $kd = intval (valueOf ($record, "kicked_on"))
              + intval (valueOf ($record, "kicked_for"));

          if ($kd < $epc) {

            /*
              encrypt password with RSA MD5:
              it's not that it protects users from the community manager (who could change the
              Postline scripts anyway, to introduce magic words), but may protect those users
              who may tend to use the same password for every forum they join... anyway, MD5 is
              just cheap and mostly uneffective obfuscation if you can access the database; yet,
              only staff members and host system administrators can acces the database; in short,
              MD5 makes it slightly more difficult to get passwords for those who read in the DB,
              and in my opinion it's still better than nothing.
            */

            $p_hash = MD5 ($pass);

            /*
              $md5_pass: check if MD5 hash of given password corresponds to known password's hash
              $man_pass: (manager) check if given password is the same as that of the SQL database
              $tmp_pass: (recovery) check if given password's hash corresponds to temporary pass.
            */

            $man_pass = (($id == 1) && ($pass == $dbPass)) ? true : false;
            $md5_pass = (($id != 1) && ($p_hash == valueOf ($record, "pass"))) ? true : false;

            $t_expire = intval (valueOf ($record, "last_sent")) + 3600;
            $tmp_pass = (($t_expire > $epc) && ($p_hash == valueOf ($record, "temp_pass"))) ? true : false;

            if (($man_pass) || ($md5_pass) || ($tmp_pass)) {

              /*
                password matches:
                login is granted, and general fields are loaded from the account's record
              */

              $login    = true;                                 // status: logged in
              $auth     = valueOf ($record, "auth");            // load member's literal rank
              $is_admin = in_array ($auth, $admin_ranks);       // admin-level access
              $is_mod   = in_array ($auth, $mod_ranks);         // mod-level access
              $jtime    = intval (valueOf ($record, "reg"));    // registration time (reuse check)
              $newpm    = valueOf ($record, "newpm");           // load new PM flag
              $ntint    = valueOf ($record, "ntint");           // load nickname color choice
              $bcol     = valueOf ($record, "bcol");            // load speech bubble color choice

              /*
                load and explode member's personal ignorelist
              */

              $ilist = wExplode (";", valueOf ($record, "ignorelist"));

              /*
               *
               * clamp message frame color index and nickname color
               *
               */

              $ntint = ($ntint === "") ? 0 : intval ($ntint);
              $ntint = ($ntint < 0) ? 0 : $ntint;
              $ntint = ($ntint < count ($ntints)) ? $ntint : (count ($ntints) - 1);

              $bcol = ($bcol === "") ? $default_bcol : intval ($bcol);
              $bcol = ($bcol < -1) ? -1 : $bcol;
              $bcol = ($bcol < count ($bcols) - 1) ? $bcol : (count ($bcols) - 2);

              /*
                check for major age promotion, unless it's already a major member
              */

              if (valueOf ($record, "major") == "yes") {

                $is_major = true;

              }
              else {

                $is_major = majoragepromotioncheck ($id);

                if ($is_major) {

                  /*
                    if member has been right now (with this login) promoted to major age,
                    remember that the ignore list is void and the former isn't valid anymore
                  */

                  $ilist = array ();

                }

              }

              /*
                load chat disable state
              */

              $is_muted = valueOf ($record, "muted");
              $is_muted = ($is_muted == "yes") ? true : false;

              /*
                if member is at least a moderator, load the intercom call flag
              */

              if (($is_admin) || ($is_mod)) {

                $intercom = intval (valueOf ($record, "intercom"));

              }

              /*
                update session data in sessions' database file (add a record, or change it)
              */

              set ("stats/sessions", "id>$id", "",
                   "<id>$id<ip>$ip<nick>$nick<auth>$auth<beg>$epc<ntint>$ntint<chatflag>$cflag");

              /*
                update profile data (number of logins, date of last login, login IP address)
              */

              $logins = intval (valueOf ($record, "logins")) + 1;
              $record = fieldSet ($record, "logins", strval ($logins));
              $record = fieldSet ($record, "lastlogin", strval ($epc));
              $record = fieldSet ($record, "logip", $ip);

              /*
                update fingerprint data:
                strict checks against void strings account for numerical values of 0 as non-empty
              */

              $record = fieldSet ($record, "li", $fingerprint_ri);

              if ($fingerprint_ua != "") $record = fieldSet ($record, "ua", $fingerprint_ua);
              if ($fingerprint_al != "") $record = fieldSet ($record, "al", $fingerprint_al);
              if ($fingerprint_bw != "") $record = fieldSet ($record, "bw", $fingerprint_bw);
              if ($fingerprint_bh != "") $record = fieldSet ($record, "bh", $fingerprint_bh);
              if ($fingerprint_sw != "") $record = fieldSet ($record, "sw", $fingerprint_sw);
              if ($fingerprint_sh != "") $record = fieldSet ($record, "sh", $fingerprint_sh);
              if ($fingerprint_aw != "") $record = fieldSet ($record, "aw", $fingerprint_aw);
              if ($fingerprint_ah != "") $record = fieldSet ($record, "ah", $fingerprint_ah);
              if ($fingerprint_cd != "") $record = fieldSet ($record, "cd", $fingerprint_cd);
              if ($fingerprint_tz != "") $record = fieldSet ($record, "tz", $fingerprint_tz);
              if ($fingerprint_mt != "") $record = fieldSet ($record, "mt", $fingerprint_mt);
              if ($fingerprint_pi != "") $record = fieldSet ($record, "pi", $fingerprint_pi);

              /*
                write account record back to database, to apply changes
              */

              set ($db, "id>$id", "", $record);

              /*
                encode autologin cookie data,
                set autologin cookie (an "eternal" cookie expiring at end of 32-bit epoch)
              */

              $login_cookie_hold

                        = $login_cookie_text
                        . base62Encode ($id . ">" . (($id == 1) ? $pass : $p_hash));

              setcookie ($login_cookie_name, $login_cookie_hold, 2147483647, "/");

              /*
                add a small javascript to reload the chat input frame so it won't show the warning
                message about not being logged in and therefore unable to chat... in this case,
                the login happened after input.php was loaded into the 'cli' frame, so that frame
                needs to be updated to make input.php realize the member is *now* logged in
              */

              if ($cflag == 'on') {

                $update_cli = "<script type=text/javascript>\n"
                            . "<!--\n"
                            .  "parent.parent.cli.document.location='input.php?disamb=$epc';\n"
                            . "//-->\n"
                            . "</script>\n";

              }

            }
            else {

              /*
                set flag to witheld registration form (so the user concentrates on login),
                output error message
              */

              $reg = true;
              $foc = "login.nick";
              $msg = "Password incorrect!//"
                   . "If you forgot your password, but you provided an email address for "
                   . "this system to contact you, you may request the system to mail the "
                   . "password by filling out a simple form in "
                   . "<a target=pan href=faq.php#q1>this page</a>.";

            }

          }
          else {

            /*
              set flag to witheld registration form (so the user concentrates on login),
              output error message
            */

            $reg = true;
            $foc = "login.nick";
            $msg = "Sorry: under kickout delay!//When a moderator or an administrator "
                 . "forces you to logout using the &quot;/kick&quot; command, there is a "
                 . "certain amount of time you'll have to wait before you can login again. "
                 . "This is a moderation feature, if you think you have been abused, "
                 . "you might report what happened to the community manager."
                 . "<hr>"
                 . "time to go:"
                 . chr (32)
                 . ((int)(($kd - $epc) / 60)) . " min,"
                 . chr (32)
                 . ((int)(($kd - $epc) % 60)) . " sec.";

          }

        }
        else {

          /*
            set flag to witheld registration and login forms,
            output error message
          */

          $reg = true;
          $wlf = true;
          $msg = "You have been banned!//"
               . "Now what? You may try to re-register with a new account, but beware: "
               . "especially if this banning has been decided by many members, who "
               . "evidently cannot be all wrong, you will likely have to change your "
               . "behavior, or this story will just keep repeating over and over. "
               . "Please don't take it personal, and don't take it as a matter of good "
               . "and evil, right and wrong principles, it's purely just the way it goes.";

        }

      }

    }

  }

}

/*
 *
 * check for registration requests:
 * only if not already logged in, either via cookie or because of a login request
 *
 */

if (($login == false) && ($submit == "register")) {

  /*
    check global community locker state:
    global community locker set by "defense.php", on request from an administrator,
    disables all registration requests, and in fact closes the board to any new members
  */

  if (get ("stats/counters", "counter>community_locker", "state") != "on") {

    /*
      in any cases, there's a timer that needs to go out before new accounts submission
      is allowed after last successful submission, calculated as...
    */

    $signup_clearance_time =
      intval (get ("stats/counters", "counter>lastsignup", "time")) + $antiuserflood;

    if ($signup_clearance_time < $epc) {

      /*
        retrieve nickname and password from registration form: the limits for both fields
        are 20 characters, and both fields are forced lowercase, except that the nickname
        field's first character is forced uppercase... objectionable - but must be so for
        retrocompatibility, although it's also comfortable for many other checks...
      */

      $nick = ucfirst (strtolower (fget ("rnick", 20, "")));
      $pass = strtolower (fget ("rpass", 20, ""));

      if ((empty ($nick)) || (empty ($pass))) {

        /*
          set autofocus to registration form and output error message,
          if one of the fields is left void
        */

        $foc = "signup.rnick";
        $msg = "Fill nick and password...//...in registration form. You can't say it's "
             . "too much I'm asking and that it violates your privacy in some way :)";

      }
      else {

        /*
          check password length (minimum 6 characters)
        */

        if (strlen ($pass) < 6) {

          $foc = "signup.rnick";
          $msg = "Invalid password!//"
               . "Password for registration must be between 6 and 20 characters long.";

        }
        else {

          /*
            check for disallowed nicknames
          */

          $allow = true;

          foreach ($disallowednicks as $n) {

            if ($nick == ucfirst (strtolower ($n))) {

              $allow = false;

              $foc = "signup.rnick";
              $msg = "Disallowed nickname!//"
                   . "Nickname \"$nick\" is disallowed, please pick another one and try again.";

              break;

            }

          }

          /*
            check for management account registration (guarded by database password)
          */

          $next_id = get ("stats/counters", "counter>signups", "count") + 1;

          if ($next_id == 1) {

            if ($pass != $dbPass) {

              $allow = false;

              $foc = "signup.rnick";
              $msg = "Wrong password!//"
                   . "The manager must sign up providing the SQL database access password. "
                   . "The given password will not be saved inside the SQL database itself, "
                   . "but it must match the string provided in file 'settings.php' on this "
                   . "board's server.";

            }

          }

          if ($allow) {

            /*
              check for ampersands and invalid characters in nick or password:
              they aren't allowed due to possible truncation of other kinds of characters,
              that get filtered out by "fget" from "suitcase.php"; when such characters,
              which aren't permitted to be saved in a database file in their regular ASCII
              form, get filtered, they become HTML entities and as such, they may be clipped
              off the resulting string when the length of the string is limited, giving
              weird behavior. Then there's the semicolon, which cannot be used in nicknames,
              because lists of nicknames (such as ignorelists and board DB file backup owners)
              are conventionally separated by semicolons. And finally the comma, since nicknames
              of voters for a poll's options are separated by commas because the semicolon was
              already used to separate options (after implementation of members' defragmentator).
            */

            $amp_1 = (strstr ($nick, "&") !== false) ? true : false;
            $amp_2 = (strstr ($pass, "&") !== false) ? true : false;
            $semic = (strstr ($nick, ";") !== false) ? true : false;
            $comma = (strstr ($nick, ",") !== false) ? true : false;

            if (($amp_1) || ($amp_2) || ($semic) || ($comma)) {

              $foc = "signup.rnick";
              $msg = "Invalid characters...//"
                   . "...in either nickname or password. Among such forbidden characters are "
                   . "the semicolon (;), the comma (,), lower and greater than signs (&lt; "
                   . "&gt;), ampersand (&amp;), and double quotes (&quot;). Please choose a "
                   . "nickname that doesn't contain any of those signs, and a password that "
                   . "doesn't contain ampersands, and try again.";

            }
            else {

              /*
                check if there's a record associated to this nickname:
                if there is, deny registration and ask for another nickname
              */

              $associated_record = get ("members/bynick", "nick>$nick", "");

              if (empty ($associated_record)) {

                /*
                  check list of banned subnets:
                  subnets of IP addresses are always banned from registration, never from login,
                  because if the member uses his/her existing account to login, we know who is.
                */

                $ban = false;

                $bsl = strtolower (readFrom ("stats/bsl"));
                $bsl = preg_replace ("/[^0123456789abcdef\(\n]/", "", $bsl);
                $bsl = wExplode ("\n", $bsl);

                foreach ($bsl as $bsn) {

                  /*
                    cut trailing comments out of each line of the subnets list:
                    ie. cut whatever follows an open parenthesis
                  */

                  $bsn = strFromTo ("/" . $bsn, "/", "(");
                  $bel = strlen ($bsn);

                  if ($bel > 0) {

                    /*
                      if subnet line isn't void, check user IP ($ip) for a match over as many
                      hexadecimal nybbles as the subnet code goes on, and ban on match...
                    */

                    if (substr ($ip, 0, $bel) == $bsn) {

                      $ban = true;
                      break;

                    }

                  }

                }

                if ($ban == false) {

                  /*
                    even though there may be no explicit ban on this subnet, check if IP appears in
                    a temporarily banned group of full IPv4 addresses, formed by merging all IPs of
                    recently kicked members and the IP that performed last successful registration:
                    people may attempt to circumvent kickouts or spam the board with registrations.
                  */

                  $kx = wExplode (";", get ("stats/counters", "counter>kicks", "list"));
                  $lr = get ("stats/counters", "counter>lastregip", "ip");

                  if (($ip == $lr) || (in_array ($ip, $kx))) {

                    $ban = true;

                  }

                }

                /*
                  banned subnets could be re-allowed if the user was given an override password:
                  it's an attempt to give a chance to register to proven innocent people who may
                  be unfortunately sharing the same subnet of a banned or recently kicked-out person.
                  This kind of override option affects (and circumvents) all of the above checks.
                */

                $bop_1 = get ("stats/counters", "counter>bop", "value");
                $bop_2 = fget ("bop", 20, "");

                if ((!empty ($bop_1)) && (!empty ($bop_2))) {

                  if ($bop_1 == $bop_2) {

                    /*
                      password correct: remove ban for this user
                    */

                    $ban = false;

                  }
                  else {

                    /*
                      password incorrect: deny registration
                    */

                    $nol = true;

                  }

                }

                if ($ban == false) {

                  /*
                    obtain exclusive write access:
                    database manipulation ahead...
                  */

                  lockSession ();

                  /*
                    generate new account's logical ID (a progressive numerical identificator,
                    used mostly to select the file where the account's record will be saved):
                    there might be no record associated with the newly generated ID, otherwise
                    it's an internal problem due to a counter that failed to update last time
                    someone signed up.
                  */

                  $id = get ("stats/counters", "counter>signups", "count") + 1;
                  $db = "members" . intervalOf ($id, $bs_members);

                  $associated_record = get ($db, "id>$id", "");

                  if (empty ($associated_record)) {

                    /*
                      every new registration is subject to validation, via a numeric code
                      that is presented on screen masked by some "noise": spam bots might
                      not be able to acknowledge this number, while humans might.
                      the "conf" field of the registration form is initially set to "must",
                      to signal that registration must be validated and it's time to make
                      out a new validation code, extracted randomly by the Mersenne Twister.
                    */

                    $cnf = fget ("conf", 10, "");

                    if ($cnf == "must") {

                      /*
                        generate validation code, and associate it with current user's IP
                        address: the code will be saved to "stats/codes" for later check
                      */

                      $cnf_code = mt_rand (1000000000, 2000000000);
                      set ("stats/codes", "ip>$ip", "", "<ip>$ip<code>$cnf_code");

                    }
                    else {

                      /*
                        check validation code ("conf" is no longer set to "must" because the
                        user filled the validation form after reading the code): of course both
                        the code submitted by the user and the one recorded and associated to
                        this IP address must not be empty, and must be the same exact number.
                      */

                      $check = get ("stats/codes", "ip>$ip", "code");

                      if ((!empty ($cnf)) && (!empty ($check)) && ($cnf == $check)) {

                        /*
                          encrypt password with RSA MD5:
                          it's not that it protects users from the community manager (who could
                          change the Postline scripts anyway, to introduce magic words), but
                          protects those users who may tend to use the same password for every
                          forum they join.
                        */

                        $pass = MD5 ($pass);

                        /*
                          management account registration:
                          default authorization level is set to "member" for all registrations
                          following the VERY FIRST ONE. The first registration is that of the
                          board's manager, and can be done only when the members' database is
                          completely empty. That's why the manager MUST register an account,
                          before making the board publicly accessible. This is very important:
                          account with logical ID 1 is also granted numerous other privileges,
                          even higher than those of administrators' accounts. Especially, account
                          number 1 is protected against deletion and its rank cannot be changed.
                        */

                        if ($id != 1) {

                          $end_auth = "member";         // regular account registration

                        }
                        else {

                          $end_auth = "manager";        // management account registration
                          $pass = "N/A";                // this has special password management

                        }

                        /*
                          add account record to proper database file:
                          database file name ($db) derives from the account's logical ID;
                          the two empty ("") string arguments passed to "set" cause that
                          function to add a new record on top of the specified file.
                        */

                        set ($db, "", "",

                           // initial profile data

                             "<id>"         . $id               // logical ID of account
                           . "<nick>"       . $nick             // nickname
                           . "<pass>"       . $pass             // password hash
                           . "<reg>"        . $epc              // registration time
                           . "<auth>"       . $end_auth         // authorization level (rank)

                           // fingerprint data

                           . "<ri>"        . $fingerprint_ri    // registration IP
                           . "<ua>"        . $fingerprint_ua    // user agent
                           . "<al>"        . $fingerprint_al    // accept language
                           . "<bw>"        . $fingerprint_bw    // browser window width
                           . "<bh>"        . $fingerprint_bh    // browser window height
                           . "<sw>"        . $fingerprint_sw    // screen width
                           . "<sh>"        . $fingerprint_sh    // screen height
                           . "<aw>"        . $fingerprint_aw    // desktop width
                           . "<ah>"        . $fingerprint_ah    // desktop height
                           . "<cd>"        . $fingerprint_cd    // screen color depth
                           . "<tz>"        . $fingerprint_tz    // client time zone
                           . "<mt>"        . $fingerprint_mt    // browser mime types
                           . "<pi>"        . $fingerprint_pi    // browser plug-ins

                           );

                        /*
                          add nickname record to global nicknames' list:
                          this list is kept to locate the logical ID of an account
                          starting from the nickname (it's a reverse lookup table)
                        */

                        set ("members/bynick", "", "", "<nick>$nick<id>$id");

                        /*
                          update:
                          signups counter (will determine next account's logical ID)
                          last registration IP (will block further submissions from the same IP)
                          last signup time (will temporarily block any submissions to reduce spam)
                        */

                        set ("stats/counters", "counter>signups", "", "<counter>signups<count>$id");
                        set ("stats/counters", "counter>lastregip", "", "<counter>lastregip<ip>$ip");
                        set ("stats/counters", "counter>lastsignup", "", "<counter>lastsignup<time>$epc");

                        /*
                          delete used validation code (to keep "stats/codes" from growing big)
                        */

                        set ("stats/codes", "ip>$ip", "", "");

                        /*
                          building array of message IDs representing all recent posts:
                          - load recent discussions archive building an array ($rd_a) out of
                            its newline-delimited records, initialize filtered array ($rd_f)
                            to hold only the message ID fields ($_m) of each record in $rd_a.
                        */

                        $rd_a = all ("forums/recdisc", asIs);
                        $rd_f = array ();

                        foreach ($rd_a as $r) {

                          list ($_F, $_T, $_m, $_n, $_e, $_h, $_f, $_t) = explode (">", $r);
                          $rd_f[] = $_m;

                        }

                        /*
                          set VRD table entry for this member ID,
                          to hold all recent discussions
                        */

                        setvrd ($id, $rd_f);

                        /*
                          output successful registration message,
                          set flag to witheld registration form (so the user concentrates on login)
                        */

                        $msg = "Welcome $nick!//Now use the form below to login. You will not need "
                             . "to register anymore; next time you come here, you might be acknowledged "
                             . "automatically, due to a cookie. Should you want to invalidate that cookie, "
                             . "such as before leaving a public machine, use the red \"log out\" link. "
                             . "To login after doing so, use this nickname ($nick) and the given password.";

                        $reg = true;

                        /*
                          log event to public chat, store in chat logs
                        */

                        logwr ("Welcoming a new member: &quot;$nick&quot;.", lw_persistent);

                      }
                      else {

                        $foc = "signup.rnick";
                        $msg = "Wrong validation number!//You might have mistyped the code: "
                             . "don't worry, the noise above it was intentional, and could give "
                             . "troubles not only to machines. Please repeat the registration "
                             . "by filling again your desired nickname and a password of your "
                             . "choice and again click 'register'. You will be given a new number "
                             . "to type in.";

                      }

                    }

                  }
                  else {

                    $foc = "signup.rnick";
                    $msg = "Registration error!//Logical ID conflict, possible problem with "
                         . "database counters: apologies, and please report this problem via "
                         . "a feedback form, thank you.";

                  }

                }
                else {

                  $foc = "signup.rnick";

                  if ($nol) {

                    $msg = "Wrong override password!//"
                         . "You either guessed the wrong password trying to wildly guess it, or you've "
                         . "mispelled the password: remember that lowercase and uppercase letters are "
                         . "considered different (\"E\" is not the same as \"e\"). Also note that since "
                         . "you receive the password you're encouraged to sign up as soon as possible: "
                         . "this kind of password might be often changed.";

                  }
                  else {

                    $nol = true;

                    $msg = "Subnet rejected!//"
                         . "Your IP subnet is banned. Possibly, not because of you, but because of "
                         . "someone else who uses an internet connection that shares your same subnet. "
                         . "In the hope that the future will bring less ambiguous methods for identifying "
                         . "visitors, please accept our most sincere apologies. You may submit a feedback "
                         . "form to signal this situation."
                         . "<hr>"
                         . "Note: this kind of protection against signup of new accounts engages "
                         . "automatically if you have been recently \"kicked out\" by a moderator, "
                         . "or if you have been subject to repeated bans, or if you already "
                         . "registered an account (you might not register two or more accounts)."
                         . "<hr>"
                         . "Should you have received a special &#171;ban override password&#187; from "
                         . "this site's administration, you could still register your account by "
                         . "filling your registration data, and this time, including that password "
                         . "in the field below.";

                  }

                }

              }
              else {

                $foc = "signup.rnick";
                $msg = "Registration error!//Nick \"$nick\" is already assigned to another "
                     . "member. Please choose another nickname.";

              }

            }

          }

        }

      }

    }
    else {

      $msg = "WARNING: registration delay!//"
           . "A member has recently signed up before you. "
           . "Please wait for the timer you see below to expire.";

    }

  }
  else {

    $msg = "Community is actually closed!//...to new members' registration. "
         . "This is a setting that may be used at times to prevent registrations "
         . "while maintainance is going on, or as a defense for existing members. "
         . "Only an administrator can change this setting, so if you know how to "
         . "contact one of this community's administrators, that's who you "
         . "have to contact for discussing about this matter.";

  }

}

/*
 *
 * create general warnings ("you might log out on public computers", etc):
 * this paragraph must not be added for special pages (validation code request,
 * subnet ban and ban override password request) defined by following conditions...
 *
 */

$no_validation_page = ($cnf != "must") ? true : false;
$no_reg_denial = ($nol) ? false : true;

if (($no_validation_page) && ($no_reg_denial)) {

  if ($login) {

    if ($all_off) {

      /*
        the $all_off flag signals the board is globally disabled for access by anyone
        except the manager (account ID #1): if the user gets the server to execute this
        piece of code, it means we're dealing with the manager, so dropping a note about
        not leaving the board disabled, and don't present any links to logout...
      */

      $genw = $opcart
            .  "<tr>"
            .   "<td class=ntop>"
            .    "--- note to the manager ---"
            .   "</td>"
            .  "</tr>"
            .  "<tr>"
            .   "<td class=nmid>"
            .    "never log out while the system is locked for maintenance: nobody except you, "
            .    "and from your exact computer and your exact browser, can access this system, "
            .    "while the maintainance lock is engaged! you may close the browser and stay "
            .    "away for as much as you need, but you must not, absolutely, delete or damage "
            .    "your login cookie! the only way to unlock the community if you lost that "
            .    "cookie, would be manually deleting file &quot;widgets/sync/system_locker&quot; "
            .    "from this server!"
            .   "</td>"
            .  "</tr>"
            . $clcart
            . $inset_bridge
            . "<table width=$pw>"
            .  "<form action=defense.php>"
            .  "<td>"
            .   "<span title=\"access the Community Defense Panel to switch off the system maintenance lock\">"
            .    "<input type=submit class=ky value=\"access defense panel\" style=width:{$pw}px>"
            .   "</span>"
            .  "</td>"
            .  "</form>"
            . "</table>"
            . $inset_shadow;

    }
    else {

      /*
        logged-in member is told to logout before leaving, if using public computers,
        not to leave the login cookie (and the account) available to unknown persons...
      */

      $genw = "<table width=$pw>"
            .  "<form action=mstats.php>"
            .  "<input type=hidden name=solong value=y>"
            .  "<td>"
            .   "<span title=\"destroys your actual session and WILL NOT automatically login next time\">"
            .    "<input type=submit class=su value=\"log out / destroy cookie\" style=width:{$pw}px>"
            .   "</span>"
            .  "</td>"
            .  "</form>"
            . "</table>"
            . $inset_shadow;

    }

  }

}

/*
 *
 * generate member statistics panel (uses sidebar template)
 *
 */

$fw = $pw - 96; // computes width of fields considering 96 pixels for field labels

if ($login == false) {

  /*
    in case member isn't logged in (guest panel)...
  */

  if ($cnf == "must") {

    /*
      when $cnf, which derives from the "conf" field of registration form, is set to "conf",
      Postline is supposed to visualize the account registration's validation form, and the
      validation code. This code will be shown as a special HTML "table" made of cells that
      constitute pixels of every digit's "bitmap". The bitmaps for every digit are declared
      by the following array (in which, 1 means pixel, 0 means no pixel)...
    */

    $digits = array (

      0,1,1,1,0,        // digit "0"
      1,0,0,0,1,
      1,0,0,0,1,
      1,0,0,0,1,
      1,0,0,0,1,
      1,0,0,0,1,
      0,1,1,1,0,

      0,0,1,0,0,        // digit "1"
      0,1,1,0,0,
      0,0,1,0,0,
      0,0,1,0,0,
      0,0,1,0,0,
      0,0,1,0,0,
      0,1,1,1,0,

      0,1,1,1,0,        // digit "2"
      1,0,0,0,1,
      0,0,0,0,1,
      0,0,0,1,0,
      0,0,1,0,0,
      0,1,0,0,0,
      1,1,1,1,1,

      0,1,1,1,0,        // digit "3"
      1,0,0,0,1,
      0,0,0,0,1,
      0,0,1,1,0,
      0,0,0,0,1,
      1,0,0,0,1,
      0,1,1,1,0,

      0,0,0,1,0,        // digit "4"
      0,0,1,1,0,
      0,1,0,1,0,
      1,0,0,1,0,
      1,1,1,1,1,
      0,0,0,1,0,
      0,0,0,1,0,

      1,1,1,1,1,        // digit "5"
      1,0,0,0,0,
      1,0,0,0,0,
      1,1,1,1,0,
      0,0,0,0,1,
      1,0,0,0,1,
      0,1,1,1,0,

      0,1,1,1,0,        // digit "6"
      1,0,0,0,0,
      1,0,0,0,0,
      1,1,1,1,0,
      1,0,0,0,1,
      1,0,0,0,1,
      0,1,1,1,0,

      1,1,1,1,1,        // digit "7"
      0,0,0,0,1,
      0,0,0,0,1,
      0,0,0,1,0,
      0,0,0,1,0,
      0,0,1,0,0,
      0,0,1,0,0,

      0,1,1,1,0,        // digit "8"
      1,0,0,0,1,
      1,0,0,0,1,
      0,1,1,1,0,
      1,0,0,0,1,
      1,0,0,0,1,
      0,1,1,1,0,

      0,1,1,1,0,        // digit "9"
      1,0,0,0,1,
      1,0,0,0,1,
      0,1,1,1,1,
      0,0,0,0,1,
      1,0,0,0,1,
      0,1,1,1,0

    );

    /*
      open validation form
    */

    $form =  $fingerprint_fields        // fingerprint detection script

          .  maketopper ("Validate sign-up!")

          .  "<table width=$pw>"
          .   "<form name=signup action=mstats.php enctype=multipart/form-data method=post>"
          .   "\n"
          .   "<script type=text/javascript>\n"
          .   "<!--\n"
          .     "fp();\n"               // tell fp() in javascript to write fingerprint fields
          .   "//-->\n"
          .   "</script>\n"
          .   "<input type=hidden name=bop value=\"$bop_1\">"
          .   "<input type=hidden name=rnick value=\"$nick\">"
          .   "<input type=hidden name=rpass value=\"$pass\">"
          .   "<td height=21 class=inv align=center>"
          .    "validation number"
          .   "</td>"
          .  "</table>"

          .  $inset_bridge

          .  $opcart
          .   "<td class=ls align=center>"
          .    "<table align=center>";

    /*
      build HTML to show "blurry" validation code in a table:
      it is important that this code NEVER appears as plain text in the HTML source
    */

    $cnf_code = strval ($cnf_code);

    for ($r = 0; $r < 7; $r ++) {

      $form .= "<tr>";

      for ($n = 0; $n < 10; $n ++) {

        $p = (5 * 7 * intval ($cnf_code[$n])) + (5 * $r);

        for ($c = 0; $c < 5; $c ++, $p ++) {

          for ($x = 0; $x < 3; $x ++) {

            $form .= ($r) ? "<td" : "<td width=1";
            $form .= ($n + $c) ? "" : " height=3";
            $form .= ($digits[$p] && mt_rand (1, 15) != 7) ? " bgcolor=black></td>" : "></td>";

          }

        }

        $form .= ($r) ? "<td></td>" : "<td width=2></td>";

      }

      $form .= "</tr>";

    }

    /*
      finish and close validation form
    */

    $form .=   "</table>"
          .   "</td>"
          .  $clcart

          .  $inset_bridge

          .  "<table width=$pw>"
          .   "<td height=21 class=inv align=center>"
          .    "type the above number"
          .   "</td>"
          .  "</table>"

          .  $inset_bridge

          .  $opcart
          .   $fspace
          .   "<tr>"
          .    "<td width=96 class=in align=center>"
          .     "Number is:"
          .    "</td>"
          .    "<td class=in>"
          .     "<input class=mf type=text name=conf value=\"\" style=width:{$fw}px>"
          .    "</td>"
          .   "</tr>"
          .   $fspace
          .  $clcart

          .  $inset_bridge

          .  "<table width=$pw>"
          .   "<tr>"
          .    "<td height=21 class=inv align=center>"
          .     "validate your sign-up"
          .    "</td>"
          .   "</tr>"
          .   $bridge
          .   "<tr>"
          .    "<td>"
          .     "<input class=ky type=submit name=submit value=register style=width:{$pw}px>"
          .    "</td>"
          .   "</tr>"
          .   $shadow
          .   "</form>"
          .  "</table>"

          .  makeframe (

             "but why...?",

             "Because it's possible to fill out such forms repeately by writing a program, "
          .  "called a spambot, that could fill several forms per day, in fact spamming "
          .  "the boards with a lot of fake new accounts. But a program, unless it was quite "
          .  "sophisticated, shouldn't be able to understand the above code. In short, "
          .  "we're just trying to check that you're a genuine sentient lifeform, that's it. "
          .  "Oh, and don't worry if you can't read it clearly enough: it's intentional, to "
          .  "mislead programs, and (usually) not real brains. Even if this time you don't "
          .  "succeed, you can keep trying, being given a new number at every attempt. Before "
          .  "or later, and averagely within two or three tries, you might succeed...", true

          )

          .  "\n"
          .  "<script type=text/javascript>\n"
          .  "<!--\n"
          .    "document.signup.conf.focus();\n"
          .  "//-->\n"
          .  "</script>\n";

  }
  else {

    /*
      if no form for confirmation code on sign-up has to be presented to the user,
      present the login and registration forms: start by making a header holding error,
      confirmation, or salutation messages (from $msg)
    */

    list ($frame_title, $frame_contents) = explode ("//", $msg);

    $form =

          makeframe (

            $frame_title, false, false

          )

          .

        (($frame_contents)

          ?

          makeframe (

            "information",
            $frame_contents,

            true

          )

          : "");

    if ($nol == true) {

      /*
        when $nol is set, it's in consequence of a denied registration request:
        it can be because of a rejected subnet, or because the override password
        provided by the user to circumvent a ban did not match.
        in both cases, a form that requests the ban override password must be shown,
        along with a copy of the registration form to confirm nickname and password.
        ---
        note: the "bop" input field is clearly visualized to help users insert the
        password and spell-check it; after all, this kind of password isn't "sensible"
        for the user. It doesn't apply to the user account, and it's often a one-shot
        password. After a troubled visitor registered successfully behind a banned IP
        subnet, the ban override password might be promptly changed by administrators.
        ---
        note II: fingerprint fields aren't gathered in this case, and the script isn't
        needed, because there will be the confirmation code request to get the fields.
      */

      $form .= "<table width=$pw>"
            .   "<form name=override action=mstats.php enctype=multipart/form-data method=post>"
            .   "<input type=hidden name=conf value=must>"
            .   "<td height=21 class=inv align=center>"
            .    "Ban override password?"
            .   "</td>"
            .  "</table>"

            .  $inset_bridge

            .  $opcart
            .   "<td class=in>"
            .    "<input class=sf type=text name=bop value=\"\" style=width:{$iw}px>"
            .   "</td>"
            .  $clcart

            .  $inset_bridge

            .  "<table width=$pw>"
            .   "<td height=21 class=inv align=center>"
            .    "Sign-up informations:"
            .   "</td>"
            .  "</table>"

            .  $inset_bridge

            .  $opcart
            .   $fspace
            .   "<tr>"
            .    "<td width=96 class=in align=center>"
            .     "Nickname:"
            .    "</td>"
            .    "<td class=in>"
            .     "<input class=mf type=text name=rnick value=\""
            .     $nick // note this input is re-loaded with nickname from last attempt
            .     "\" style=width:{$fw}px>"
            .    "</td>"
            .   "</tr>"
            .   "<tr>"
            .    "<td width=96 class=in align=center>"
            .     "Password:"
            .    "</td>"
            .    "<td class=in>"
            .     "<input class=mf type=password name=rpass value=\""
            .     $pass // note this input is re-loaded with password from last attempt
            .     "\" style=width:{$fw}px>"
            .    "</td>"
            .   "</tr>"
            .   $fspace
            .  $clcart

            .  $inset_bridge

            .  "<table width=$pw>"
            .   "<td>"
            .    "<input class=ky type=submit name=submit value=register style=width:{$pw}px>"
            .   "</td>"
            .   "</form>"
            .  "</table>"

            .  $inset_shadow

            .  "\n"
            .  "<script type=text/javascript>\n"
            .  "<!--\n"
            .    "document.override.bop.focus();\n"
            .  "//-->\n"
            .  "</script>\n";

    }
    else {

      /*
        top of page (guest case)
      */

      if (($wlf == false) && ($foc == "login.nick")) {

        /*
          show login form:
          only if not witheld, and when $foc is "login.nick", you might show the login form:
          $foc changes to "login.rnick" after an attempt to register an account; if there's
          been a problem with that (banned subnets, illegal nicknames, etc) you will only show
          the registration form so the user can concentrate on that.
        */

        $form .= $fingerprint_fields    // fingerprint detection script
              .  "<table width=$pw>"
              .   "<form name=login action=mstats.php enctype=multipart/form-data method=post>"
              .   "\n"
              .   "<script type=text/javascript>\n"
              .   "<!--\n"
              .     "fp();\n"           // tell fp() in javascript to write fingerprint fields
              .   "//-->\n"
              .   "</script>\n"
              .   "<td height=44 class=inv align=center>"
              .    "Are you a member?<br>"
              .    "Sign in to your account..."
              .   "</td>"
              .  "</table>"

              .  $inset_bridge

              .  $opcart
              .   $fspace
              .   "<tr>"
              .    "<td width=96 class=in align=center>"
              .     "Nickname:"
              .    "</td>"
              .    "<td class=in>"
              .     "<input class=mf type=text name=nick value=\"\" style=width:{$fw}px>"
              .    "</td>"
              .   "</tr>"
              .   "<tr>"
              .    "<td width=96 class=in align=center>"
              .     "Password:"
              .    "</td>"
              .    "<td class=in>"
              .     "<input class=mf type=password name=pass value=\"\" style=width:{$fw}px>"
              .    "</td>"
              .   "</tr>"
              .   $fspace
              .  $clcart

              .  $inset_bridge

              .  "<table width=$pw>"
              .   "<td>"
              .    "<input class=ky type=submit name=submit value=login style=width:{$pw}px>"
              .   "</td>"
              .   "</form>"
              .  "</table>"

              .  $inset_shadow;

      }

      if ($reg == false) {

        /*
          when flag $reg goes true, it means the user has just registered an account, or that
          he/she tried to login but failed due to some error: in both cases, the intention
          should be that of logging in, so show the registration form only if $reg is false
        */

        if (get ("stats/counters", "counter>community_locker", "state") != "on") {

          /*
            if community isn't locked from new members registration, check delay timer
          */

          $signup_clearance_time =
            intval (get ("stats/counters", "counter>lastsignup", "time")) + $antiuserflood;

          if ($signup_clearance_time < $epc) {

            /*
              show registration form:
              if delay timer has expired (or was never set, holding a value of zero),
              finally present the registration form: of course the presence of the form
              is a mere question concerning the HTML output of this page - further checks
              are made to ensure that the registration form, wether present or not, cannot
              trigger registrations when it's not possible to.
              ---
              note: fingerprint fields aren't gathered in this case, and the script isn't
              needed, because there will be the confirmation code request to get the fields.
            */

            $form .= "<table width=$pw>"
                  .   "<form name=signup action=mstats.php enctype=multipart/form-data method=post>"
                  .   "<input type=hidden name=conf value=must>"
                  .   "<td height=44 class=inv align=center>"
                  .    "Never been here?<br>"
                  .    "Register below, it's free!"
                  .   "</td>"
                  .  "</table>"

                  .  $inset_bridge

                  .  $opcart
                  .   $fspace
                  .   "<tr>"
                  .    "<td width=96 class=in align=center>"
                  .     "Nickname:"
                  .    "</td>"
                  .    "<td class=in>"
                  .     "<input class=mf type=text name=rnick value=\""
                  .     $nick   // note this input is re-loaded with nickname from last attempt
                  .     "\" style=width:{$fw}px>"
                  .    "</td>"
                  .   "</tr>"
                  .   "<tr>"
                  .    "<td width=96 class=in align=center>"
                  .     "Password:"
                  .    "</td>"
                  .    "<td class=in>"
                  .     "<input class=mf type=password name=rpass value=\""
                  .     $pass   // note this input is re-loaded with password from last attempt
                  .     "\" style=width:{$fw}px>"
                  .    "</td>"
                  .   "</tr>"
                  .   $fspace
                  .  $clcart

                  .  $inset_bridge

                  .  "<table width=$pw>"
                  .   "<td>"
                  .    "<input class=ky type=submit name=submit value=register style=width:{$pw}px>"
                  .   "</td>"
                  .   "</form>"
                  .  "</table>"

                  .  $inset_shadow;

          }
          else {

            /*
              show registration delay timer, while it's active
              (mutually excludes the above code for the registration form)
            */

            $form .= "<table width=$pw>"
                  .   "<td height=20 class=inv align=center>"
                  .    "WARNING: registration delay"
                  .   "</td>"
                  .  "</table>"

                  .  $inset_bridge

                  .  $opcart
                  .   "<td class=ls colspan=2>"
                  .    "<p align=justify>"
                  .     "There is a " . ((int) ($antiuserflood / 60)) . "-minutes "
                  .     "delay since last member's signup. Please wait for this timer to "
                  .     "expire before attempting to create your account."
                  .     "<hr>"
                  .     "time to go:"
                  .     chr (32)
                  .     ((int) (($signup_clearance_time - $epc) / 60)) . " min,"
                  .     chr (32)
                  .     ((int) (($signup_clearance_time - $epc) % 60)) . " sec."
                  .     chr (32)
                  .     "[<a class=ll href=mstats.php>reload</a>]"
                  .    "</p>"
                  .   "</td>"
                  .  $clcart

                  .  $inset_shadow;

          }

        }
        else {

          /*
            claim community is closed to registrations, if it's the case
            (this hides any further processing for the registration form)
          */

          $form .= $opcart
                .   "<td class=ls colspan=2>"
                .     "Sorry, " . ucfirst ($sitename) . " does currently "
                .     "not allow registering accounts."
                .   "</td>"
                .  $clcart

                .  $inset_shadow;

        }

      }

      /*
        this script, appended to HTML code holding login and/or registration forms,
        causes the browser to focus the input control specified by $foc
      */

      $form .= "\n"
            .  "<script type=text/javascript>\n"
            .  "<!--\n"
            .    "document.$foc.focus();\n"
            .  "//-->\n"
            .  "</script>\n";

    }

  }

}
else { // else, member is logged in, so it's eventually performing login-time utilities

  /*
    marks/unmarks session as "away from keyboard", if requested
  */

  if (fget ("afk", 1, "") == "y") {

    $afk_flag = voidString;
    $afk_link = "remove &#171;away&#187; sign";
    $afk_desc = "removes the AFK indication - alternatively, just do something and it'll disappear";

    define ("turn_on_afk_sign", true);

  }
  else {

    $afk_flag = "y";
    $afk_link = "place &#171;away&#187; sign";
    $afk_desc = "tails an 'away' indication to your nickname that will show in the status line";

  }

  /*
    logs out, if requested; in turn, it does the following:
    - obtains exclusive database access to safely erase session record from "stats/sessions"
    - writes the word "out" over the contents of the login cookie, to invalidate it
    - sets $hint to be added to page header (informative)
    - clears $login flag to let pquit's session updater know that the session no longer exists
    - outputs notice to public chat frame, for others to know
  */

  if (fget ("solong", 1, "") == "y") {

    lockSession ();
    set ("stats/sessions", "id>$id", wholeRecord, deleteRecord);

    $login_cookie_name = "{$sitename}_postline_login";
    setcookie ($login_cookie_name, "out", 2147483647, "/");

    $hint = "Successfully logged out.";
    $salutation = "So long,";

    $login = false;

    /*
      add a small javascript to reload the chat input frame so it will resume showing the warning
      message about not being logged in and therefore unable to chat... in this case, the logout
      happened after input.php was loaded into the 'cli' frame, so that frame needs to be updated
      to make input.php realize the member is *no longer* logged in
    */

    if ($cflag == 'on') {

      $update_cli = "<script type=text/javascript>\n"
                  . "<!--\n"
                  .  "parent.parent.cli.document.location='input.php?disamb=$epc';\n"
                  . "//-->\n"
                  . "</script>\n";

    }

  }
  else {

    $hint = "";
    $salutation = "Hello,";

  }

  /*
    outputs the stats panel
  */

  $form =

        makeframe (

          $salutation . chr (32) . $nick,
          $hint,

          true

        )

        . $update_cli;

  /*
    if member has just logged out, this page ends here, else...
  */

  if ($login) {

    /*
      top of page (member case)
    */

    /*
      determine access rights for visualization,
      and to eventually show the link to the CDP (Community Defense Panel)
    */

    $accr = "Regular";
    $stff = false;

    if ($is_admin) {

      $accr = "Admininstration";
      $stff = true;

    }

    if ($is_mod) {

      $accr = "Moderation";
      $stff = true;

    }

    /*
      if member is a moderator or an administrator, he/she's part of the board staff,
      and as such invited to provide an email address where to drop feedback forms:
      if not, email is asked anyway because it's involved in lost passwords' recovery.
      in any cases, email address is not mandatory, and can be witheld by setting its
      field to "NONE"; otherwise, Postline will keep requesting for a valid email address...
    */

    $db = "members" . intervalOf (intval ($id), $bs_members);
    $email = get ($db, "id>$id", "email");

    $no_privacy = (strtolower ($email) != "none") ? true : false;
    $request_email = (strpos ($email, "@") === false) ? true : false;

    if (($no_privacy) && ($request_email)) {

      $form .= $opcart
            .   "<tr>"
            .    "<td class=ntop>"
            .     "warning: no contact email"
            .    "</td>"
            .   "</tr>"
            .   "<tr>"
            .    "<td class=nmid>"
            .

          (($stff)

            ?     "as part of the staff, being either an admin or a moderator, you might supply "
            .     "your email address to receive feedback forms, when visitors and members will "
            .     "compile one, in <a class=ll target=pan href=tellme.php>this page</a>, so please check "
            .     "your profile and type a valid email address in the corresponding field. That "
            .     "address will be kept private: that field only shows to the profile's owner. "
            .     "If you really don't want to be mailed about feedbacks (but as a staff member "
            .     "you're pleased to) you could type \"none\" in your email's field.<br>Thank you."

            :     "should you forget your password in the future, you might supply a valid email "
            .     "address so that you could receive temporary passwords, which may be sent on "
            .     "request throught <a class=ll target=pan href=faq.php#q1>this page</a>, and "
            .     "which purpose is to provide a way to login and give you some time to change "
            .     "the real password to something else (that you'll then, hopefully, remember). "
            .     "The email address will be kept private: it only shows to the owner of the "
            .     "corresponding profile form. If you're sure that you will never forget your "
            .     "password, you may type &quot;none&quot; in your email's field to shut up this "
            .     "warning box.<br>Thank you."

            )

            .    "</td>"
            .   "</tr>"
            .  $clcart

            .  $inset_bridge

            .  "<table width=$pw>"
            .   "<form action=profile.php>"
            .   "<input type=hidden name=nick value=" . base62Encode ($nick) . ">"
            .   "<td>"
            .    "<span title=\"here you can tell us something about you, but only if you want\">"
            .     "<input type=submit class=ky value=\"access your profile here\" style=width:{$pw}px>"
            .    "</span>"
            .   "</td>"
            .   "</form>"
            .  "</table>"

            .  $inset_shadow;

    }

    /*
      output session control panel for logged-in member:
      it's a set of links to profile, preferences, latest posts, etc...
    */

    $form .= "<table width=$pw>"
          .   "<td height=40 class=inv align=center>"
          .    "LOCATE YOUR STUFF HERE<br>"
          .    "<small>quick links to what you did</small>"
          .   "</td>"
          .  "</table>"

          .  $inset_bridge

          .  "<table width=$pw>"
          .   "<form action=profile.php>"
          .   "<input type=hidden name=nick value=" . base62Encode ($nick) . ">"
          .   "<td>"
          .    "<span title=\"here you can tell us something about you, but only if you want\">"
          .     "<input type=submit class=ky value=\"access your profile\" style=width:{$pw}px>"
          .    "</span>"
          .   "</td>"
          .   "</form>"
          .  "</table>"

          .  $inset_bridge

          .  "<table width=$pw>"
          .   "<form action=cdisk.php>"
          .   "<input type=hidden name=sub value=" . base62Encode ($nick) . ">"
          .   "<td>"
          .    "<input type=submit class=ky value=\"find your c-disk files\" style=width:{$pw}px>"
          .   "</td>"
          .   "</form>"
          .  "</table>"

          .  $inset_bridge

          .  "<table width=$pw>"
          .   "<form action=recdisc.php>"
          .   "<input type=hidden name=n value=" . base62Encode ($nick) . ">"
          .   "<td>"
          .    "<input type=submit class=ky value=\"find your latest posts\" style=width:{$pw}px>"
          .   "</td>"
          .   "</form>"
          .  "</table>"

          .  $inset_shadow

          .  "<table width=$pw>"
          .   "<td height=40 class=inv align=center>"
          .    "ABOUT YOUR SESSION<br>"
          .    "<small>info &amp; utilities</small>"
          .   "</td>"
          .  "</table>"

          .  $inset_bridge

          .  $opcart
          .   "<td class=ls>"
          .    "Literal rank: " . ucfirst ($auth) . "<br>"
          .    "Access rights: " . $accr
          .   "</td>"
          .  $clcart

          .  $inset_bridge

          .  "<table width=$pw>"
          .   "<form action=mstats.php>"
          .   "<input type=hidden name=afk value=\"" . $afk_flag . "\">"
          .   "<td>"
          .    "<span title=\"$afk_desc\">"
          .     "<input type=submit class=ky value=\"$afk_link\" style=width:{$pw}px>"
          .    "</span>"
          .   "</td>"
          .   "</form>"
          .  "</table>"

          .  $inset_bridge

          .  "<table width=$pw>"
          .   "<form action=forums.php target=pan>"
          .   "<input type=hidden name=mtr value=y>"
          .   "<td>"
          .    "<input type=submit class=ky value=\"mark recent posts read\" style=width:{$pw}px>"
          .   "</td>"
          .   "</form>"
          .  "</table>"

          .  $inset_bridge

          .  $genw;

  }

}

/*
 *
 * template initialization (everyone is informed about the actual system load)
 *
 */

if (($no_validation_page) && ($no_reg_denial)) {

  /*
   *
   * generate system load meter:
   *
   * 440 is the width, in pixels, of "layout/images/votebar1.gif" used to fill the bars,
   * which was originally designed to be used in polls, but which may fit here as well.
   *
   */

  $ticker_life = $epc - @filemtime ($tStartFile);

  if ($ticker_life < 10) {

    /*
      below ten seconds of life for the ticker-file is considered insufficient for an estimation
    */

    $ticker = "ELAPSING";
    $per = "";

    $load_indicator = -440;

  }
  else {

    $ticker = (int) (wFilesize ($tickerFile) / $ticker_life);
    $per = "%";

    $load_indicator = (int) (($iw * $ticker) / 100) - 440;
    $load_indicator = ($load_indicator > ($iw - 440)) ? $iw - 440 : $load_indicator;

  }

  $load = "<table width=$pw>"
        .   "<td height=20 class=inv align=center>"
        .    "ABOUT THIS SYSTEM"
        .   "</td>"
        .  "</table>"
        . $inset_bridge
        . $opcart
        .  "<td class=ls align=center style=\"width:{$iw}px;"
        .  "background-image:url(layout/images/votebar1.gif);"
        .  "background-position:{$load_indicator}px 0px;"
        .  "background-repeat:repeat-y\">"
        .   ((int) ($avg_load_reset / 60)) . "-min System Load: $ticker $per"
        .  "</td>"
        . $clcart
        . $inset_shadow;

}

else {

  $load = voidString;

}

$sidebar = str_replace

  (

    array (

      "[FORM]",
      "[PERMALINK]"

    ),

    array (

      $form . $load,
      $permalink

    ),

    $sidebar

  );

/*
 *
 * saving navigation tracking informations for recovery of central frame page upon showing or
 * hiding the chat frame, and for the online members list links (unless no-spy flag checked):
 *
 *    - before, cut any GET arguments from the request URI to avoid repeating actions on re-loads
 *
 */

$_SERVER['REQUEST_URI'] = strBefore ($_SERVER['REQUEST_URI'], '?');
include ("setlfcookie.php");

/*
 *
 * releasing locked session frame, page output
 *
 */

echo (pquit ($sidebar));



?>
