<?php
function rate_guide($loan, $parameters) {
    $rate_adjust = max($loan["target_delta"] / $loan["average_outstanding"] * 100, 0);
    $test_rate = $rate_adjust + $loan["rate"];
    $test_rate = round($test_rate * 8) / 8;
    $guide_rate = 0;
    while ($test_rate != $guide_rate) {
        $parameters["rate"] = $test_rate;
        $test_loan = new loan($parameters, $GLOBALS["cof_curve"]);
        $test_loan->set_income()->set_costofFunds()->set_non_interest_expense()->set_requiredCapital_LLR()->set_netincome()->target_delta();
        $rate_adjust = max($test_loan->target_delta / $test_loan->average_outstanding * 100, 0);
        $guide_rate = $rate_adjust + $test_loan->rate;
        $guide_rate = round($guide_rate * 8) / 8;
        $test_rate = $guide_rate < $test_rate ? $test_rate - .125 : ( $guide_rate > $test_rate ? $test_rate + .125 : $guide_rate);
    }    
    return number_format($guide_rate, 3, '.', '') . "%";
}

function reprice_guide($loan, $parameters) {
    for ($reprice_period = 120; $reprice_period > 0; $reprice_period -= 12) {
        $parameters["reprice_period"] = $reprice_period; 
        $test_loan = new loan($parameters, $GLOBALS["cof_curve"]);
        $test_loan->set_income()->set_costofFunds()->set_non_interest_expense()->set_requiredCapital_LLR()->set_netincome()->target_delta();
        $guide_period = $test_loan->target_delta > 0 ? 0 : $reprice_period;
        $reprice_period = $test_loan->target_delta > 0 ? $reprice_period : 0;
    }
    return $guide_period;
}

function maturity_guide($loan, $parameters) {
    $guide_maturity = array();
    $guide_term = 0;
    for ($period = $loan["periods"] - 1; $period >= max(12, $loan["periods"] / 2 ); $period--) {
        $parameters["periods"] = $period;
        $test_loan = new loan($parameters, $GLOBALS["cof_curve"]);
        $test_loan->set_income()->set_costofFunds()->set_non_interest_expense()->set_requiredCapital_LLR()->set_netincome()->target_delta();
        $guide_term = $test_loan->target_delta > 0 ? 0 : $period;
        $period = $test_loan->target_delta > 0 ? $period : 0;
    }
    if ($guide_term != 0) {
        array_push($guide_maturity, $guide_term);
    }
    
    for ($period = $loan["periods"] + 1; $period <= min(360, $loan["periods"] * 1.5); $period++) {
        $parameters["periods"] = $period;
        $test_loan = new loan($parameters, $GLOBALS["cof_curve"]);
        $test_loan->set_income()->set_costofFunds()->set_non_interest_expense()->set_requiredCapital_LLR()->set_netincome()->target_delta();
        $guide_term = $test_loan->target_delta > 0 ? 0 : $period;
        $period = $test_loan->target_delta > 0 ? $period : 360;
    }
    if ($guide_term !== 0) {
        array_push($guide_maturity, $guide_term);
    }
    return $guide_maturity;
}

function ltv_guide($loan, $parameters) {
    $guide_ltv = 0;
    for ($ltv = $loan["loan_to_value"] > 0 ? $loan["loan_to_value"] : 100; $ltv >= 50; $ltv--) {
        $parameters["loan_to_value"] = $ltv;
        $test_loan = new loan($parameters, $GLOBALS["cof_curve"]);
        $test_loan->set_income()->set_costofFunds()->set_non_interest_expense()->set_requiredCapital_LLR()->set_netincome()->target_delta();
        $guide_ltv = $test_loan->target_delta > 0 ? 0 : $ltv;
        $ltv = $test_loan->target_delta > 0 ? $ltv : 0;
    }
    return $guide_ltv;
}

function deposits_guide($loan) {
    $capital_expense = .25 * $loan["capital_percent"];
    $cff_rate = $GLOBALS["cof_curve"][12] - $capital_expense; 
    $guide_deposits = max(round(($loan["target_delta"] + 400)  / ($cff_rate * .01)), 0);
    return $guide_deposits / .90; 
}

function smart_guide($loan_array, $original_parameters) {
    if (isset($loan_array["raroc"])) {
        $iq = array();
    	if ( $loan_array["target_delta"] > 0 ) {
            $iq["guidance_rate"] = rate_guide($loan_array, $original_parameters);
            $iq["guidance_fees"] = number_format(max($loan_array["target_delta"] * $loan_array["periods"] / 12, 0), 2, '.', ',');
            $iq["guidance_reprice"] = reprice_guide($loan_array, $original_parameters);
            if ($iq["guidance_reprice"] == 0) {
                unset($iq["guidance_reprice"]);
            }
            $iq["guidance_ltv"] = ltv_guide($loan_array, $original_parameters);
            if ($iq["guidance_ltv"] == 0) {
                unset($iq["guidance_ltv"]);
            }
            $iq["guidance_deposits"] = number_format(deposits_guide($loan_array), 2, '.', ',');
            $iq["guidance_maturity"] = "";
            foreach (maturity_guide($loan_array, $original_parameters) as $value) {
                $iq["guidance_maturity"] .= "$value,";
            }
            if ( $iq["guidance_maturity"] != "" ) {
                $iq["guidance_maturity"] = substr($iq["guidance_maturity"], 0, -1);
            } else {
                unset($iq["guidance_maturity"]);
            }
    	    return $iq;
    	}
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET["type"]) && isset($_GET["request"]) && isset($_GET["periods"]) && isset($_GET["rate"])) {
    /* @@ guru
    https://www.bankersiq.com/v4/loan/?type=2&principal=1000000&periods=36&amortization=120&rate=5&report=capital_LLR
    //reprice_period=24
    */
    $version_path = realpath("../");
    require_once ($version_path . "/includes/render_ftp.php");
    $cof_curve = create_FTPCurve("../../");
    require_once ("includes/loan_class.php");
    $loan1 = new loan($_GET, $cof_curve);
    //$loan1->set_income(); 
    //$loan1->set_requiredCapital_LLR();
    //$loan1->set_costofFunds()->set_non_interest_expense();
    //$json_result = $loan1->return_all_json();
    //$json_result = $loan1->set_costofFunds();
    $result_json = $loan1->set_income()->set_costofFunds()->set_non_interest_expense()->set_requiredCapital_LLR()->add_dda_netincome()->add_sweep_netincome()->set_netincome()->target_delta()->return_all_json();
    $result_array = json_decode($result_json, true);
    if (isset($result_array["observations"])) {
        $result_array["iq"] = smart_guide($result_array["observations"], $_GET);
    }
    echo json_encode($result_array);
}   

if (!empty($_POST)) {
    require_once "../../s1/index.php";
    $valid_parameters= array("principal", "original", "rate", "payment", "periods", "months_early", "avg_outstanding", "type");
    $post_array = array();
    foreach ($_POST as $key => $value) {
        if (in_array($key, $valid_parameters)) {
            $post_array[$key] = str_replace( ',', '', $value );
        }   
    }
    $version_path = realpath("../");
    if (count($post_array) >= 3) {
        require_once ($version_path . "/includes/render_ftp.php");
        $cof_curve = create_FTPCurve("../../");
        require_once ("includes/loan_class.php");
        $loan1 = new loan($post_array, $cof_curve);
        //@@
        
        /* if (array_key_exists("avg_outstanding", $post_array) || array_key_exists("payment", $post_array)) {
            $loan1->get_lineRate();
        } */
        if (!array_key_exists("principal", $post_array) && array_key_exists("payment", $post_array)) {
            $loan1->get_originPrincipal();
        }
        else if (!array_key_exists("rate", $post_array)) {
            $loan1->get_rate();
        }
        else if (!array_key_exists("payment", $post_array) && array_key_exists("original", $post_array)) {
            $loan1->get_originalPayment();
        }
        else if (!array_key_exists("payment", $post_array)) {
            $loan1->get_payment();
        }
        else {
            $loan1->get_periods();
            //@@echo "\n<br> Months = " . $loan1->get_periods();
        }
        echo $loan1->get_interestSchedule();
    }
    //this needs a fix -- line rate??
    /*
    else if (array_key_exists("avg_outstanding", $post_array) || array_key_exists("payment", $post_array)) {
        require_once ($version_path . "/includes/render_ftp.php");
        $cof_curve = create_FTPCurve("../../");
        require_once ("includes/loan_class.php");
        $loan1 = new loan($post_array, $cof_curve);
        $loan1->get_lineRate();
        echo $loan1->get_interestSchedule();
    }
    */
    else {
        $return["director"] = "invalid parameters";
        echo json_encode($return);
    }
}

/*
$post_array = array(
    "rate" => 4.5,
    "payment" => 85.38,
    "periods" => 12,
    "principal" => 1000
);

$loan1 = new loan($post_array);
echo "\n<br> PV = " . $loan1->get_presentValue();
echo "\n<br> Original principal = " . $loan1->get_originPrincipal();
echo "\n<br> Months = " . $loan1->get_periods();
echo "\n<br> Rate = " . $loan1->get_rate();
echo "\n<br>" . $loan1->get_principalAmortization();
*/
?>

