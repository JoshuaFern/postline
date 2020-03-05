<?php require ('widgets/overall/primer.php'); if (!defined ("$productName/widgets/dbengine.php")) {

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
 *	includes
 *
 */

require ('widgets/errors.php');
require ('widgets/strings.php');

/*
 *
 *	'set' and 'get' query defines
 *
 */

define ('newRecord'	, voidString);
define ('wholeRecord'	, voidString);
define ('deleteField'	, voidString);
define ('deleteRecord'	, voidString);

/*
 *
 *	'all' and 'asm' query defines
 *
 */

define ('asIs'		, 1);
define ('makeProper'	, 2);

/*
 *
 *	engine database parameters:
 *
 *	$dbSqli is a flag: if true it will use 'libsqli' (SQLimproved) function calls to perform
 *	queries via these "virtualized file access" functions; if false, it will use the regular
 *	legacy 'libsql' as the intended PHP extension providing MySQL access
 *
 */

$dbSqli = true; 		// generally true for using MySQL 5+ with libsqli, false otherwise
$dbHost = 'localhost';		// to be changed if SQL server is not the same machine running PHP
$dbUser = 'root';		// locally 'root'; else, a username associated with your homestead
$dbPass = 'something';		// password of your choice, or the one chosen upon creating the DB
$dbName = 'pl2010';		// locally any name; else, the name chosen when you created the DB

/*
 *
 *	engine cache parameters:
 *
 *	setting $cacheIx to zero (or less) disables the caching mechanism, but should not be done
 *	while a script is in its run time except after the entire cache is flushed and cleared by
 *	calling 'invalidateCache'
 *
 */

$cacheIx = 50;	// maximum number of cache slots, holding an equivalent number of files, in memory

/*
 *
 *	errors logging:
 *	name of errors log file; redefine to redirect (append) logged errors somewhere else
 *
 */

$dbengineLog = 'widgets/logfiles/errorlog.txt';

/*
 *
 *	globals
 *
 */

$sql		= false;	// assume no database link established so far
$queryCount	= 0;		// assume no queries performed so far
$ownersOf	= array ();	// cache of backup owners arrays, keyed by folder and file
$cacheSt	= array ();	// array of file contents ("storage") being cached, indexed by file
$cacheOp	= array ();	// array of operations, 'r' (read) or 'w' (write), indexed by file
$cacheOverflow	= false;	// true if one or more cache overflows occured (not enough slots)

/*
 *
 *	error messages
 *
 */

  $em['wSqlConnect_no_library']
= $em['wSqlConnect_type_mismatch']
= $em['wSqlConnect_void_argument']
= $em['wSqlSelectDb_type_mismatch']
= $em['wSqlSelectDb_void_dbName']
= $em['wSqlQuery_type_mismatch']
= $em['wSqlQuery_void_QueryString']
= $em['makeProperRecord_type_mismatch']
= $em['missing_initial_key_in_record']
= $em['newline_in_record_image']
= $em['invalid_record_layout']
= $em['truncated_key_in_record']
= $em['intervalOf_type_mismatch']
= $em['intervalOf_negative_ID']
= $em['intervalOf_invalid_granularity']
= $em['offsetOf_type_mismatch']
= $em['offsetOf_negative_position']
= $em['recordOf_type_mismatch']
= $em['recordOf_negative_position']
= $em['valueOf_void_key']
= $em['valueOf_invalid_key']
= $em['fieldSet_type_mismatch']
= $em['fieldSet_void_key']
= $em['fieldSet_invalid_key']
= $em['fieldSet_invalid_value']
= $em['set_type_mismatch']
= $em['set_corrupted_file']
= $em['set_invalid_query']
= $em['set_invalid_key']
= $em['set_invalid_value']
= $em['set_corrupted_record']
= $em['get_type_mismatch']
= $em['get_corrupted_file']
= $em['get_invalid_query']
= $em['get_corrupted_record']
= $em['get_invalid_key']
= $em['all_type_mismatch']
= $em['all_corrupted_file']
= $em['asm_type_mismatch']

	= 'WIDGET CLIENT ERROR';

$ex['wSqlConnect_no_library'] =

	  "Client '$productName': "
	. "SQL library functions are unavailable.";

$ex['wSqlConnect_type_mismatch'] =

	  "Client '$productName': "
	. "type mismatch in wSqlConnect call; given arguments were not of string type.";

$ex['wSqlConnect_void_argument'] =

	  "Client '$productName': "
	. "void argument in wSqlConnect call; one or more given arguments were void strings.";

$ex['wSqlSelectDb_type_mismatch'] =

	  "Client '$productName': "
	. "type mismatch in wSqlSelectDb call; given database name was not of string type.";

$ex['wSqlSelectDb_void_dbName'] =

	  "Client '$productName': "
	. "void argument in wSqlSelectDb call; given database name was a void string.";

$ex['wSqlQuery_type_mismatch'] =

	  "Client '$productName': "
	. "type mismatch in wSqlQuery call; given query string was not of string type.";

$ex['wSqlQuery_void_queryString'] =

	  "Client '$productName': "
	. "void argument in wSqlQuery call; given query string was a void string.";

$em['cant_connect_to_sql'] = 'CANNOT CONNECT TO SQL';
$ex['cant_connect_to_sql'] =

	  "The software is experiencing troubles connecting to the SQL database "
	. "on its server. This is probably a temporary problem and will be soon "
	. "corrected. For the moment, please accept our apologies.";

$em['cant_select_database'] = 'UNABLE TO SELECT DATABASE';
$ex['cant_select_database'] =

	  "The software is experiencing troubles selecting its intended database "
	. "on its server. This inconvenience is probably temporary, and might be "
	. "soon corrected. For the moment, please accept our apologies.";

$ex['makeProperRecord_type_mismatch'] =

	  "Client '$productName': "
	. "makeProperRecord detected a record that was not of string type.";

$ex['missing_initial_key_in_record'] =

	  "Client '$productName': "
	. "makeProperRecord detected a record which lacks an initial key (or unclassified data).";

$ex['newline_in_record_image'] =

	  "Client '$productName': "
	. "makeProperRecord detected a record image which includes one or more newline codes.";

$ex['invalid_record_layout'] =

	  "Client '$productName': "
	. "makeProperRecord detected a record which layout is invalid (may have been damaged).";

$ex['truncated_key_in_record'] =

	  "Client '$productName': "
	. "makeProperRecord detected a record having a truncated key at its end (it may "
	. "have been damaged in consequence of reading from an incomplete database file).";

$ex['intervalOf_type_mismatch'] =

	  "Client '$productName': "
	. "type mismatch in intervalOf call; given arguments were not of integer type.";

$ex['intervalOf_negative_ID'] =

	  "Client '$productName': "
	. "intervalOf was called with a negative record ID.";

$ex['intervalOf_invalid_granularity'] =

	  "Client '$productName': "
	. "intervalOf was called with a negative or zero granularity.";

$ex['offsetOf_type_mismatch'] =

	  "Client '$productName': "
	. "type mismatch in offsetOf call; given data stream was not of string type, "
	. "or given position was not of integer type.";

$ex['offsetOf_negative_position'] =

	  "Client '$productName': "
	. "offsetOf was called with a negative position.";

$ex['recordOf_type_mismatch'] =

	  "Client '$productName': "
	. "type mismatch in recordOf call; given data stream was not of string type, "
	. "or given position was not of integer type.";

$ex['recordOf_negative_position'] =

	  "Client '$productName': "
	. "recordOf was called with a negative position.";

$ex['valueOf_void_key'] =

	  "Client '$productName': "
	. "valueOf was called with a void string as the key.";

$ex['valueOf_invalid_key'] =

	  "Client '$productName': "
	. "valueOf was called with an invalid key.";

$ex['fieldSet_type_mismatch'] =

	  "Client '$productName': "
	. "type mismatch in fieldSet call; given arguments were not of string type.";

$ex['fieldSet_void_key'] =

	  "Client '$productName': "
	. "fieldSet was called with a void string as the key.";

$ex['fieldSet_invalid_key'] =

	  "Client '$productName': "
	. "fieldSet was called with an invalid key.";

$ex['fieldSet_invalid_value'] =

	  "Client '$productName': "
	. "fieldSet was called with an invalid value.";

$ex['set_type_mismatch'] =

	  "Client '$productName': "
	. "type mismatch in 'set' function call; given arguments were not of string type.";

$ex['set_corrupted_file'] =

	  "Client '$productName': "
	. "the 'set' function detected a corrupted file (neither void nor terminated by newline).";

$ex['set_invalid_query'] =

	  "Client '$productName': "
	. "an invalid query string was passed as the 'where' argument of the 'set' function.";

$ex['set_invalid_key'] =

	  "Client '$productName': "
	. "the 'set' function was called with an invalid key.";

$ex['set_invalid_value'] =

	  "Client '$productName': "
	. "the 'set' function was called with an invalid value.";

$ex['set_corrupted_record'] =

	  "Client '$productName': "
	. "the 'set' function detected an improper record, probably corrupted by truncation.";

$ex['get_type_mismatch'] =

	  "Client '$productName': "
	. "type mismatch in 'get' function call; given arguments were not of string type.";

$ex['get_corrupted_file'] =

	  "Client '$productName': "
	. "the 'get' function detected a corrupted file (neither void nor terminated by newline).";

$ex['get_invalid_query'] =

	  "Client '$productName': "
	. "an invalid query string was passed as the 'where' argument of the 'get' function.";

$ex['get_corrupted_record'] =

	  "Client '$productName': "
	. "the 'get' function detected an improper record, probably corrupted by truncation.";

$ex['get_invalid_key'] =

	  "Client '$productName': "
	. "the 'get' function was called with an invalid key.";

$ex['all_type_mismatch'] =

	  "Client '$productName': "
	. "type mismatch in 'all' function call; given file name was not of string type, "
	. "or behavior selector was not among allowed values.";

$ex['all_corrupted_file'] =

	  "Client '$productName': "
	. "the 'all' function detected a corrupted file (neither void nor terminated by newline).";

$ex['asm_type_mismatch'] =

	  "Client '$productName': "
	. "type mismatch in 'asm' function call; given file name was not of string type, "
	. "behavior selector was not among allowed values, or given records array is not "
	. "an array of strings.";

/*
 *
 *	error handler
 *
 */

function onDbEngineError () {

	global $cacheOverflow;
	global $dbengineLog;

	/*
	 *
	 *	aside of that, scripts raising unrecoverable errors might not touch the
	 *	database in any ways; under normal circumstances this is ensured by the
	 *	caching mechanism, but that could fail if the cache was "overflowed" by
	 *	accessing an amount of different files in excess of 'cacheIx', in which
	 *	case forceful dumping of busy cache slots may have occured; this should
	 *	be reported to a log file, warning database files may have been left in
	 *	an inconsistent state (e.g. directories of entries may refer to entries
	 *	that were not yet saved as instances somewhere else)
	 *
	 */

	if ($cacheOverflow == true) {

		$requestDate = (isset ($_SERVER['REQUEST_TIME']))

			? $_SERVER['REQUEST_TIME']
			: time ();

		$logDate = gmdate ('M d Y H:i:s', $requestDate);
		$message = 'ONE OR MORE FILE WRITES FLUSHED BEFORE AN UNRECOVERABLE ERROR';

		$hFile = @fopen ($dbengineLog, 'ab');
		$error = ($h === false) ? true : false;

		if ($error == false) {

			fwrite ($hFile, $logDate . blank . '--' . blank . $message . "\n");
			fclose ($hFile);

		}

	}

} $errorHandlers[] = 'onDbEngineError();';

/*
 *
 *	SQL interfacing functions
 *
 */

function wSqlConnect ($dbHost, $dbUser, $dbPass) {

	global $dbSqli;

	$bogusCall = (is_string ($dbHost)) ? false : true;
	$bogusCall = (is_string ($dbUser)) ? $bogusCall : true;
	$bogusCall = (is_string ($dbPass)) ? $bogusCall : true;

	/*
	 *
	 *	connect to SQL or SQLi (SQL-improved) database,
	 *	according to configuration parameters
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	either argument is no string
		 *
		 */

		die (because ('wSqlConnect_type_mismatch'));

	}

	$nullHost = ($dbHost == voidString) ? true : false;
	$nullUser = ($dbUser == voidString) ? true : false;
	$nullPass = ($dbPass == voidString) ? true : false;

	if (($nullHost) || ($nullUser) || ($nullPass)) {

		/*
		 *
		 *	either argument is a void string
		 *
		 */

		die (because ('wSqlConnect_void_argument'));

	}

	$sqlReady = ($dbSqli)

		? (function_exists ('mysqli_connect') ? true : false)
		: (function_exists ('mysql_connect') ? true : false);

	if ($sqlReady == false) {

		/*
		 *
		 *	SQL library is unavailable
		 *
		 */

		die (because ('wSqlConnect_no_library'));

	}

	return (($dbSqli)

		? @mysqli_connect ($dbHost, $dbUser, $dbPass)
		: @mysql_connect ($dbHost, $dbUser, $dbPass)

	);

}

function wSqlSelectDb ($dbName) {

	global $dbSqli;
	global $sql;

	$bogusCall = (is_string ($dbName)) ? false : true;

	/*
	 *
	 *	select SQL or SQLi (SQL-improved) database,
	 *	according to configuration parameters
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	$dbName is no string
		 *
		 */

		die (because ('wSqlSelectDb_type_mismatch'));

	}

	if ($dbName == voidString) {

		/*
		 *
		 *	$dbName is a void string
		 *
		 */

		die (because ('wSqlSelectDb_void_dbName'));

	}

	return (($dbSqli)

		? @mysqli_select_db ($sql, $dbName)
		: @mysql_select_db ($dbName, $sql)

	);

}

function wSqlQuery ($queryString) {

	global $dbSqli;
	global $dbHost;
	global $dbName;
	global $dbUser;
	global $dbPass;

	global $sql;
	global $queryCount;

	$bogusCall = (is_string ($queryString)) ? false : true;

	/*
	 *
	 *	query SQL or SQLi (SQL-improved) database,
	 *	according to configuration parameters
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	$queryString is no string
		 *
		 */

		die (because ('wSqlQuery_type_mismatch'));

	}

	if ($queryString == voidString) {

		/*
		 *
		 *	$queryString is a void string
		 *
		 */

		die (because ('wSqlQuery_void_queryString'));

	}

	/*
	 *
	 *	if first query, connect to database server and select database:
	 *	this is an optimization that avoids repeating connections when unnecessary
	 *
	 */

	if ($queryCount == 0) {

		$sql = wSqlConnect ($dbHost, $dbUser, $dbPass);

		if ($sql == false) {

			die (because ('cant_connect_to_sql'));

		}

		$selected = wSqlSelectDB ($dbName);

		if ($selected == false) {

			die (because ('cant_select_database'));

		}

	}

	++ $queryCount;

	return (($dbSqli)

		? @mysqli_query ($sql, $queryString)
		: @mysql_query ($queryString, $sql)

	);

}

function wSqlAffectedRows () {

	global $dbSqli;
	global $sql;

	/*
	 *
	 *	return number of rows affected by last operation throught SQL or SQLi link
	 *
	 */

	return (($dbSqli)

		? @mysqli_affected_rows ($sql)
		: @mysql_affected_rows ($sql)

	);

}

function wSqlFetchAssoc ($object) {

	global $dbSqli;

	$bogusCall = (is_object ($object)) ? false : true;

	/*
	 *
	 *	fetch associative array from SQL or SQLi (SQL-improved) result object
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	$object is no object,
		 *	but this may result from reading unexisting virtual files
		 *
		 */

		 // return (array ());

	}

	return (($dbSqli)

		? @mysqli_fetch_assoc ($object)
		: @mysql_fetch_assoc ($object)

	);

}

/*
 *
 *	virtual file access functions
 *
 */

function isInvalidName ($name) {

	$bogusCall = (is_string ($name)) ? false : true;

	/*
	 *
	 *	checks wether the string $name, supposedly a file or folder name,
	 *	contains illegal characters in the SQL scope; however, to be sure
	 *	that handled names might be legal everywhere, this just disallows
	 *	everything outside [a..z][A..Z][0..9], dash (-), dot, underscore:
	 *	returns boolean true if name is INVALID, false if name is valid
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	$name is no string, and thus invalid
		 *
		 */

		return (true);

	}

	return (preg_match ('/[^a-zA-Z0-9\-\.\_]/', $name) ? true : false);

}

function getFolders () {

	/*
	 *
	 *	return all tables existing in the database:
	 *	each table is a folder of the root directory of the virtual file system
	 *
	 */

	$j = wSqlQuery ('SHOW TABLES');

	if ($j === false) {

		/*
		 *
		 *	query failed:
		 *	database appears to be void, return void array
		 *
		 */

		return (array ());

	}

	else {

		/*
		 *
		 *	query succeeded:
		 *
		 *	each row ($r) has the name of the database and a folder's name,
		 *	make out and return an array holding the 2nd column of each row
		 *
		 */

		$s = array ();

		while ($r = wSqlFetchAssoc ($j)) {

			list (, $name) = each ($r);
			$s[] = $name;

		}

		return $s;

	}

}

function getFiles ($folder) {

	global $ownersOf;

	$bogusCall = (is_string ($folder)) ? false : true;

	/*
	 *
	 *	return all rows existing in table $folder:
	 *	each row is a file in that folder of the virtual file system
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	folder name is no string:
		 *	traten as a non-existing folder
		 *
		 */

		return (array ());

	}

	if ($folder == voidString) {

		/*
		 *
		 *	folder name is a void string
		 *
		 */

		return (array ());

	}

	if (isInvalidName ($folder)) {

		/*
		 *
		 *	invalid folder name:
		 *	it's supposed to be not existing and to contain no files
		 *
		 */

		return (array ());

	}

	$j = wSqlQuery ("SELECT name, backups FROM {$folder}");

	if ($j === false) {

		/*
		 *
		 *	query failed:
		 *	folder doesn't exist, return void array
		 *
		 */

		return (array ());

	}

	else {

		/*
		 *
		 *	query succeeded: each row ($r) is in the form 'name' => 'filename',
		 *	return an array holding all values of 'filename', and cache backup
		 *	owners lists in the array $ownersOf
		 *
		 */

		$s = array ();
		$b = array ();

		while ($r = wSqlFetchAssoc ($j)) {

			$name = $r['name'];

			$s[] = $name;
			$b[$name] = wExplode (';', base64_decode ($r['backups']));

		}

		$ownersOf[$folder] = $b;

		return ($s);

	}

}

function getIntervals ($folder) {

	$bogusCall = (is_string ($folder)) ? false : true;

	/*
	 *
	 *	return all "virtual files" from all divisions of the database matching $folder:
	 *	divisions are formed by the table matching the name of $folder, plus additional
	 *	tables matching $folder with a progressive numeric index appended, however this
	 *	function will also filter file names to see if they comply to intervals syntax,
	 *	whereby an interval can be intended as "a file having a name in the form %d-%d"
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	folder name is no string
		 *
		 */

		return (array ());

	}

	if ($folder == voidString) {

		/*
		 *
		 *	folder name is a void string
		 *
		 */

		return (array ());

	}

	if (isInvalidName ($folder)) {

		/*
		 *
		 *	invalid folder name
		 *
		 */

		return (array ());

	}

	/*
	 *
	 *	build array of divisions matching $folder (except legacy instance of folder)
	 *
	 */

	$f = getFolders ();
	$d = array ();

	foreach ($f as $folderName) {

		$divisionIndex = substr ($folderName, strlen ($folder));
		$folderName = substr ($folderName, 0, strlen ($folder));

		if (($folderName == $folder) && (is_numeric ($divisionIndex))) {

			$d[] = $folderName . $divisionIndex;

		}

	}

	/*
	 *
	 *	get all interval files from all divisions (including legacy instance of folder)
	 *
	 */

	$f = getFiles ($folder);	// these are files in legacy division (e.g. 'posts')

	foreach ($d as $divisionName) {

		$f = array_merge ($f, getFiles ($divisionName));

	}

	/*
	 *
	 *	filter file names to leave only those that match the definition of "interval",
	 *	then return filtered array
	 *
	 */

	$r = array ();	// resulting filtered array (initially void)

	foreach ($f as $fileName) {

		list ($lo, $hi) = explode ('-', $fileName);

		if ((is_numeric ($lo)) && (is_numeric ($hi))) {

			$r[] = $fileName;

		}

	}

	return ($r);

}

function getBackupOwners ($file) {

	global $ownersOf;

	$bogusCall = (is_string ($file)) ? false : true;

	/*
	 *
	 *	returns an array holding nicknames of backup owners of a given file:
	 *	backup owners are added to the list held in a 'backups' column, upon
	 *	requesting a backup copy of a file from the Database Explorer; lists
	 *	of backup owners are cleared whenever content is WRITTEN to a file
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	file name is no string
		 *
		 */

		return (array ());

	}

	if ($file == voidString) {

		/*
		 *
		 *	file name is a void string
		 *
		 */

		return (array ());

	}

	list ($f, $n, $extraNode) = explode ('/', $file);

	if ((isInvalidName ($f)) || (isInvalidName ($n)) || (isset ($extraNode))) {

		/*
		 *
		 *	invalid folder or file name:
		 *	notice that subfolders are currently not supported
		 *
		 */

		return (array ());

	}

	/*
	 *
	 *	unless cached, query 'backups' column of table $f where name is $n:
	 *	if query failed, return void array; if not, explode owners list by semicolon
	 *
	 */

	if (isset ($ownersOf[$f][$n])) {

		return ($ownersOf[$f][$n]);

	}

	else {

		$r = wSqlFetchAssoc (wSqlQuery ("SELECT backups FROM {$f} WHERE name='{$n}'"));

		$ownersOf[$f][$n] = ($r === false)

			? array ()
			: wExplode (';', base64_decode ($r['backups']));

		return ($ownersOf[$f][$n]);

	}

}

function setBackupOwner ($file, $owner) {

	$bogusCall = (is_string ($file)) ? false : true;
	$bogusCall = (is_string ($owner)) ? $bogusCall : true;

	/*
	 *
	 *	adds $owner (which is a nickname) to the list of backup owners of
	 *	the file indicated by $file: repeately adding the same $owner is
	 *	checked and does not cause an owner to be listed multiple times
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	file or owner name is no string
		 *
		 */

		return;

	}

	if (($file == voidString) || ($owner == voidString)) {

		/*
		 *
		 *	file or owner name is a void string
		 *
		 */

		return;

	}

	list ($f, $n, $extraNode) = explode ('/', $file);

	if ((isInvalidName ($f)) || (isInvalidName ($n)) || (isset ($extraNode))) {

		/*
		 *
		 *	invalid folder or file name:
		 *	notice that subfolders are currently not supported
		 *
		 */

		return;

	}

	/*
	 *
	 *	query 'backups' column of table $f where name is $n:
	 *	if query failed, set $r to a void array to simplify the subsequent check
	 *
	 */

	$r = wSqlFetchAssoc (wSqlQuery ("SELECT backups FROM $f WHERE name='$n'"));
	$r = ($r === false) ? array () : $r;

	/*
	 *
	 *	at least one row must be returned by the above query: it indicates the existence
	 *	of the file in the "virtual file system"; if no rows exist, no such file exists,
	 *	and nothing is done
	 *
	 */

	if (count ($r) === 0) {

		/*
		 *
		 *	file does not exist
		 *
		 */

		return;

	}

	/*
	 *
	 *	$p will be used to preserve the list of backup owners as it was before attempting
	 *	to add this owner: the query to update the list in the database will be done only
	 *	if the resulting list will be different from $p
	 *
	 */

	$p = base64_decode ($r['backups']);
	$r = implode (';', wArrayUnique (array_merge (wExplode (';', $p), array ($owner))));

	if ($r == $p) {

		/*
		 *
		 *	given backup owner was already listed among this file's backup owners
		 *
		 */

		return;

	}

	wSqlQuery ("UPDATE $f SET backups='" . (base64_encode ($r)) . "' WHERE name='$n'");

}

function unSetBackupOwner ($file, $owner) {

	$bogusCall = (is_string ($file)) ? false : true;
	$bogusCall = (is_string ($owner)) ? $bogusCall : true;

	/*
	 *
	 *	removes $owner (which is a nickname) from the list of backup owners of the file
	 *	indicated by $filename: this is typically called to "disclaim" some lost backup
	 *	copy, or to get an additional backup copy, or to make sure a copy of given file
	 *	will be sent as part of a complete backup packet
	 *
	 *	returns 0 if the specified $owner wasn't really in the list of backup owners of
	 *	the target file (and thus no changes were made), 1 if the owner was effectively
	 *	removed: the return value is ideally reported to be simply used for informative
	 *	reports by 'explorer.php' (they add up to count how many files were disclaimed)
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	file or owner name is no string
		 *
		 */

		return (0);

	}

	if (($file == voidString) || ($owner == voidString)) {

		/*
		 *
		 *	file or owner name is a void string
		 *
		 */

		return (0);

	}

	list ($f, $n, $extraNode) = explode ('/', $file);

	if ((isInvalidName ($f)) || (isInvalidName ($n)) || (isset ($extraNode))) {

		/*
		 *
		 *	invalid folder or file name:
		 *	notice that subfolders are currently not supported
		 *
		 */

		return (0);

	}

	/*
	 *
	 *	query 'backups' column of table $f where name is $n:
	 *	if query failed, set $r to a void array to simplify the subsequent check
	 *
	 */

	$r = wSqlFetchAssoc (wSqlQuery ("SELECT backups FROM $f WHERE name='$n'"));
	$r = ($r === false) ? array () : $r;

	/*
	 *
	 *	at least one row must be returned by the above query: it indicates the existence
	 *	of the file in the "virtual file system"; if no rows exist, no such file exists,
	 *	and nothing is done
	 *
	 */

	if (count ($r) === 0) {

		/*
		 *
		 *	file does not exist
		 *
		 */

		return;

	}

	/*
	 *
	 *	$p will be used to preserve the list of backup owners as it was before attempting
	 *	to remove this backup owner: the query to update the list in the database will be
	 *	done only if the resulting list will be different from $p
	 *
	 */

	$p = base64_decode ($r['backups']);
	$r = implode (';', wArrayDiff (wExplode (';', $p), array ($owner)));

	if ($r == $p) {

		/*
		 *
		 *	given backup owner was already listed among this file's backup owners
		 *
		 */

		return (0);

	}

	wSqlQuery ("UPDATE $f SET backups='" . (base64_encode ($r)) . "' WHERE name='$n'");

	return (1);

}

function virtualFileRead ($file) {

	global $ownersOf;

	$bogusCall = (is_string ($file)) ? false : true;

	/*
	 *
	 *	database file read:
	 *
	 *	retrieves contents from a table in a SQL database; the table's name is given by
	 *	the name of the folder holding the file, and the table's row corresponding to the
	 *	file is located by using the file name as key, held by column 'name' in the said
	 *	table; if query succeeds, file data is returned from the 'content' variable-length
	 *	text field of the same table (as a string); a void string is returned on error, as
	 *	an existing void file might be impossible due to checks performed by 'writeFile'
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	file name is no string
		 *
		 */

		return (voidString);

	}

	if ($file == voidString) {

		/*
		 *
		 *	file name is a void string
		 *
		 */

		return (voidString);

	}

	list ($f, $n, $extraNode) = explode ('/', $file);

	if ((isInvalidName ($f)) || (isInvalidName ($n)) || (isset ($extraNode))) {

		/*
		 *
		 *	invalid folder or file name:
		 *	notice that subfolders are currently not supported
		 *
		 */

		return (voidString);

	}

	/*
	 *
	 *	query 'content' column of table $f where name is $n:
	 *	if query failed, set $r to a void array to simplify the subsequent check
	 *
	 */

	$r = wSqlFetchAssoc (wSqlQuery ("SELECT content, backups FROM $f WHERE name='$n'"));
	$r = ($r === false) ? array () : $r;

	if (count ($r) == 0) {

		return (voidString);

	}

	/*
	 *
	 *	set or update backup owners list in memory cache
	 *
	 */

	$ownersOf[$f][$n] = wExplode (';', base64_decode ($r['backups']));

	/*
	 *
	 *	return file content read from virtual file: if first character is found to be an
	 *	underscore sign, file contents are assumed to be base64-encoded as of Postline
	 *	version 6.1.30 and later, but since then, that became a general convention here:
	 *	as a consequence, all saved file contents in base64 might begin by an underscore
	 *
	 */

	$encodedContent = ($r['content'][0] === chr (95)) ? true : false;

	return (($encodedContent)

		? base64_decode (substr ($r['content'], 1))
		: $r['content']

	);

}

function virtualFileWrite ($file, $content) {

	global $ownersOf;

	$bogusCall = (is_string ($file)) ? false : true;
	$bogusCall = (is_string ($content)) ? $bogusCall : true;

	/*
	 *
	 *	database file write:
	 *
	 *	note that a write where $content is empty (a void string) causes the file to be
	 *	automatically deleted; a write where $content is empty and the write causes the
	 *	last file of the given folder to be deleted also causes the file's folder to be
	 *	deleted (i.e. its table gets completely dropped from the database); vice-versa,
	 *	writing non-empty content to the first file of a folder that does not yet exist
	 *	causes the folder to be created
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	file name or content is no string
		 *
		 */

		return;

	}

	if ($file == voidString) {

		/*
		 *
		 *	file name is a void string
		 *
		 */

		return;

	}

	list ($f, $n, $extraNode) = explode ('/', $file);

	if ((isInvalidName ($f)) || (isInvalidName ($n)) || (isset ($extraNode))) {

		/*
		 *
		 *	invalid folder or file name:
		 *	notice that subfolders are currently not supported
		 *
		 */

		return;

	}

	/*
	 *
	 *	set null write flag
	 *
	 */

	$nullWrite = ($content == voidString) ? true : false;

	/*
	 *
	 *	check file existence
	 *
	 */

	$r = wSqlFetchAssoc (wSqlQuery ("SELECT name FROM $f WHERE name='$n'"));
	$r = ($r === false) ? 0 : count ($r);

	if ($r == 0) {

		/*
		 *
		 *	file doesn't exist:
		 *	if the write is null, there's no need to change anything; if not...
		 *
		 */

		if ($nullWrite == false) {

			/*
			 *
			 *	...the query becomes an INSERT query to create a new row,
			 *	a new file, in $f, and it's sent to the server: if it succeeds
			 *	affecting rows ($r > 0), it's ok and the function is done writing
			 *	the new file; if it fails ($r == 0)...
			 *
			 */

			$content = chr (95) . base64_encode ($content);
			$insertQuery =

				"INSERT INTO $f" . chr (32)
			      . "(name, content, backups) VALUES ('$n', '$content', '')";

			$q = wSqlQuery ($insertQuery);
			$r = ($q === false) ? 0 : wSqlAffectedRows ();

			/*
			 *
			 *	...if it fails, it might mean the table (or folder) doesn't exist
			 *	yet, and needs be created before the query to insert the new file
			 *	is repeated
			 *
			 */

			if ($r == 0) {

				wSqlQuery (

					"CREATE TABLE $f" . chr (32)
				      . "(name TEXT NOT NULL, content MEDIUMTEXT, backups TEXT)"

				);

				wSqlQuery ($insertQuery);

			}

		} // non-null write to non-existing file

	} // write to non-existing file

	else {

		/*
		 *
		 *	file already exists
		 *
		 */

		if ($nullWrite == false) {

			/*
			 *
			 *	attempt to write to 'content' column of table $f where name is $n:
			 *	this would also write an empty string into the 'backups' column of
			 *	the same row, and so the cached backup owners list is devoided
			 *
			 */

			$content = chr (95) . base64_encode ($content);
			wSqlQuery ("UPDATE $f SET content='$content', backups='' WHERE name='$n'");

			unset ($ownersOf[$f][$n]);

		} // non-null write to existing file

		else {

			/*
			 *
			 *	null write to existing file:
			 *	attempt to delete row of table $f where name is $n
			 *
			 */

			$q = wSqlQuery ("DELETE FROM $f WHERE name='$n'");
			$r = ($q === false) ? 0 : wSqlAffectedRows ();

			/*
			 *
			 *	if a non-zero number of rows were affected, the 'delete' query
			 *	succeeded: now check if no more rows remained in $f and if yes,
			 *	drop table $f (delete folder if empty); in any cases, void the
			 *	list of backup owners from the corresponding cache array
			 *
			 */

			if ($r > 0) {

				if (count (getFiles ($f)) == 0) {

					wSqlQuery ("DROP TABLE $f");

				}

				unset ($ownersOf[$f][$n]);

			}

		} // null write to existing file

	} // write to existing file

}

/*
 *
 *	virtual file data caching functions,
 *	implementing read and write caching of contents read by 'readFrom' and written by 'writeTo'
 *
 */

function flushCache () {

	global $cacheSt;
	global $cacheOp;

	/*
	 *
	 *	flushes all write operations that have been cached so far, to the database: once
	 *	called, any cached write operation becomes a call to 'virtualFileWrite'; until
	 *	flushed, writes via 'writeTo' only modify memory images of cached files, so this
	 *	function must be called in some way before quitting a script's execution, to
	 *	"consolidate" changes to the affected database files; in Postline this is normally
	 *	done by calling 'pquit', which calls unlockSession that, through an onSessionUnlock
	 *	event handler, calls this function
	 *
	 */

	foreach ($cacheOp as $file => $operation) {

		if ($operation == 'w') {

			/*
			 *
			 *	after writing, operation turns to 'r' (read), to signal that file
			 *	may still be cached on subsequent reads, but it needs (so far) not
			 *	be written again: should this function be called again, the write
			 *	will not be repeated unless operation was turned back to 'w' by a
			 *	call to 'writeTo', in the meantime...
			 *
			 */

			virtualFileWrite ($file, $cacheSt[$file]);

			$cacheOp[$file] = 'r';

		}

	}

}

function invalidateCache () {

	global $cacheSt;
	global $cacheOp;

	/*
	 *
	 *	flushes the write cache AND clears the whole cache (from either past reads and
	 *	writes): this is ideally called to ensure that subsequent reads from database
	 *	files will return up-to-date informations, and is normally done upon entering
	 *	a locked session frame via a call to 'lockSession'; in a typical situation, a
	 *	script calls 'lockSession' when it wants to write something to the database with
	 *	a set of 'writeTo' calls; after the script called 'lockSession', this function
	 *	might be called to especially clear cached READS from memory, so that before
	 *	writing new informations the calling script could verify stored data by reading
	 *	the implied files while no other script, concurrently executing, writes to them
	 *
	 */

	flushCache ();		// flush write cache
	clearstatcache ();	// clear PHP's file statistics cache

	/*
	 *
	 *	clean up all memory images of files from the read-write cache:
	 *	subsequent reads to a certain file, at least for the first time, will be uncached
	 *
	 */

	$cacheSt = array ();
	$cacheOp = array ();

}

function emptyOneSlot () {

	global $cacheSt;
	global $cacheOp;
	global $cacheOverflow;

	/*
	 *
	 *	oh, well, this is an "emergency" flushing operation, and ideally it should never
	 *	happen: if the read-write cache becomes full (because there were not enough slots
	 *	to hold files) this function is called to free just one slot, before scheduling
	 *	another read or write; of course, freeing one slot discards the cache of what is
	 *	held by that slot, causing the corresponding file to be read again if re-accessed:
	 *	if enough cache slots are given (parameter $cacheIx) this should never be called
	 *
	 */

	foreach ($cacheOp as $file => $operation) {

		if ($operation == 'w') {

			$cacheOverflow = true;

			virtualFileWrite ($file, $cacheSt[$file]);

		}

		break;	// after the first cache slot has been parsed, and eventually flushed

	}

	/*
	 *
	 *	unconditionally delete the first cache slot:
	 *
	 *	if it's a write operation, it's been just flushed by above code;
	 *	if it's a read operation, erasing its slot is all there is to do
	 *
	 */

	$cacheOp = array_slice ($cacheOp, 1);
	$cacheSt = array_slice ($cacheSt, 1);

}

function readFrom ($file) {

	global $cacheIx;
	global $cacheSt;
	global $cacheOp;

	$bogusCall = (is_string ($file)) ? false : true;

	/*
	 *
	 *	CACHE-DRIVEN DATABASE FILE READ:
	 *	THIS IS THE FUNCTION TO CALL FOR ALL READS CONCERNING REGULAR DATABASE FILES
	 *
	 *		(at least if you want to take advantage of the caching mechanism)
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	file name is no string
		 *
		 */

		return (voidString);

	}

	if ($file == voidString) {

		/*
		 *
		 *	file name is a void string
		 *
		 */

		return (voidString);

	}

	$enableCache	= ($cacheIx > 0) ? true : false;
	$cachedFile	= ($enableCache) ? ((isset ($cacheOp[$file])) ? true : false) : false;

	if ($cachedFile) {

		/*
		 *
		 *	this file was cached before, so just return data from its memory image,
		 *	for the rest leaving the cache slot completely untouched...
		 *
		 */

		return ($cacheSt[$file]);

	}

	else {

		/*
		 *
		 *	this file has not been cached yet, during this run
		 *
		 */

		$data = virtualFileRead ($file);

		if ($enableCache) {

			if (count ($cacheOp) >= $cacheIx) {

				/*
				 *
				 *	if the cache is full then (reluctantly) free one slot,
				 *	this heavily affects performance, and shouldn't happen
				 *
				 */

				emptyOneSlot ();

			}

			/*
			 *
			 *	allocate one cache slot for this file,
			 *	so subsequent reads to the same file will be cached
			 *
			 */

			$cacheOp[$file] = 'r';
			$cacheSt[$file] = $data;

		}

		return ($data);

	} // cached versus not-yet-cached read

}

function writeTo ($file, $content) {

	global $cacheIx;
	global $cacheSt;
	global $cacheOp;

	$bogusCall = (is_string ($file)) ? false : true;
	$bogusCall = (is_string ($content)) ? $bogusCall : true;

	/*
	 *
	 *	CACHE-DRIVEN DATABASE FILE WRITE:
	 *	THIS IS THE FUNCTION TO CALL FOR ALL WRITES CONCERNING REGULAR DATABASE FILES
	 *
	 *		(at least if you want to take advantage of the caching mechanism)
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	file name is no string
		 *
		 */

		return;

	}

	if ($file == voidString) {

		/*
		 *
		 *	file name is a void string
		 *
		 */

		return;

	}

	$enableCache	= ($cacheIx > 0) ? true : false;
	$cachedFile	= ($enableCache) ? ((isset ($cacheOp[$file])) ? true : false) : false;

	if ($cachedFile) {

		/*
		 *
		 *	this file has been cached before, so just update its memory image
		 *
		 */

		$cacheOp[$file] = 'w';
		$cacheSt[$file] = $content;

	}

	else {

		/*
		 *
		 *	this file has not been cached yet, during this run
		 *
		 */

		if ($enableCache) {

			if (count ($cacheOp) >= $cacheIx) {

				/*
				 *
				 *	if the cache is full then (reluctantly) free one slot,
				 *	this heavily affects performance, and shouldn't happen
				 *
				 */

				emptyOneSlot ();

			}

			/*
			 *
			 *	after ensuring there is one available cache slot, allocate one
			 *	cache slot for this file, scheduling this write operation for
			 *	future flushing: while new writes may be done to the same file
			 *	before the cache gets flushed, cached writes would still use the
			 *	following code to keep the memory image of the file up to date
			 *
			 */

			$cacheOp[$file] = 'w';
			$cacheSt[$file] = $content;

		}

		else {

			/*
			 *
			 *	this file has not been cached yet, during this run,
			 *	but caching is disabled ($cacheIx = 0), so write it directly
			 *
			 */

			virtualFileWrite ($file, $content);

		}

	} // cached versus non-cached write

}

/*
 *
 *	relational database access functions:
 *
 *	these are unrelated to the above functions for virtualization of files, but if used they
 *	may help organizing files, records and fields of databases, while virtual file functions
 *	keep managing data in raw form as they interface with an underlying SQL database server;
 *	in general, a large database is organized in variable-width columns, in which fields are
 *	represented under the syntax: <fieldname>field content<nextfield>, sort of a "simplified
 *	XML"; to keep a single file from eventually getting too long, if a corresponding archive
 *	becomes very large, each row may be assigned a progressive ID and 'intervalOf' be called
 *	to determine the file name where each row has to be stored depending on the range of IDs
 *	that intervalOf assigns to that file, causing an archive to be split into multiple files,
 *	regularly being created as the ID of each new record raises; relevant ID counters should
 *	be held in a dedicated file (in Postline, it's 'stats/counters'), and are therefore very
 *	important to avoid conflicts between IDs; each file holding an interval of IDs is called
 *	an 'interval' of the archive; on an even greater scale, intervalOf implements divisions:
 *	divisions are additional folders, each containing upto 100 intervals, distinguished from
 *	the "legacy folder" by appending a progressive index to the archive folder's name; while
 *	the "granularity" of intervals is passed as an argument to 'intervalOf', and can vary on
 *	a per-archive basis, the granularity of divisions is fixed to 100 intervals per division
 *	in order to simplify the management (listing, exploration, generation of backup packets)
 *	of each division; e.g. Postline determines where to save the single message having ID $m
 *	by appending the string returned by intervalOf to the archive name 'posts', and choosing
 *	a default granularity of 150 entries per interval:
 *
 *		$dbFile = 'posts' . intervalOf ($m, 150);	// storing 150 messages per file
 *
 *	which, depending on the message ID given as $m, could return the following sample paths:
 *
 *		$m	intervalOf returns	$dbFile 		notes
 *
 *		1	'/0-149'		'posts/0-149'		legacy folder ('posts')
 *		149	'/0-149'		'posts/0-149'		legacy folder ('posts')
 *		150	'/150-300'		'posts/150-300' 	legacy folder ('posts')
 *		14999	'/14850-14999'		'posts/14850-14999'	legacy folder ('posts')
 *		15000	'1/15000-15149' 	'posts1/15000-15149'	division # 1 ('posts1')
 *		30000	'2/30000-30149' 	'posts2/30000-30149'	division # 2 ('posts2')
 *		etc...
 *
 *	then, each file holds a list of rows organized in an XML-like style: each row terminates
 *	with a newline code (\n) which is mandatory and applies to all files that may be handled
 *	through the Database Explorer; optionally, though, the final newline may be PRECEEDED by
 *	a less than sign ('<') when the file holds "proper" records divided into multiple fields
 *	and managed by 'set' and 'get'; having the trailing '<' always at the end of each record
 *	simply uniforms the behavior of functions that are supposed to match over field contents,
 *	given the lack of a "closing tag" for each field (which saves database space, each field
 *	except the last field being terminated by an opening tag of the next field); for example,
 *	a file called 'system/users' may be holding two newline-terminated "proper" records as:
 *
 *		<userid>2<username>Beta<pw>67890<
 *		<userid>1<username>Alpha<pw>12345<
 *
 *	note that they are normally stored in reverse chronological order, which ideally enables
 *	faster matching for more recent records, so you have progressively decreasing IDs there;
 *	subsequent functions of the engine care for checking integrity of proper records and for
 *	adding, removing, or updating fields of a record; by using 'set' and 'get', records in a
 *	given file must be selected by matching the name of a field ("key") to that of its exact
 *	content ("value"), e.g.
 *
 *		get ('system/users', 'userid>1', 'username');	// match key 'userid', value '1'
 *
 *	accessing the above example file, would return 'Alpha', being the value of the 'username'
 *	field held by the record where the condition 'userid>1' is matched: in such queries, the
 *	"greater than" sign safely separates the key field name from the value to match, without
 *	introducing any more meta-characters aside of the greater than/less than pair already in
 *	use as field delimiters; the above 'get' statement is equivalent to an SQL query like:
 *
 *		SELECT 'username' FROM 'system/users' WHERE 'userid=1'
 *
 *	however, do not assume you can interchange 'get' with an SQL query: the "equivalence" is
 *	only ideal; in practice, SQL would neither find any tables called 'system/users' nor any
 *	fields called 'userid' within that table; which brings the following legitimate question:
 *
 *		if SQL is already relational, why does this script implement its own engine?
 *
 *	there are multiple answers, concerning safety, performance and abstraction:
 *
 *	     1) queries by 'set' and 'get' use a simpler mark-up and are less prone to injections;
 *	     2) virtualFileWrite and virtualFileRead prevent SQL injections by saving base64 data;
 *	     3) splitting large archives in multiple tables ("divisions") may improve performance;
 *	     4) this engine's query language abstracts from the underlying SQL dialect; the actual
 *		version uses MySQL syntax and libraries to access the SQL server, but switching to
 *		another form of SQL database (or even another DB architecture, e.g. flat files) is
 *		possible by changing only the functions in the "virtual file access" section above
 *
 *	finally a couple considerations: when choosing the "granularity" of an archive, a number
 *	of records to be held in each interval, it should be a good idea to consider the average
 *	expected length of each entry, to pick the corresponding archive's granularity such that
 *	an interval file will averagely not exceed a certain size; for example, Postline expects
 *	messages to be averagely 1.5 kB each and allows 150 entries per file to obtain intervals
 *	of around 200 kB; the last consideration concerns "memory images" of proper records: the
 *	relevant 'set' and 'get' functions optionally allow storing or retrieving entire records
 *	to then manipulate their exact "image" in memory, which often allows speeding up changes
 *	to multiple fields of a same record (in particular, see 'fieldSet'); well, in such cases
 *	the image of a record is already terminated by the end of the string, and would not need
 *	the trailing "less than" sign: in memory images of records, the final "less than" can be
 *	witheld to simplify the record's string; upon storing the whole record back with a 'set'
 *	call, the trailing "less than" will be automatically inserted back in the record string;
 *	similarly, when reading entire proper records by calling 'get' or 'all', the terminators
 *	would be stripped from the end of each record's image; that having being said, including
 *	terminators in memory images fed to 'fieldSet', 'set' or 'asm' will be uninfluent; e.g.
 *
 *		$recordImage = get ('system/users', 'userid>1', wholeRecord);
 *
 *	will return $recordImage as:
 *
 *		<userid>1<username>Alpha<pw>12345
 *
 *	which lacks the final "less than", since functions like 'valueOf' or 'fieldSet' wouldn't
 *	need that to know where the last field ends (it ends where the string itself ends), i.e.
 *	the terminators are only necessary to simplify the matching of records concatenated in a
 *	whole file, while stripping them from single record images simplifies the layout of such
 *	images; at that point, you could alter the image without having to insert the terminator
 *	at its end, and still save the image back with, for example:
 *
 *		set ('system/users', 'userid>1', wholeRecord, $recordImage);
 *
 *	and expect 'set' to put the terminator back in place before saving back the record; also
 *	note that the same thing happens to records being built as images and inserted as wholly
 *	new records, e.g.
 *
 *		set ('system/users', newRecord, wholeRecord, '<userid>3<username>Gamma<pw>9966');
 *
 *	where the new record's layout still lacks the terminator and is, however, legitimate; to
 *	conclude (yes I've been quite verbose here), in the particular cases of 'all' and 'asm',
 *	notice that those functions includes an argument to determine their behavior: in effect,
 *	'all' and 'asm' can be used to explode ('all') then put back together ('asm') files made
 *	of either proper or improper records; consequentially they must be told how to treat the
 *	format of the given file: 'makeProper' will force 'all' to strip record terminators from
 *	the end of each newline-delimited record it reads and 'asm' to force back terminators in
 *	the saved file, while 'asIs' enables 'asm' and 'all' to treat files of improper records
 *
 */

function makeProperRecord ($record) {

	$bogusCall = (is_string ($record)) ? false : true;

	/*
	 *
	 *	ensures $record is properly terminated (has a trailing "less than" sign)
	 *	and checks for any invalid layouts of record fields (all fields properly
	 *	terminating, no characters outside fields); accepts a record missing the
	 *	trailing "less than", and in that case forces one in the returned record
	 *	string; will also make sure no newline codes occur in the string; issues
	 *	other than a missing final "less than" will raise an unrecoverable error
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	record is no string
		 *
		 */

		die (because ('makeProperRecord_type_mismatch'));

	}

	$l = strlen ($record);

	if ($l == 0) {

		/*
		 *
		 *	record is a void string: it's valid, but needs the trailing "less than"
		 *
		 */

		return (voidString . '<');

	}

	$initialField = ($record[0] == '<') ? true : false;

	if ($initialField == false) {

		/*
		 *
		 *	non-empty record does not begin with a field's key
		 *
		 */

		die (because ('missing_initial_key_in_record'));

	}

	$balance = 0;
	$closing = false;

	for ($i = 0; $i < $l; ++ $i) {

		$lt = ($record[$i] == '<') ? true : false;
		$gt = ($record[$i] == '>') ? true : false;
		$nl = ($record[$i] == "\n") ? true : false;

		if ($lt) {

			if ($i < $l - 1) {

				++ $balance;

			}

			else {

				$closing = true;

			}

		}

		else
		if ($gt) {

			-- $balance;

		}

		else
		if ($nl) {

			/*
			 *
			 *	record string includes a newline code
			 *
			 */

			die (because ('newline_in_record_image'));

		}

		else
		if (($balance < 0) || ($balance > 1)) {

			/*
			 *
			 *	record layout has invalid disposition of < key markers >,
			 *	such as a key nested inside another key ($balance > 1) or
			 *	closing "greater than" not matching a previous "less than"
			 *
			 */

			die (because ('invalid_record_layout'));

		}

	}

	if ($balance == 1) {

		/*
		 *
		 *	record string ends by a key that lacks its final "greater than"
		 *
		 */

		die (because ('truncated_key_in_record'));

	}

	return (($closing)

		? $record
		: $record . '<'

	);

}

function isInvalidKeyOrValue ($keyOrValue) {

	$bogusCall = (is_string ($keyOrValue)) ? false : true;

	/*
	 *
	 *	checks if $keyOrValue is invalid; to be valid:
	 *	must be string, hold no newlines, no "greater than" signs, no "less than" signs
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	entry is no string
		 *
		 */

		return (true);

	}

	return (preg_match ("/[\>\<\n]/", $keyOrValue) ? true : false);

}

function isInvalidQuery ($where) {

	$bogusCall = (is_string ($where)) ? false : true;

	/*
	 *
	 *	the following are safety checks on the validity of the syntax of the $where string
	 *	given to 'get' and 'set'; the function returns true if there's something wrong
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	$where is no string
		 *
		 */

		return (true);

	}

	$queryParts = wExplode ('>', $where);
	$exactParts = (count ($queryParts) == 2) ? true : false;

	if ($exactParts == false) {

		/*
		 *
		 *	$where is composed by more or less than 2 parts
		 *
		 */

		return (true);

	}

	list ($whereKey, $whereVal) = $queryParts;

	if (($whereKey == voidString) || ($whereVal == voidString)) {

		/*
		 *
		 *	either part is void
		 *
		 */

		return (true);

	}

	$invalidKey = (strpos ($whereKey, '<') === false) ? false : true;
	$invalidKey = (strpos ($whereKey, "\n") === false) ? $invalidKey : true;

	if ($invalidKey == true) {

		/*
		 *
		 *	the key part has invalid characters (less than, newline)
		 *
		 */

		return (true);

	}

	$invalidVal = (strpos ($whereVal, '<') === false) ? false : true;
	$invalidVal = (strpos ($whereVal, "\n") === false) ? $invalidVal : true;

	if ($invalidVal == true) {

		/*
		 *
		 *	the value part has invalid characters (less than, newline)
		 *
		 */

		return (true);

	}

	return (false);

}

function intervalOf ($id, $granularity) {

	$bogusCall = (is_int ($id)) ? false : true;
	$bogusCall = (is_int ($granularity)) ? $bogusCall : true;

	/*
	 *
	 *	used for an extremely important task: splitting possibly large archives in smaller
	 *	files, indexed by progressive record identificators, $id, in conjuction to another
	 *	argument, $granularity, which gives how many records are held together inside each
	 *	file; each such file is called an "interval" of the corresponding archive
	 *
	 *	this function returns a string that might be used as part of a file's name, in the
	 *	form 'x-y' where x is the ID of the first record stored in that file, and y is the
	 *	ID of the last record; although none of the extremes may be practically present in
	 *	that file, whatever is saved in that file is some record of given archive which ID
	 *	whose id falls into the [x-y] range; archives themselves are intended to be formed
	 *	by a set of virtual directories containing files whose names are generated by this
	 *	function; each directory being part of the same archive is called a "division" and
	 *	its path is obtained by prepending a progressive ID to the name string returned by
	 *	this function; each division (directory) ALWAYS holds 100 intervals (100 files)
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	ID or granularity is no integer
		 *
		 */

		die (because ('intervalOf_type_mismatch'));

	}

	if ($id < 0) {

		/*
		 *
		 *	ID is negative
		 *
		 */

		die (because ('intervalOf_negative_ID'));

	}

	if ($granularity <= 0) {

		/*
		 *
		 *	granularity is negative or zero
		 *
		 */

		die (because ('intervalOf_invalid_granularity'));

	}

	$base = $id - ($id % $granularity);
	$division = (int) ($id / (100 * $granularity));

	return (($division == 0)

		? sprintf ("/%d-%d", $base, $base + $granularity - 1)
		: sprintf ("%d/%d-%d", $division, $base, $base + $granularity - 1)

	);

}

function offsetOf ($data, $position) {

	$bogusCall = (is_string ($data)) ? false : true;
	$bogusCall = (is_int ($position)) ? $bogusCall : true;

	/*
	 *
	 *	returns the offset (within $data) of the first character of the line holding the
	 *	record that $position refers to, whereby $position is the offset within $data to
	 *	any character of that record
	 *
	 *	note:
	 *	this function is generally not called directly but it's a helper of 'set' and 'get'
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	data is no string or position is no integer
		 *
		 */

		die (because ('offsetOf_type_mismatch'));

	}

	if ($position < 0) {

		/*
		 *
		 *	position is negative
		 *
		 */

		die (because ('offsetOf_negative_position'));

	}

	while (($position > 0) && ($data[$position - 1] != "\n")) {

		-- $position;

	}

	return ($position);

}

function recordOf ($data, $position) {

	$bogusCall = (is_string ($data)) ? false : true;
	$bogusCall = (is_int ($position)) ? $bogusCall : true;

	/*
	 *
	 *	returns the whole line holding the record that $position refers to,
	 *	whereby $position is the offset within $data to any character of that record
	 *
	 *	note:
	 *	this function is generally not called directly but it's a helper of 'set' and 'get'
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	data is no string or position is no integer
		 *
		 */

		die (because ('recordOf_type_mismatch'));

	}

	if ($position < 0) {

		/*
		 *
		 *	position is negative
		 *
		 */

		die (because ('recordOf_negative_position'));

	}

	while (($position > 0) && ($data[$position - 1] != "\n")) {

		-- $position;

	}

	$l = 0;

	do {

		++ $l;

	} while (($data[$position + $l] != "\n") && ($data[$position + $l] != voidString));

	return (substr ($data, $position, $l));

}

function valueOf ($record, $key) {

	$bogusCall = (is_string ($record)) ? false : true;
	$bogusCall = (is_string ($key)) ? $bogusCall : true;

	/*
	 *
	 *	simply returns the value of field $key, extracting it from the given $record
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	record or key is no string
		 *
		 */

		return (voidString);

	}

	if ($key == voidString) {

		/*
		 *
		 *	key is empty
		 *
		 */

		die (because ('valueOf_void_key'));

	}

	if (isInvalidKeyOrValue ($key)) {

		/*
		 *
		 *	key is invalid
		 *
		 */

		die (because ('valueOf_invalid_key'));

	}

	return (strfromto ("$record<", "<$key>", '<'));

}

function fieldSet ($oldRecord, $key, $value) {

	$bogusCall = (is_string ($oldRecord)) ? false : true;
	$bogusCall = (is_string ($key)) ? $bogusCall : true;
	$bogusCall = (is_string ($value)) ? $bogusCall : true;

	/*
	 *
	 *	adds or updates the value of field $key in $oldRecord according to $value,
	 *	or deletes $key from $oldRecord:
	 *
	 *	- if $key doesn't occur in $old_record, adds '<$key>$value' to $oldRecord
	 *	- if $value is a void string but $key exists removes $key from $oldRecord
	 *	- in other cases, updates the value of field $key to $value in $oldRecord
	 *
	 *	the return value is the modified record ($newRecord), except if the value
	 *	given to a field that does not exist in $oldRecord is void, in which case
	 *	the return value is the given $oldRecord unchanged
	 *
	 *	note:
	 *	this function can be used to alter the memory image of a single record by
	 *	changing, adding or removing single fields, but that might only be useful
	 *	and convenient when you have two or more fields to manipulate in the same
	 *	record; much more often, single writes to database entries occur to alter
	 *	a single field in a random record, in which case 'set' might be preferred
	 *
	 *	example of convenient use (updating TWO fields in the same record):
	 *
	 *		$userRecord = get ('system/users', 'userid>1', wholeRecord);
	 *		$userRecord = fieldSet ($userRecord, 'username', 'Gamma');
	 *		$userRecord = fieldSet ($userRecord, 'pw', '9966');
	 *		set ('system/users', 'userid>1', wholeRecord, $userRecord);
	 *
	 *	which would be more complicated, but faster, than its simpler equivalent:
	 *
	 *		set ('system/users', 'userid>1', 'username', 'Gamma');
	 *		set ('system/users', 'userid>1', 'pw', '9966');
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	any of the arguments is no string
		 *
		 */

		die (because ('fieldSet_type_mismatch'));

	}

	if ($key == voidString) {

		/*
		 *
		 *	key is void
		 *
		 */

		die (because ('fieldSet_void_key'));

	}

	if (isInvalidKeyOrValue ($key)) {

		/*
		 *
		 *	key is invalid
		 *
		 */

		die (because ('fieldSet_invalid_key'));

	}

	if (isInvalidKeyOrValue ($value)) {

		/*
		 *
		 *	value is invalid
		 *
		 */

		die (because ('fieldSet_invalid_value'));

	}

	$nullValue = ($value == voidString) ? true : false;
	$keyMatch = (strpos ($oldRecord, "<$key>") === false) ? false : true;

	if ($keyMatch) {

		/*
		 *
		 *	key appears in record:
		 *	update or delete field in memory image of record
		 *
		 */

		$newRecord = ($nullValue)

			? strReplaceFromTo ("$oldRecord<", "<$key>", '<', '<')
			: strReplaceFromTo ("$oldRecord<", "<$key>", '<', "<$key>$value<");

	}

	else {

		/*
		 *
		 *	key doesn't appear in record:
		 *	if value is void, return $oldRecord unchanged, else add new field at end
		 *
		 */

		$newRecord = ($nullValue)

			? "$oldRecord<"
			: "$oldRecord<$key>$value<";

	}

	return (substr ($newRecord, 0, -1));

}

function set ($file, $where, $key, $value) {

	$bogusCall = (is_string ($file)) ? false : true;
	$bogusCall = (is_string ($where)) ? $bogusCall : true;
	$bogusCall = (is_string ($key)) ? $bogusCall : true;
	$bogusCall = (is_string ($value)) ? $bogusCall : true;

	/*
	 *
	 *	sets the value of a field, directly into database file specified by $file:
	 *	$where is in the form 'key>value' whereby the key may occur anywhere in a
	 *	record and which key is assumed to be a primary key (uniquely identifying
	 *	only one record)
	 *
	 *    - if $where isn't matched among records of $file, or if $where is empty:
	 *
	 *	      - if both $key and $value are empty (unmatched record deletion) does nothing;
	 *	      - if both $key and $value are not empty, adds a record with $key and $value;
	 *	      - if $key is empty and $value is not empty, adds $value as a full new record
	 *
	 *    - if $where is matched, updates the matching record according to the following rules:
	 *
	 *	      - if both $key and $value are not empty, inserts/updates field matching $key;
	 *	      - if $key is not empty, and $value is empty, deletes the field matching $key;
	 *	      - if $key is empty, and $value is not empty, sets THE WHOLE RECORD to $value;
	 *	      - if both $key and $value are empty, deletes THE WHOLE MATCHING RECORD
	 *
	 *	following examples use equates defined as empty strings, but conveniently
	 *	declared as 'newRecord', 'wholeRecord', 'deleteField' and 'deleteRecord',
	 *	to improve the readability of the relative queries:
	 *
	 *		set ('pets/cats', newRecord, 'name', 'Lucky');
	 *		set ('pets/cats', newRecord, wholeRecord, '<name>Grain');
	 *
	 *	which are the two ways to add similar one-field records, albeit the first
	 *	instance only works for adding a new record holding the sole 'name' field,
	 *	whereas the second call is more generic and could concatenate more fields
	 *	to make up the exact layout of the new record; moreover, the second call
	 *	can be used to replace the entire content of a given record when a record
	 *	already exists; this is accomplished by adding the $where argument to it:
	 *
	 *		set ('pets/cats', 'name>Grain', wholeRecord, '<name>Grain<color>blue');
	 *
	 *	which adds a new record for 'Grain' only if a record already having that
	 *	name doesn't exist yet, otherwise replaces the existing record; specific
	 *	and extra fields could then be added or modified as follows:
	 *
	 *		set ('pets/cats', 'name>Lucky', 'age', '1');	// adding a new field
	 *		set ('pets/cats', 'name>Lucky', 'age', '2');	// changing Lucky's age
	 *
	 *	and subsequently, removing single fields (e.g. Lucky's age) is as follows:
	 *
	 *		set ('pets/cats', 'name>Lucky', 'age', deleteField);
	 *
	 *	with a null key (or the 'wholeRecord' alias) you may handle whole records;
	 *	below, Lucky's record is first completely overwritten, then deleted:
	 *
	 *		set ('pets/cats', 'name>Lucky', wholeRecord, '<name>Martin');
	 *		set ('pets/cats', 'name>Martin', wholeRecord, deleteRecord);
	 *
	 *	notes:
	 *	if using PHP variables in the $where string, remember to put doublequotes
	 *	around the string, not single quotes (e.g. "message_id>{$m}"); for making
	 *	multiple changes to a same record, also, see notes at 'fieldSet'
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	any of the arguments is no string
		 *
		 */

		die (because ('set_type_mismatch'));

	}

	$fileImage = readFrom ($file);
	$emptyFile = (strlen ($fileImage) == 0) ? true : false;
	$validFile = (($emptyFile) || (substr ($fileImage, -1, 1) == "\n")) ? true : false;

	if ($validFile == false) {

		/*
		 *
		 *	the file isn't empty but holds data not being newline-terminated
		 *
		 */

		die (because ('set_corrupted_file'));

	}

	$nullWhere = ($where == voidString) ? true : false;
	$nullKey = ($key == voidString) ? true : false;
	$nullValue = ($value == voidString) ? true : false;

	if ($nullWhere) {

		/*
		 *
		 *	$where not given, so there's nothing to match
		 *
		 */

		$matchPosition = false;

	}

	else {

		/*
		 *
		 *	$where is given, so search the whole file for a match
		 *
		 */

		if (isInvalidQuery ($where)) {

			die (because ('set_invalid_query'));

		}

		list ($whereKey, $whereVal) = explode ('>', $where);

		$matchString = "<$whereKey>$whereVal<"; 		// build string to locate
		$matchPosition = strpos ($fileImage, $matchString);	// locate matching record

	} // $where was given

	if ($matchPosition === false) {

		/*
		 *
		 *	$where not matched in any records of target file, or not given
		 *
		 */

		if ($nullValue == false) {

			/*
			 *
			 *	$value is not empty, so there's something to do
			 *
			 */

			if ($nullKey) {

				/*
				 *
				 *	$key is empty, so add a new full record (on top),
				 *	taking its entire layout from $value
				 *
				 *	      - new records are added on top of database files
				 *		because in many cases, most recent records are
				 *		accessed more frequently than "deposited" ones
				 *		stored farther from the beginning of the file,
				 *		and string matches would be faster this way
				 *
				 */

				$value = makeProperRecord ($value);

				writeTo ($file, "$value\n" . $fileImage);

			}

			else {

				/*
				 *
				 *	$key is not empty, so add a new record,
				 *	as a simple '<key>value' pair
				 *
				 */

				if (isInvalidKeyOrValue ($key)) {

					/*
					 *
					 *	but $key is invalid
					 *
					 */

					die (because ('set_invalid_key'));

				}

				if (isInvalidKeyOrValue ($value)) {

					/*
					 *
					 *	but $value is invalid
					 *
					 */

					die (because ('set_invalid_value'));

				}

				writeTo ($file, "<$key>$value<\n" . $fileImage);

			}

		} // value not empty

	} // $where not matched

	else {

		/*
		 *
		 *	$where was matched at some point of the file:
		 *
		 *	note that it implicitly locates the first match, and will expect no further
		 *	matches i.e. assumes $whereKey to be a unique identificator (a primary key)
		 *	for the matching record; however, if more than one record were present with
		 *	the same $key and $value pair, the matching record will be the last one, in
		 *	chronological order, that had been inserted in the file
		 *
		 */

		$matchingRecord = recordOf ($fileImage, $matchPosition);

		/*
		 *
		 *	the following are safety checks on the integrity of the matched record,
		 *	which expect the final "less than" of each proper record to occur there
		 *	before sending the whole record's layout to 'makeProperRecord' for more
		 *	in-depth checks
		 *
		 */

		$validRecord = (strlen ($matchingRecord) == 0) ? false : true;
		$validRecord = (substr ($matchingRecord, -1, 1) == '<') ? $validRecord : false;

		if ($validRecord == false) {

			die (because ('set_corrupted_record'));

		}

		makeProperRecord ($matchingRecord);

		/*
		 *
		 *	determine matching record position,
		 *	and use it to insulate data preceeding and following the matching record
		 *
		 */

		$matchPosition = offsetOf ($fileImage, $matchPosition);
		$dataBeforeRecord = substr ($fileImage, 0, $matchPosition);
		$dataPastRecord = substr ($fileImage, $matchPosition + strlen ($matchingRecord) + 1);

		if ($nullKey) {

			/*
			 *
			 *	$key is empty
			 *
			 */

			if ($nullValue) {

				/*
				 *
				 *	$value is also empty:
				 *	delete the whole record, keeping what preceeds and follows
				 *
				 */

				writeTo ($file, $dataBeforeRecord . $dataPastRecord);

			}

			else {

				/*
				 *
				 *	$value is not empty:
				 *	replace the whole matching record with $value
				 *
				 */

				$value = makeProperRecord ($value);

				writeTo ($file, $dataBeforeRecord . "$value\n" . $dataPastRecord);

			}

		} // empty $key

		else {

			/*
			 *
			 *	$key is not empty, so change the corresponding field to $value:
			 *	if $value is empty, 'fieldSet' deletes the field from the record
			 *
			 */

			if (isInvalidKeyOrValue ($key)) {

				/*
				 *
				 *	but $key is invalid
				 *
				 */

				die (because ('set_invalid_key'));

			}

			if (isInvalidKeyOrValue ($value)) {

				/*
				 *
				 *	but $value is invalid
				 *
				 */

				die (because ('set_invalid_value'));

			}

			/*
			 *
			 *	note: 'fieldSet' normally accepts the clean image of a record,
			 *	deprived of the terminator; in this case, it's called to work
			 *	on the physical image of the record, including the terminator,
			 *	meaning the terminator of $matchingRecord needs to be removed,
			 *	then appended back to the resulting $newRecord, equalizing its
			 *	saved layout to that of other cases (which have the terminator)
			 *
			 */

			$newRecord = fieldSet (substr ($matchingRecord, 0, -1), $key, $value) . '<';

			/*
			 *
			 *	save changed file back to database
			 *
			 */

			writeTo ($file, $dataBeforeRecord . "$newRecord\n" . $dataPastRecord);

		} // given $key and $value

	} // $where matched

}

function get ($file, $where, $key) {

	$bogusCall = (is_string ($file)) ? false : true;
	$bogusCall = (is_string ($where)) ? $bogusCall : true;
	$bogusCall = (is_string ($key)) ? $bogusCall : true;

	/*
	 *
	 *	gets the value of a field from database file specified by $file: $where is in the
	 *	form 'key>value', whereby the key may occur anywhere in a record, and that key is
	 *	assumed to be a primary key (uniquely identifying only one record)
	 *
	 *    - if $where isn't matched, returns a void string;
	 *    - if $where is matched and $key is not empty, returns value of $key from the match;
	 *    - if $where is matched, but $key is empty, returns the whole matching record
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	any of the arguments is no string
		 *
		 */

		die (because ('get_type_mismatch'));

	}

	if (isInvalidQuery ($where)) {

		/*
		 *
		 *	the $where string is invalid
		 *
		 */

		die (because ('get_invalid_query'));

	}

	$fileImage = readFrom ($file);
	$emptyFile = (strlen ($fileImage) == 0) ? true : false;
	$validFile = (($emptyFile) || (substr ($fileImage, -1, 1) == "\n")) ? true : false;

	if ($validFile == false) {

		/*
		 *
		 *	the file isn't empty but holds data not being newline-terminated
		 *
		 */

		die (because ('get_corrupted_file'));

	}

	list ($whereKey, $whereVal) = explode ('>', $where);

	$matchString = "<$whereKey>$whereVal<"; 		// build string to locate
	$matchPosition = strpos ($fileImage, $matchString);	// locate matching record

	if ($matchPosition === false) {

		/*
		 *
		 *	no record matches $where condition
		 *
		 */

		return (voidString);

	}

	else {

		/*
		 *
		 *	one record matches $where:
		 *
		 *	note that it implicitly locates the first match, and will expect no further
		 *	matches i.e. assumes $whereKey to be a unique identificator (a primary key)
		 *	for the matching record; however, if more than one record were present with
		 *	the same $key and $value pair, the matching record will be the last one, in
		 *	chronological order, that had been inserted in the file
		 *
		 */

		$matchingRecord = recordOf ($fileImage, $matchPosition);

		/*
		 *
		 *	the following are safety checks on the integrity of the matched record,
		 *	which expect the final "less than" of each proper record to occur there
		 *	before sending the whole record's layout to 'makeProperRecord' for more
		 *	in-depth checks
		 *
		 */

		$validRecord = (strlen ($matchingRecord) == 0) ? false : true;
		$validRecord = (substr ($matchingRecord, -1, 1) == '<') ? $validRecord : false;

		if ($validRecord == false) {

			die (because ('get_corrupted_record'));

		}

		makeProperRecord ($matchingRecord);

		/*
		 *
		 *	return the desired field, or the whole record, depending on $key
		 *
		 */

		$nullKey = ($key == voidString) ? true : false;

		if ($nullKey) {

			/*
			 *
			 *	key is empty, so return the whole record image (without terminator)
			 *
			 */

			return (rtrim ($matchingRecord, '<'));

		}

		else {

			/*
			 *
			 *	key is not empty, so return corresponding field's value
			 *
			 */

			if (isInvalidKeyOrValue ($key)) {

				/*
				 *
				 *	but $key is invalid
				 *
				 */

				die (because ('get_invalid_key'));

			}

			return (valueOf ($matchingRecord, $key));

		}

	} // $where matched

}

function all ($file, $behavior) {

	$bogusCall = (is_string ($file)) ? false : true;
	$bogusCall = (($behavior === makeProper) || ($behavior === asIs)) ? $bogusCall : true;

	/*
	 *
	 *	returns an array containing all records from given database file;
	 *	returns a void array if the file doesn't exist, or if it's empty
	 *
	 *	note:
	 *	this function can read files made of "improper" records not being
	 *	terminated by the expected "less than" sign; in any case, records
	 *	are returned without the trailing "less than"
	 *
	 */

	if ($bogusCall) {

		/*
		 *
		 *	file name is no string, or behavior is not among allowed values
		 *
		 */

		die (because ('all_type_mismatch'));

	}

	$fileImage = readFrom ($file);
	$emptyFile = (strlen ($fileImage) == 0) ? true : false;
	$validFile = (($emptyFile) || (substr ($fileImage, -1, 1) == "\n")) ? true : false;

	if ($validFile == false) {

		/*
		 *
		 *	the file isn't empty but holds data not being newline-terminated
		 *
		 */

		die (because ('all_corrupted_file'));

	}

	$records = ($fileImage == voidString) ? array () : explode ("\n", rtrim ($fileImage));

	if ($behavior == asIs) {

		return ($records);

	}

	else {

		$entries = array ();

		foreach ($records as $r) {

			$entries[] = rtrim ($r, '<');

		}

		return ($entries);

	}

}

function asm ($file, $records, $behavior) {

	$bogusCall = (is_string ($file)) ? false : true;
	$bogusCall = (is_array ($records)) ? $bogusCall : true;
	$bogusCall = (($behavior === makeProper) || ($behavior === asIs)) ? $bogusCall : true;

	/*
	 *
	 *	assembles records from input array $records, and re-writes the whole $file
	 *
	 *	note:
	 *	this function accepts an array of records being either "proper"
	 *	or "improper", i.e. terminated by any occurrences of newline or
	 *	"less than" signs but emits data made of proper records only if
	 *	the entry $behavior is 'makeProper'
	 *
	 */

	if ($bogusCall == false) {

		foreach ($records as $r) {

			$bogusCall = (is_string ($r)) ? $bogusCall : true;

			if ($bogusCall) {

				break;

			}

		}

	}

	if ($bogusCall) {

		/*
		 *
		 *	file name is no string, behavior is not among allowed values,
		 *	or records array is no array of strings
		 *
		 */

		die (because ('asm_type_mismatch'));

	}

	$fileImage = voidString;

	foreach ($records as $r) {

		$fileImage .= ($behavior == asIs)

			? "$r\n"
			: makeProperRecord ($r) . "\n";

	}

	writeTo ($file, $fileImage);

}

define ("$productName/widgets/dbengine.php", true); } ?>
