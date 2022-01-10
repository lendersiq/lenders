<?PHP
function create_FTPCurve($home_path) {
    require_once ($home_path . "s1/config.php");
    //@@echo $home_path; //. "s1/config.php\n";
    $sql = "SELECT rate_json FROM classic_adv_rates ORDER BY id DESC LIMIT 1";
    if ($result = $link->query($sql)) {
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $get_json = $row["rate_json"];
            }
        } else {
            echo "0 results";
        }
    } else {
        echo "Error: " . $sql . "<br>" . $link->error;
    }

    $current_rates = json_decode($get_json, true);
    //guru
    //print_r($current_rates);
    
    $series_labels = array("1 mo","2 mo","3 mo","4 mo","5 mo","6 mo","9 mo","1 yr","1.25 yr","1.50 yr","1.75 yr","2 yr","2.25 yr","2.50 yr","2.75 yr","3 yr","3.50 yr","4 yr","4.50 yr","5 yr","5.50 yr","6 yr","6.50 yr","7 yr","7.50 yr","8 yr","8.50 yr","9 yr","9.50 yr","10 yr","15 yr","20 yr");
    
	$series_mos = array(1,2,3,4,5,6,9,12,15,18,21,24,27,30,33,36,42,48,54,60,66,72,78,84,90,96,102,108,114,120,180,240);	
	
	//unit test
	$test_failure = FALSE;
	if (count($current_rates) != count($series_labels)) {
	    $test_failure = TRUE;
	}
	else {
	    //guru
	    //echo "<br>\n";
	    //foreach ($series_mos as $value) {
	        //echo "$value<br>\n";
	    //}
	    //echo "<br>\n";
        foreach ($current_rates as $index => $value) {
            if (!is_int(array_search($index, $series_labels))) {
    	        $test_failure = TRUE;
    	    }
    	}
	}
	
	$r_curve = array();
    $current_rate = current($current_rates);
	$next_rate = next($current_rates);
	$mos_array = $series_mos;
	$mos = current($mos_array);
	unset($mos_array[0]);
	foreach ($mos_array as $next_mos) {
	    //mos = 1, next_mos = 2
	    $mos_gap = $next_mos - $mos;
		$rate_gap =  $next_rate - $current_rate;
		$increment = $rate_gap / $mos_gap;		
		$pointer = 0;			
		for ($period = $mos; $period <  $next_mos; $period++, $pointer++) {
		    array_push($r_curve, $current_rate + $increment * $pointer);
		}
		$current_rate = $next_rate;
		$next_rate = next($current_rates);
		$mos = $next_mos;
	}
	array_push($r_curve, $current_rate);
	//get t_rates
    $sql = "SELECT rate_json FROM fed_rates ORDER BY id DESC LIMIT 1";
    $result = $link->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $get_json = $row["rate_json"];
        }
    } else {
        echo "0 results";
    }
    $t_thirty = json_decode($get_json, true)["DGS30"];
    if (defined("FUNDING_ADJ")) {
        $t_thirty += FUNDING_ADJ; 
    } else {
        $t_thirty += 0.60;  //default
    }
    //mysqli_close($link);
    //guru 
    //echo "t_thirty: $t_thirty\nmonths: $mos\n";
    
    $increment = ($t_thirty - $current_rate) / (360 - $mos);		
	$pointer = 1;	
    for ($period = $mos; $period <  360; $period++, $pointer++) {
	    array_push($r_curve, $current_rate + $increment * $pointer);
	}
	return $r_curve;
}

/* guru
$curve = create_FTPCurve($home_path);
foreach ($curve as $value) {
    echo $value * .01 . "\n";
}
*/

?>