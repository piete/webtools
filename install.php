<?php 
/*  include ("../header.php"); */

$on = 0;

if ($on) die("Currently disabled.");

$uname="";
$upasswd="";
$udb="";

	$db = mysql_connect("localhost",$uname,$upasswd);

function testdb($db,$udb)
{
// The user & database should already have been created
	if (!mysql_select_db($udb,$db)) {
		printf("Connection failed: ".mysql_error($db)."<br>");
	} else {
		printf("Hua! Everything worked.<br><br><a href='?typ=forms'>Return</a>");
	}
		
}

function install($db,$udb,$fname)
{
mysql_select_db($udb,$db);

// Load the file into a variable, it gets loaded as an array
//$mytxt = file(dirname(__FILE__) . '/' . 'sqlsetup/setupWSE' . '.sql');
//$mytxt = file(dirname(__FILE__) . '/' . $fname . '.sql');
$mytxt = file(dirname(__FILE__) . '/' . $fname);
$current_statement = "";

// Loop through the array, reassemble the statements
	foreach ($mytxt as $count => $line)
	{

		// Remove the -- comments
		if (substr(trim($line), 0, 2) != '#')
			$current_statement .= "\n" . rtrim($line);

		// This marks the end of an SQL statement
		if ((preg_match('~;[\s]*$~s', $line)) == 1) {

		if ($_GET["opt"] != "supress") {
			printf("Executing: <b>".$current_statement."</b><br>");
		} else {
			printf("Executing: <b>".htmlspecialchars($current_statement)."</b><br>");
		}
				
			// Attempt to execute the statement
			if (!mysql_query($current_statement))
				printf("Error: ".mysql_error($db)."<br>");
				
			// Reset the statement to blank, to build the next one.
			$current_statement = "";
			
				printf("<br>");
		}
	}
	
	printf("Installation finished.<br><a href='?typ=forms'>Return</a>");
}

function runsql($db,$udb,$sql) {
	mysql_select_db($udb,$db);
	
	$rslt = mysql_query($sql);

	if (!$rslt) {
		printf("Error: ".mysql_error($db)."<br>");
	} else {
		
		if (mysql_num_rows($rslt) > 0) {
			
			echo("<table>");

/*			for ($x = 0; $x < count(mysql_fetch_row($rslt)); $x++) {
				echo("<th>Row ".$x."</th>");
			}
			
			echo("</tr>");
*/
			while ($myrow = mysql_fetch_row($rslt)) {
			
			echo("<tr>");
				for ($x = 0; $x < count($myrow); $x++) {
					echo("<td>".$myrow[$x]."</td>");
				}
			echo("</tr>");
			}
		echo("</table>");
		} else {
			printf($sql);		
		}
	}
	echo("<br><a href='?typ=forms'>Return</a>");
}

if (isset($_GET['typ'])) {
	$typ = $_GET['typ'];
}else{
	$typ = "forms";
}

switch ($typ) {
case "forms":

?>
<style>
h4 {margin-bottom: 3px; background-color: #99ddff; padding-left: 5px;}
table {border: 1px solid gray;}
th {background-color: #cceeff;}
.odd {background-color: #ddeeff;}
</style>

<h4>Generate Password</h4>
<form action="?typ=password" method="post">
<input size=50 type=text name="password"><input type=submit value="Get sha1 hash">
</form>

<h4>Database test utilities</h4>
<a href="?typ=test">Test database connection</a><br><br>

<a href="?typ=delall">Clear all tables</a><br><br>

<a href="?typ=iall">Install all SQL</a><br><br>

<a href="?typ=reset">Reset (clear / install all)</a><br>

<h4>Table instantiation</h4>
<table width=450>
<tr>
	<th>SQL file</th>
	<th>Install</th>
	<th>Delete</th>
</tr>
<?php
	$d = dir(".");
	$oddline = false;
	while (false !== ($entry = $d->read())) {
		if ($entry!="." && $entry!=".." && $entry!="null.sql"){
		
			$farray = explode(".",$entry);
			$issql = false;
			$flen = count($farray);
			if ($farray[$flen-1] == "sql") $issql = true;
			
			//echo $farray[$flen-1];
			
			if ($issql) {
			echo '<tr>';
			echo '<td'.($oddline?' class="odd"':'').'>'.$entry.'</td>';
			echo '<td'.($oddline?' class="odd"':'').'><a href="?typ=install&opt=supress&file='.$entry.'">Install '.$farray[0].'</a></td>';
			echo '<td'.($oddline?' class="odd"':'').'><a href="?typ=delete&opt=supress&file='.$entry.'">Delete '.$farray[0].'</a></td>';
			echo '</tr>';

			$oddline = !$oddline;
			}
		}
	}
$d->close();

?>
</table>
<br />
<a href="?typ=install&opt=supress&file=null.sql">Install null.sql</a>
<br />

<h4>Current tables</h4>

<?php

runsql($db,$udb,"SHOW TABLES");

?>
<br /><br />
<form action="?typ=execute" method="post">
<input size=50 type=text name="query"><input type=submit>
</form>

<?php

	break;

case "test":
	testdb($db,$udb);
	break;
case "install":
	install($db,$udb,$_GET["file"]);
	break;
case "delete";
	
	$farray = explode(".",$_GET["file"]);
	runsql($db,$udb,"DROP TABLE ".$farray[0]);
	break;	
case "iall":

	$d = dir(".");
	while (false !== ($entry = $d->read())) {
		if ($entry!="." && $entry!=".." && $entry!="null.sql"){
		
			$farray = explode(".",$entry);
			$issql = false;
			$flen = count($farray);
			if ($farray[$flen-1] == "sql") $issql = true;
			
			//echo $farray[$flen-1];
			
			if ($issql) {
				install($db,$udb,$entry);
			}
		}
	}
$d->close();

	break;
case "delall":

	$d = dir(".");
	while (false !== ($entry = $d->read())) {
		if ($entry!="." && $entry!=".." && $entry!="null.sql"){
		
			$farray = explode(".",$entry);
			$issql = false;
			$flen = count($farray);
			if ($farray[$flen-1] == "sql") $issql = true;
			
			//echo $farray[$flen-1];
			
			if ($issql) {
				runsql($db,$udb,"DROP TABLE ".$farray[0]);
			}
		}
	}
$d->close();

	break;
case "reset":

	$d = dir(".");
	while (false !== ($entry = $d->read())) {
		if ($entry!="." && $entry!=".." && $entry!="null.sql"){
		
			$farray = explode(".",$entry);
			$issql = false;
			$flen = count($farray);
			if ($farray[$flen-1] == "sql") $issql = true;
			
			//echo $farray[$flen-1];
			
			if ($issql) {
				runsql($db,$udb,"DROP TABLE ".$farray[0]);
				install($db,$udb,$entry);
			}
		}
	}
$d->close();

	break;
	case "execute":
		runsql($db,$udb,$_POST["query"]);
	break;

	case "password":
		$passwordhash = sha1($_GET['password']);
		printf($_GET['password']." hashes to: ".$passwordhash);
	break;

}


/*  include ("../footer.php"); */
?>
