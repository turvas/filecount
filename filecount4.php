<?php
// GPL by kalev, turvas@gmail.com
// creates URL link based on current location of this script and added newpath = ./somedir
function makeLink($newpath){
	$uri = $_SERVER["REQUEST_URI"];			// /somedir/filecount4.php?path=.&depth=1 
	$filename = $_SERVER["PHP_SELF"]; 		// /somedir/filecount4.php
	$uri2 = $filename."?path=".$newpath;	// 	
	$depthstart = strpos($uri,"&depth=");	// &depth=  -> or %26depth%3D
	if($depthstart === FALSE){				// if no depth in use
		$uri2 .= "&depth=1";				// assume 1
	}
	else{
		$uri2 .= substr($uri, $depthstart);	// add current
	}		
//	echo "uri2:". $uri2."<br>"; 
	$link = "<a href=\"$uri2\">$newpath</a>";
	return $link;
}
// maxprint: how deep subdirs to print
define("MAXPRINT", 2);
// returns all recusrisvley below path and 
// prints subdirectory URL-s (dependent of $maxdepth >0) to stdout
function getFileCount($path, $maxdepth = MAXPRINT) {
    $count = 0;
    $ignore = array('.','..','cgi-bin','.DS_Store');
    $files = scandir($path);
    foreach($files as $t) {
        if(in_array($t, $ignore)) continue;
        $fullpath = rtrim($path, '/') . '/' . $t;
        if ( is_dir($fullpath) && !is_link($fullpath)) {		// only directories, which are not links
        	$subcount = getFileCount($fullpath, $maxdepth-1);
        	if($maxdepth>0)
        		echo "<tr><td>".($maxdepth==MAXPRINT?"<b>":"").makeLink($fullpath).($maxdepth==MAXPRINT?"</b>":"")."</td><td>".$subcount."</td></tr>";
            $count += $subcount;
        } else {
            $count++;
        }   
    }
    return $count;
}
// some constants
define("TRESHOLD", "90"); // % to make action
define("FROM", "cron@wavecom.ee");
define("SUBJECT", "File (inode) limit critical");
define("MESSAGE", "File (inode) limit critical %d%%, %s of permitted %s on node: %s, path: %s\n");
//// START ////
// could be long tree..
set_time_limit(120);
if (PHP_SAPI === 'cli' || empty($_SERVER['REMOTE_ADDR'])){  // command line	
	// php filecount4.php -m10000 -etest@test.com
	// most options (except m) are optional, thus not accept space between option key and value
	$options = getopt("m:p::e::t::d::vn"); // path, maxcount, email, treshold %, depth, verbose, no-output
	//var_dump($options);
	$maxcount = $options["m"];
	if (!empty($maxcount)) {
		$path = $options["p"];
		if (empty($path)) $path = '.';			// if path not given, assume current dir
		ob_start ();
		$depth = $options["d"];
		$count = getFileCount($path, $depth);	// real job is here, if depth > 0, prits some stuf to output buffer
		$ob = ob_get_contents();
		ob_end_clean();
		//echo $count."\n";
		$rpath = realpath($path);
		$maxcount = intval($maxcount);
		$count  = intval($count);
		$treshold = $options["t"];
		if (empty($treshold)) $treshold = TRESHOLD; // in not given take default
		$treshold  = intval($treshold);				// treshold %
		$count_treshold = $maxcount * $treshold / 100;	// treshold count
		if($count > $count_treshold){
			// echo "suurem\n";
			$usage = $count * 100 / $maxcount;
			$txt = sprintf(MESSAGE, $usage, number_format($count), number_format($maxcount), gethostname (), $rpath );
			$no_output = $options["n"];
			if (!isset($no_output))	// value is bool (false)!
				echo "WARNING: ". $txt;
			$mailto = $options["e"];
			if (!empty($mailto)){
				$headers = "From: " .FROM. "\r\n";
				if (strlen($ob)){
					$headers .= "MIME-Version: 1.0" . "\r\n";
					$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
					$body = "<html> <head> <title>$txt</title> </head> <body>";
					$txt = $body . $txt . "<br>" .$ob. " </body> </html>";
				}
				$verbose = $options["v"];
				if (isset($verbose)){	// value is bool (flase)!
					echo " sending email to: $mailto \r\n with headers: \r\n$headers \r\n and message:\r\n$txt \n";
				}
				$ret = mail($mailto,SUBJECT,$txt,$headers);
				if (!$ret)// error
					echo "ERROR: sending mail() failed";
			}
			else {
				echo "ERROR: Cant send email, no To: address given, use: -e \n";
			}
		}
	}
	else echo "ERROR: no Max is given, use: -m \n";
}
else {// web req
	// filecount4.php?path=./dir&depth=1
	$path = isset($_GET["path"]) ? $_GET["path"] : ".";
	$depth = isset($_GET["depth"]) ? $_GET["depth"] : "1";
	
	$path = filter_var($path, FILTER_SANITIZE_STRING);
	$depth = filter_var($depth,FILTER_SANITIZE_NUMBER_INT);
	if (!is_string($path)) $path = ".";
	if (is_string($depth))	$depth = intval($depth);		
	else $depth = 1;
	echo "path=$path <br>"; // . 1
	$len = strrpos($path,'/',0);	// last /
	if ($len !== FALSE)
		$parent = substr($path,0,$len);
	else	
		$parent = '.';
	echo "parent=".makeLink($parent)."<br>";
	echo "<table border=\"1\"> ";
	echo "<tr> <th>Path</th> <th>Files</th> </tr>";
	echo "<b> Total Files:".getFileCount($path, $depth)."</b>";
	echo "</table>";
}
?>
