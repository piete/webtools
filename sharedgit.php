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

//$subdirserver = explode(".",$_SERVER['SERVER_NAME']);

if ($_SERVER['SERVER_NAME'] == "pesartain.com") {
	define("_GITLIST","sharedgit.list");
	define("_STYPE","POST");
	define("_GIT_BIN","/home/pesar2/git/bin/git --no-pager ");
} else {
	define("_GITLIST","sharedgit.list");
	define("_STYPE","GET");
	define("_GIT_BIN","git --no-pager ");
}	
/******************
 * Functions
 ******************/
 
/*
 * Parses the file in _GITLIST and returns array( $directory , $sources )
 * where $directory is a numerically keyed array of git directories 
 * locally and $sources is an array of sources, keyed the same as $directory.
 * Each $source[] item is itself an array of all sources keyed to that directory
 * in a further array containing the branch and the URL.
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
		$tmp = null;
		foreach($source as $item) {
			$tmp[]=explode("=",$item);
		}
		$sources[$key] = $tmp;
	}
	
	return array($directory,$sources);
}

/*
 * Accept a directory string and an array of arrays ($sources) containing 
 * the URL and the branch to pull from.
 *
 * This function rewrites the entire _GITLIST file
 */
function save_list($dir_str, $sources, $id) {

	// Build a complete line to write back

	$write = false;
	if ($dir_str != '') {
		$s_str = '{';
		foreach($sources as $source) {
			$s_str .= "${source[0]}=${source[1]},";
		}
		$s_str = rtrim($s_str,",")."}";


		$saved_line = $dir_str." => ".$s_str;
		$write = true;
	}

	$file = file(_GITLIST);
	$handle = fopen(_GITLIST,'w');

	$i = 0;	
	foreach($file as $line) {
		if ($id == $i++) {
			if ($write) {
				fwrite($handle,$saved_line."\n");
				$write = false;
			}
		} else {
			fwrite($handle,$line);
		}
	}
	
	if ($write) {
		fwrite($handle,$saved_line."\n");
	}
	
	fclose($handle);
}

/*
 * Takes a single $directory string, and an array of $sources and builds 
 * a html view.
 */
function print_dir($directory, $sources, $id) {	
	
	$str = "
	
	<form method='"._STYPE."' action='".$_SERVER['PHP_SELF']."'>
	<input type='hidden' name='id' value='$id'></input>

	<table>
	<tr>
		<td colspan=2>	
		<input type='text' name='dir' value='".htmlspecialchars($directory)."' size='40'></input>
		</td>
		<td>
			<select name='action'>
				<option value='0' selected>Save</option>
				<option value='1'>Pull</option>
				<option value='2'>Initialise</option>
				<option value='3'>Delete</option>
				<option value='4'>Show log</option>
			</select>
			<input type='submit' name='bsubmit' value='Run'></input>
		</td>
	</tr>";

foreach ($sources as $source) {
	$str .= "
	<tr><td>
	<input type='text' name='branch[]' value='${source[0]}' size='10'></input>
	</td><td>
	<input type='text' name='url[]' value='${source[1]}' size='26'></input>
	</td>
	<td></td></tr>";
}

	$str .= "
	<tr><td>
	<input type='text' name='branch[]' value='' size='10'></input>
	</td><td>
	<input type='text' name='url[]' value='' size='26'></input>
	</td>
	<td></td></tr>		
	</table>
	
	</form>";

	return $str;
}

/******************
 * Git functions
 ******************/

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

function git_pull($directory,$sources) {

	$fulldir = $directory;

	$d = explode('/',$directory);
	$x = 0;
	$directory = '';
	for ($x = 1; $x < count($d)-1; $x++) {
		$directory .= '/'.$d[$x];
	}

	foreach($sources as $source) {

		if (file_exists($fulldir."/.git")) {
			chdir($fulldir);
			$cmd = "GIT_SSL_NO_VERIFY=1 "._GIT_BIN." pull ".$source[1]." ".$source[0].' 2>&1';
		} else {
			chdir($directory);
			$cmd = "GIT_SSL_NO_VERIFY=1 "._GIT_BIN." clone ".$source[1].' 2>&1';
		}

		$str .= $cmd."<br>";

		exec($cmd, $output, $retval);

		$str .= "Returned: ".$retval."<br>";
		foreach($output as $line) {
			$str .= $line."<br>";
		}
	}
	return $str;

}

/*
 * Run `git log` on a all git repos in the array $directory and 
 * return the results.
 */
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

/*
 * Run `git log` on a given directory and return the results.
 */
function git_log($dir) {
	chdir($dir);
	$cmd = _GIT_BIN." log 2>\&1";
	$str .= $cmd."<br>";

	exec($cmd, $output, $retval);
	foreach($output as $line) {
		$str .= $line."<br>";
	}
	return $str;	
}

/******************
 * Main
 ******************/

list($directory,$sources) = parse_list();

if (isset(${"_"._STYPE}['action'])) {
	// Handle post-action

	if (${"_"._STYPE}['bsubmit'] == "Run") {
		switch(${"_"._STYPE}['action']) {
			case 0:
				// Save changes
				
				foreach(${"_"._STYPE}['branch'] as $key => $branch) {
					$url = ${"_"._STYPE}['url'][$key];
					if (($url != '') && ($branch != '')) {
						$source[] = array($branch,$url);
					}
				}
				// Save the changes to file
				save_list(${"_"._STYPE}['dir'],$source,${"_"._STYPE}['id']);
				// Then reload the file to propogate the changes
				list($directory,$sources) = parse_list();
			
			break;
			case 1:
				// Pull from sources
				//$rslt = git_pull_all($directory,$sources);
				$id = ${"_"._STYPE}['id'];
				$rslt = git_pull($directory[$id],$sources[$id]);
			break;
			case 2:
				// Init new repo
			break;
			case 3:
				// Delete this repo
			break;
			case 4:
				// Git log
				//$rslt = git_log_all($directory);
				$rslt = git_log(${"_"._STYPE}['dir']);
			break;
		}
	} else {
		echo "";
	}
}

echo "<html><head></head><body>";

/*
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
*/
echo "<div id='output'><pre>".$rslt."</pre></div>";

/*
print_r($directory);
echo "<br><br>";
print_r($sources);
echo "<br><br>";
*/

foreach($directory as $key => $dir) {
	echo print_dir($dir,$sources[$key],$key);
	
	/*
	echo $dir;
	echo "<br>";
	foreach($sources[$key] as $source) {
		echo $source[0]." = ".$source[1];
		echo "<br>";
	}
//	print_r($sources[$key]);

	*/
	echo "<br><br>";
	
}
	echo print_dir('',array(),$key+1);


echo "</body></html>";

?>
