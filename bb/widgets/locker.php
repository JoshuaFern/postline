<?php require ('widgets/overall/primer.php'); if (!defined ("$productName/widgets/locker.php")) {

/*************************************************************************************************

            Copyright (C) 2009 by Alessandro Ghignola

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
 *      includes
 *
 */

require ('widgets/errors.php');

/*
 *
 *      race-condition protected access settings
 *
 */

$lockfile = 'widgets/sync/lock.txt';    // path and name of the lockfile (relative to current)
$rcTimeOut = 500;                       // with 1/100th of second per attempt 500 = 5 seconds
$rcTimeSlice = 33000;                   // used if usleep not supported (measured on 1GHz CPU)
$rcTimeOutms = 10000;                   // single attempt's time slice: 0 to use $rcTimeSlice

/*
 *
 *      globals
 *
 */

$lockedSession = false;         // gets true while currently locking read/write access

/*
 *
 *      error messages from the session locking functions
 *
 */

$em['cannotUnlock'] = 'LOCKFILE COULD NOT BE UNLOCKED';
$ex['cannotUnlock'] =

        "Race conditions across concurrently executing PHP scripts of this installation "
      . "relies on a special 'lockfile' to assign exclusive write access to certain "
      . "shared archives and databases; the file needs to be brought from unlocked to "
      . "locked state upon acquiring an exclusive lock, and vice-versa, must be brought "
      . "back to an unlocked state upon releasing it; this error condition occurs because "
      . "the lockfile could not be brought to unlocked state, and may be a consequence of "
      . "insufficient access rights to that file.";

$em['cantInitLockfile'] = 'LOCKFILE COULD NOT BE REACHED FOR INITIALIZATION';
$ex['cantInitLockfile'] =

        "Race conditions across concurrently executing PHP scripts of this installation "
      . "relies on a special 'lockfile' to assign exclusive write access to certain "
      . "shared archives and databases; the file needs to be brought from unlocked to "
      . "locked state upon acquiring an exclusive lock, and vice-versa, must be brought "
      . "back to an unlocked state upon releasing it; this error condition occurs because "
      . "the lockfile's intended location on the server could not be reached from the "
      . "installed scripts, possibly because the path to the lockfile points to a folder "
      . "that does not exist server-side, or is not accessible by the relevant scripts.";

$em['cannotLock'] = 'LOCKFILE COULD NOT BE LOCKED';
$ex['cannotLock'] =

        "Race conditions across concurrently executing PHP scripts of this installation "
      . "relies on a special 'lockfile' to assign exclusive write access to certain "
      . "shared archives and databases; the file needs to be brought from unlocked to "
      . "locked state upon acquiring an exclusive lock, and vice-versa, must be brought "
      . "back to an unlocked state upon releasing it; this error condition occurs because "
      . "the lockfile could not be brought to locked state, and may be a consequence of "
      . "insufficient access rights to that file.";

/*
 *
 *      error handler:
 *
 *      simply, if the session locker is in use, the session may be locked and may need
 *      to be unlocked before having some random script "dying" on unrecoverable errors
 *
 */

$errorHandlers[] = 'unlockSession();';

/*
 *
 *      session locker functions:
 *      avoids interferences in race conditions by using a lockfile as a persistent flag
 *
 */

function getLockfileSize () {

        global $lockfile;

        /*
         *
         *      lockfile size check:
         *
         *      this is supposed to return the actual size of the lockfile: PHP's cache of
         *      such informations must be discarded, so it will not report outdated sizes;
         *      the lockfile must be 1 byte for writes to be allowed: if it's 2 bytes, one
         *      other script is actually writing to the database, if it's more than 2, two
         *      or more scripts are trying to set a write lock with lockLockFile(), at the
         *      same time, and lockLockFile will react to those situations
         *
         */

        clearstatcache ();

        return (@filesize ($lockfile));

}

function delayLock () {

        global $rcTimeSlice;
        global $rcTimeOutms;

        /*
         *
         *      delay between attempts to get read clearance, or write lock clearance:
         *
         *      this delay is given by how much time it takes for the server to perform a
         *      void loop for the amount of iterations given by $rcTimeSlice, at least
         *      on systems that don't provide a working implementation of usleep(); if it
         *      is possible to do a usleep(), $rcTimeOutms is set to non-zero, and its value
         *      will be passed to the usleep() function
         *
         */

        if ($rcTimeOutms == 0) {

                $n = $rcTimeSlice;
                while (-- $n);

        }

        else {

                usleep ($rcTimeOutms);

        }

}

function unlockLockfile () {

        global $lockfile;

        /*
         *
         *      unlocking write access:
         *
         *      when a script has finished writing to any of the database files it can
         *      release the write lock for other scripts to be allowed to do their own
         *      writes; this function releases the lock, by unconditionally truncating
         *      the lockfile to 1 byte
         *
         */

        $h = @fopen ($lockfile, 'r+');

        if ($h !== false) {

                @ftruncate ($h, 1);
                @fclose ($h);

        }

        else {

                die (because ('cannotUnlock'));

        }

}

function waitReadClearance () {

        global $lockfile;
        global $rcTimeOut;

        /*
         *
         *      lockfile presence check:
         *
         *      if the lockfile doesn't exist i.e. its size is zero bytes, create a lockfile,
         *      with the given name, and let it be 1 byte in size: 1 byte means that there is
         *      no script that's writing to the database; then return, because if there was
         *      no lockfile at all, it's obvious that we have read access
         *
         */

        if (getLockfileSize () == 0) {

                $h = @fopen ($lockfile, 'w');

                if ($h !== false) {

                        fwrite ($h, chr (46));
                        fclose ($h);

                }

                else {

                        die (because ('cantInitLockfile'));

                }

                return;

        }

        /*
         *
         *      check read clearance:
         *
         *      it only implies the lockfile to be 1 byte: do upto $rctimeout iterations,
         *      and for each iteration see if the size of the lockfile is 1 byte: as soon
         *      as the condition is met, return; else keep the script going until another
         *      script finished writing to the database
         *
         */

        for ($t = 0; $t < $rcTimeOut; ++ $t) {

                if (getLockfileSize () == 1) {

                        return;

                }

                delayLock ();

        }

        /*
         *
         *      forceful unlock (timeout):
         *
         *      no script is supposed to exceed the time span given by n times the time taken
         *      by a call to delayLock(), with n being the value of $rcTimeOut; in such cases
         *      the script supposes that there must have been a user break while the database
         *      was being locked by another script, so the lockfile remained stuck to 2 bytes:
         *      this function will now forcely truncate the lockfile to 1 byte, and return
         *
         */

        unlockLockfile ();

}

function lockLockfile () {

        global $lockfile;
        global $rcTimeOut;

        /*
         *
         *      lockfile presence check:
         *
         *      if the lockfile doesn't exist i.e. its size is zero bytes, create a lockfile,
         *      with the given name, and let it be 1 byte in size: 1 byte means that there is
         *      no script that's writing to the database; otherwise proceed to append a byte,
         *      in an attempt to do so while no other script is trying to do the same: such a
         *      condition will be, however, checked
         *
         */

        if (getLockfileSize () == 0) {

                $h = @fopen ($lockfile, 'w');

                if ($h !== false) {

                        fwrite ($h, chr (46));
                        fclose ($h);

                }

                else {

                        die (because ('cantInitLockfile'));

                }

                return;

        }

        while (1) {

                /*
                 *
                 *      check lock clearance:
                 *
                 *      loop for upto $rctimeout iterations with each iteration separated by the
                 *      amount of time taken by a single call to delayLock(); for every iteration,
                 *      check if the size of the lockfile is 1 byte: this means no other script is
                 *      writing to the database, and so this script can try to lock write access,
                 *      to perform its own duties undisturbed
                 *
                 */

                for ($t = 0; $t < $rcTimeOut; ++ $t) {

                        if (getLockfileSize () == 1) {

                                /*
                                 *
                                 *      the lockfile is 1 byte - lock clearance granted:
                                 *
                                 *      this function can try to get exclusive write clearance to
                                 *      the database, by appending a single byte after the single
                                 *      dot within the lockfile
                                 *
                                 */

                                $h = @fopen ($lockfile, 'a');

                                if ($h !== false) {

                                        fwrite ($h, chr (46));
                                        fclose ($h);

                                }

                                else {

                                        die (because ('cannotLock'));

                                }

                                if (getLockfileSize () == 2) {

                                        /*
                                         *
                                         *      exclusive write clearance is granted:
                                         *
                                         *      if the lockfile is now 2 bytes we have an exclusive
                                         *      lock, because no other script seems to have added a
                                         *      byte to that lock file while we were doing so; this
                                         *      function returns, knowing that it now has exclusive
                                         *      write clearance
                                         *
                                         */

                                        return;

                                } // lockfilesize == 2 after appending one byte

                                else {

                                        /*
                                         *
                                         *      perfectly concurrent lockings:
                                         *
                                         *      if the lockfile's more than 2 bytes, another script
                                         *      is running at the same exact time; the other script
                                         *      found lockfilesize was 1 as well as this script, so
                                         *      both scripts have now appended 1 byte to the file,
                                         *      and that caused the file to grow to three (or more)
                                         *      bytes! - the solution is as follows:
                                         *
                                         *    - we have to first unlock the file: this might not be
                                         *      dangerous, since the check that entered this block
                                         *      of code said lockfilesize was 1, so no other script
                                         *      (but the actual concurrent scripts) was attempting
                                         *      to get a lock
                                         *
                                         *    - at that point it will be alright to accept locking,
                                         *      but we have to solve the issue that such concurrent
                                         *      scripts may be running perfectly synched, executing
                                         *      the same exact instruction while the other/s do; so
                                         *      I'll make them waste some random amount of time, no
                                         *      matter how tiny the delay may be, as long as it's a
                                         *      genuinely random amount: when the delay ends, each
                                         *      concurrent script will retry to get exclusive lock,
                                         *      and whichever of them comes first, gets it
                                         *
                                         */

                                        unlockLockfile ();

                                        for ($n = mt_rand (1, 1000); $n > 0; -- $n);

                                } // lockfilesize > 2 after appending one byte

                        } // lockfilesize == 1

                        else {

                                /*
                                 *
                                 *      the lockfile is not 1 byte - no lock clearance (yet):
                                 *
                                 *      wait for another iteration, to allow the script that's got
                                 *      the lock to complete its duties and release the lock...
                                 *
                                 */

                                delayLock ();

                        } // lockfilesize > 1

                } // timeout loop

                /*
                 *
                 *      forceful unlock (timeout):
                 *
                 *      no script is supposed to exceed the time frame given by n times the time
                 *      taken by a call to delayLock(), with n being the value of $rctimeout; in
                 *      such cases this script supposes that there must have been a "user break"
                 *      while execution was locked by another script, and the lockfile remained
                 *      "stuck" to 2 bytes: this function will now forcely truncate the lockfile
                 *      to 1 byte, granting write access to whichever script was waiting for it,
                 *      and then retry getting a lock; this should never happen because a script
                 *      exceeded the amount of time of $rctimeout iterations, but only on breaks
                 *
                 */

                unlockLockfile ();

        } // endless loop (function will not return until it succeeds)

}

function lockSession () {

        global $lockedSession;
        global $onSessionLock;

        /*
         *
         *      entering locked session frame:
         *
         *      check if this script didn't lock before, and if it didn't, call 'lockLockfile'
         *      to obtain an exclusive write lock; event handlers are called just after having
         *      locked the session
         *
         */

        if ($lockedSession == false) {

                lockLockfile ();

                foreach ($onSessionLock as $h) {

                        eval ($h);

                }

                $lockedSession = true;

        }

}

function unlockSession () {

        global $lockedSession;
        global $onSessionUnlock;

        /*
         *
         *      exiting locked session frame:
         *
         *      check if this script obtained a write lock after calling 'lockSession',
         *      then call unlockLockfile() to release the lock; for better performance,
         *      and for having smaller locked-access time frames, it is recommended to
         *      keep scripts from locking during most of their time, whenever possible,
         *      and place most write operations, preceeded by a call to 'lockSession',
         *      as near as possible to each other in the run time of the script; event
         *      handlers are called just before leaving the locked frame
         *
         */

        if ($lockedSession == true) {

                foreach ($onSessionUnlock as $h) {

                        eval ($h);

                }

                unlockLockfile ();

                $lockedSession = false;

        }

}

define ("$productName/widgets/locker.php", true); } ?>
