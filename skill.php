<?php

exec("skill ping",$output,$retval);		

foreach($output as $line) {
			$str .= $line."<br>";
//			echo $line."<br>";
}

?>
