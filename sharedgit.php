<?php

/**
 * Functions:
 *  - Parse the sharedgit.list into local directory & pull sources
 *  - Display all directories under git control
 *  - Init a new git controlled directory
 *  - Update directory from pull source
 *
 * List format, per line:
 *
 * /local/path/to/repo => {branch=URL,branch=URL..branch=URL}
 *
 */

/******************
 * Settings
 ******************/

define("_GITLIST","sharedgit.list");
define("_STYPE","GET");
define("_GIT_BIN","git --no-pager ");

/******************
 * Functions
 ******************/
 
/*
 * Parses the file in _GITLIST and returns array( $directory , $sources )
 * where $directory is a numerically keyed array of git directories 
 * locally and $sources is an array of sources, keyed the same as $directory.
 * Each $source[] item is itself an array of all sources keyed to that directory in
 * a further array containing the branch and the URL.
 *
 * The BRANCH is the branch to pull from on the remote URL.
 */
function parse_list() {
	$file = file(_GITLIST);
	
	foreach($file as $line) {
		//echo $line;
		
		// Perl-style regex:  /regex/
		preg_match("/(.*) => {(.*)}/",$line,$match);
		// First part is the directory
		$directory[] = $match[1];
		// Second part is a comma-separated list of branch=source pairs
		$sources[] = explode(",",$match[2]);
	}
	
	foreach($sources as $key => $source) {
		foreach($source as $item) {
			$tmp[]=explode("=",$item);
		}
		$sources[$key] = $tmp;
	}
	
	return array($directory,$sources);
}

/*
 * Takes a local directory as a string, a \n-separated string of sources, and
 * a line number and saves the values back to the file specified in _GITLIST.
 */
function save_list($dir_str, $source_str, $id) {

	// Build a complete line to write back
	$line = $dir_str." => {".rtrim($source_str,"\x00..\x1F,")."}";

	$file = file(_GITLIST);
	
	foreach($file as $line) {
		//echo $line;
		
		// Perl-style regex:  /regex/
		preg_match("/(.*) => {(.*)}/",$line,$match);
		// First part is the directory
		$directory[] = $match[1];
		// Second part is a comma-separated list of sources
		$sources[] = explode(",",$match[2]);
	}
	

}

function save_all($directories, $sources) {

}

/*
 * Takes a single $directory string, and an array of $sources and builds 
 * a html view.
 */
function print_dir($directory, $sources, $id) {	

	$sls = "";
	foreach ($sources as $source) {
		$sls .= $source.',&#012;';
	}
	
	$str = "
	<form method='"._STYPE."' action='".$_SERVER['PHP_SELF']."'>
	
	<input type='hidden' name='id' value='$id'></input>
	
	<table>
	<tr>
		<td>	
		<input type='text' name='dir' value='".htmlspecialchars($directory)."' size='40'></input>
		</td>
		<td rowspan=3>
			<textarea name='sources' cols='40' rows='5'>".$sls."</textarea></td>
		<td></td>
	</tr>
	<tr>
		<td>
		
		<select name='action'>
			<option value='0' selected>Save</option>
			<option value='1'>Pull</option>
			<option value='2'>Initialise</option>
			<option value='3'>Delete</option>
		</select
		<input type='submit' name='bsubmit' value='Run'></input>
		
				</td>
		<td></td>
	</tr>
	<tr>
		<td></td>
		<td></td>
	</tr>

	</form>";

	return $str;
}

function git_pull_all($directory,$sources) {
	foreach ($directory as $key => $ls) {
		chdir($ls);
		foreach($sources[$key] as $source) {

			if (file_exists($ls."/.git")) {
				$cmd = _GIT_BIN." pull ".$source[1]." ".$source[0].' 2>&1';
			} else {
				$cmd = _GIT_BIN." clone ".$source[1]." ".$source[0].' 2>&1';
			}

			$str .= $cmd."<br>";

			exec($cmd, $output, $retval);

			$str .= "Returned: ".$retval."<br>";
			foreach($output as $line) {
				$str .= $line."<br>";
			}
		}
	}
	return $str;
}

function git_log_all($directory) {
	foreach ($directory as $key => $ls) {
		chdir($ls);
		$cmd = _GIT_BIN." log 2>\&1";
		$str .= $cmd."<br>";
//		echo $cmd."<br>";

		exec($cmd, $output, $retval);
		foreach($output as $line) {
			$str .= $line."<br>";
//			echo $line."<br>";
		}
	}
	return $str;
}

/******************
 * Main
 ******************/

list($directory,$sources) = parse_list();

if (isset(${"_"._STYPE}['action'])) {
	// Handle post-action

	switch(${"_"._STYPE}['action']) {
		case 0:
			// Save changes
			save_list(${"_"._STYPE}['dir'],${"_"._STYPE}['sources'],${"_"._STYPE}['id']);
		break;
		case 1:
			// Pull from sources
			$rslt = git_pull_all($directory,$sources);
		break;
		case 2:
			// Init new repo
		break;
		case 3:
			// Delete this repo
		break;
		case 4:
			// Git log
			$rslt = git_log_all($directory);
		break;
	}

}

echo "<html><head></head><body>";

echo "<form method='"._STYPE."' action='".$_SERVER['PHP_SELF']."'>
	  <input type='hidden' name='id' value='$id'></input>
	  <select name='action'>
			<option value='0' selected>Save</option>
			<option value='1'>Pull</option>
			<option value='2'>Initialise</option>
			<option value='3'>Delete</option>
			<option value='4'>Show log</option>
		</select
		<input type='submit' name='bsubmit' value='Run'></input>
	  </form>";


echo "<div id='output'>$rslt</div>";

echo "</body></html>";

?>
