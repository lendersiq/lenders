<?php
/*
[  ] create amortization function so I can populate charts, and get cashflows & expenses


https://www.calculatorsoup.com/calculators/financial/loan-calculator.php
https://financialmentor.com/calculator/interest-rate-calculator

*/
class loan {
    public $type;
    public $principal;
    public $original;
    public $payment;
    public $rate;
    public $periods;
    public $months_early;
    public $average_outstanding;
    public $parameters;
    public $grade;
    
    function __construct($param_array, $cof_curve) {
        $this->date = date("F j, Y");
        $this->principal = 0;
        $this->original = 0;
        $this->payment = 0;
        $this->rate = floatval(0);
        $this->fee_income = floatval(0);
        $this->periods = 0;
        $this->reprice_period = 0;
        $this->amortization = 0;
        $this->months_early = 0;
        $this->loan_to_value = 80;
        $this->average_outstanding = 0;
        $this->tax_exempt = 0;
        $this->guarantee = 0;
        $this->guarantee_coverage_rate = 0;
        $this->report = "";
        $this->grade = "NG";
        $this->funding_credit = 0;
        $this->balance = 0;
        $this->sweep = 0;
        $this->sweep_rate = 0;
        $this->cof_curve = $cof_curve;
        $this->proposed_scheduled = array();
        $this->proposed_projected = array();

        //@@ pull this from assumptions in database; And some from FDIC data
        $this->config = array(
            "efficiency" => .7315,
            "tax_rate" => .21,
            "capital_floor" => .09,
            "loan_loss" => .0135,
            "min_loan_loss" => .0015,
            "roe_target" => 18
        );
        
        $this->parameters = $param_array;
        foreach ($param_array as $key => $value) {
            $this->$key = $value;
        }
        
        $this->warnings_list = array();
        $valid_parameters= array("type", "rate", "periods");
        foreach ($valid_parameters as $value) {
            if(!array_key_exists($value, $this->parameters)) {
                array_push($this->warnings_list, "$value is missing or invalid");
            }   
        }
        if (count($this->warnings_list) > 0) {
            echo json_encode($this->warnings_list);
        } else {
            $this->principal = intval($this->request) > 0 ? $this->request : (intval($this->principal) > 0 ? $this->principal : 0);
            $this->drivers = array(
                "collateral_recovery_rate" => .5,
                //"personal_guarantee_coverage_rate" => .05,
                //"government_guarantee_coverage_rate" => .8,
                "operations_capital_rate" => .01,
                //no guarantee, goverment, personal
                "guarantee_coverage_rates" => array(0, .8, .05)
    		);
            //$this->guarantee_coverage_rate = $this->guarantee == 1 ? $this->drivers["government_guarantee_coverage_rate"] : ($this->guarantee == 2 ? $this->drivers["personal_guarantee_coverage_rate"] : 0);
            //@@
            $this->guarantee_coverage_rate = isset($this->drivers["guarantee_coverage_rates"][intval($this->guarantee)]) ? $this->drivers["guarantee_coverage_rates"][intval($this->guarantee)] : 0;
            $this->amortization = isset($this->amortization) ? $this->amortization = $this->periods : $this->amortization;
            
            $this->amortization = min(max($this->amortization, 1), 480);
    	    $this->periods = min(max($this->periods, 1), 480);
    	    if ($this->amortization < $this->periods) {
    	        array_push($this->warnings_list, "amortization (" . $this->amortization . ") is less than periods (" . $this->periods . ")" );       
    	    }
    	    $this->reprice_period = intval($this->reprice_period) > 0 ? $this->reprice_period : $this->periods;
            $this->payment = $this->convert2real($this->principal * ($this->rate *.01 / 12) / (1 - (pow(1/(1 + ($this->rate*.01 / 12)), $this->amortization)))); 
            $this->tax_exempt = $this->type === 7 ? 1 : $this->tax_exempt;
            $this->config["roe_target"] = isset($this->roe_target) ? $this->roe_target : $this->config["roe_target"];
        }
    }
      
    private function properties_set( array $keys ) {
        $count = 0;
        if ( ! is_array( $keys ) ) {
            $keys = func_get_args();
            array_shift( $keys );
        }
        foreach ( $keys as $key ) {
        if ( $this->$key !== 0 ) {
                $count ++;
            }
        }
        return $count;
    }
    
    private static function convert2real($float, $precision=2) {
        if(!is_array($float)) {
            return intval($float * pow(10, $precision)) / pow(10, $precision);
        }
    }
    
    private static function str_putcsv($fields = array(), $delimiter = ',', $enclosure = '"') {
        $str = '';
        $escape_char = '\\';
        foreach ($fields as $value) {
            if (strpos($value, $delimiter) !== false ||
                strpos($value, $enclosure) !== false ||
                strpos($value, "\n") !== false ||
                strpos($value, "\r") !== false ||
                strpos($value, "\t") !== false ||
                strpos($value, ' ') !== false
            ) {
                $str2 = $enclosure;
                $escaped = 0;
                $len = strlen($value);
                for ($i = 0; $i < $len; $i++) {
                    if ($value[$i] == $escape_char) {
                        $escaped = 1;
                    } else if (!$escaped && $value[$i] == $enclosure) {
                        $str2 .= $enclosure;
                    } else {
                        $escaped = 0;
                    }
                    $str2 .= $value[$i];
                }
                $str2 .= $enclosure;
                $str .= $str2 . $delimiter;
            } else {
                $str .= $value . $delimiter;
            }
        }
        $str = substr($str, 0, -1);
        $str .= "\n";
        return $str;
    }
    
    public function array2csv($array, $delimiter = ',', $enclosure = '"') {
        $contents = '';
        foreach ($array as $line) {
            $contents .= $this->str_putcsv($line, $delimiter, $enclosure);
        }
        return $contents;
    }
    
    function set_income() {
        if ($this->rate > 0) {
            if ($this->periods > 0) {
                $this->non_interest_income = $this->fee_income > 0 ? $this->fee_income / $this->periods * min(12, $this->periods) : 0;
                $this->interest_income = 0;
                $mrate_low = $this->rate *.01 / 12;
    	        $mrate_high = $this->rate *.01 / 12;
                //prime + .75 at compression
            	$mrate_floor = .04 / 12;
            	//up to 200 bps (25 bps per year)
            	$mrate_ceiling = $this->rate *.01 / 12 + .02 / 12 ;
                $period_principal = $this->principal;
                for ($period = 1; $period <= $this->periods; $period++) {
                    $mrate_low = $mrate_low > $mrate_floor ? ($this->rate * .01 / 12) - intval(max(1, $period - $this->reprice_period + 11) / 12) * .0025 / 12  : $mrate_low; 
    		        $mrate_high = $mrate_high < $mrate_ceiling ? ($this->rate * .01 / 12) + intval(max(1, $period - $this->reprice_period + 11) / 12) * .0025 / 12  : $mrate_high; 
    		        $mrate_average = ($mrate_low + $mrate_high) / 2;
    		        array_push($this->proposed_projected, floatval(preg_replace('/[^\d.]/', '', number_format($period_principal * $mrate_average))));
    		        if ($period <= $this->reprice_period) {
    		            array_push($this->proposed_scheduled, floatval(preg_replace('/[^\d.]/', '', number_format($period_principal * $this->rate *.01 / 12))));
    		        }
                    $this->interest_income += $period_principal * $this->rate *.01 / 12;
    		        $period_paydown = $this->payment - $period_principal * $this->rate * .01 / 12;
    		        $period_principal -= $period_paydown;
                }
                $this->interest_income = $this->interest_income / $this->periods * 12;
                if ($this->periods < $this->amortization) {
    	            $this->last_payment = ($period_principal + $period_paydown) + ($period_principal + $period_paydown) * $this->rate * .01 / 12;
            	} else {
            	    $this->last_payment = $this->payment;
            	}
            
                return $this;
            } else {
                array_push($this->warnings_list, "term/periods is missing or invalid");
            }
        } else {
            array_push($this->warnings_list, "interest rate missing or invalid");
        }
        echo json_encode($this->warnings_list);
    }
    
    function set_costofFunds() {
        if (floatval($this->rate) > 0) {
            //@@$version_path = realpath("../");
            //@@require_once ($version_path . "/includes/render_ftp.php");
            //@@$cof_curve = $this->create_FTPCurve();
            if ($this->periods > 0 && $this->periods != null) {
                if (isset($this->cof_curve)) {
                    $sigma_principal = 0;
                    $sigma_cof = 0;
                    $period_principal = $this->principal;
                    $table_array = array(["Period", "Cost of Funds Rate", "Cost of Funds"]);
                    for ($period = 1; $period <= $this->periods; $period++) {
                        $line_temp = array();
                        $sigma_principal += $period_principal;
                        $period_paydown = $this->payment - $period_principal * $this->rate * .01 / 12;
                        if ($period <= $this->reprice_period ) {
        	                $period_cof = $this->cof_curve[min(359, $period-1)] / 100 * $period_paydown * $period;
                        }
                        $sigma_cof += $period_cof;
                        array_push($line_temp, $period, strval($this->convert2real($this->cof_curve[min(359, $period-1)],4)), strval($this->convert2real($period_cof)));
                        array_push($table_array, $line_temp);
                        $period_principal -= $period_paydown;
                    }
                    $this->average_outstanding = $sigma_principal / $this->periods; 
                    $this->cost_of_funds_rate = $sigma_cof / $sigma_principal;
                    $this->cost_of_funds = $this->average_outstanding * $this->cost_of_funds_rate;
                    if ($this->report == "costofFunds") {
                        $summary_array = array (
                            "periods" => $this->periods,
                            "amortization" => $this->amortization,
                            "principal" => number_format($this->principal, 2, '.', ''),
                            "payment" => number_format($this->payment, 2, '.', ''),
                            "sigma" => number_format($sigma_principal, 2, '.', ''),
                            "average" => number_format($this->average_outstanding, 2, '.', ''),
                            "cof" => number_format($this->cost_of_funds, 2, '.', ''),
                            "cofr" => number_format($this->cost_of_funds_rate, 4, '.', '')
                        );
                        echo json_encode($summary_array) . "\n";
                        echo $this->array2csv($table_array);
                        if (count($this->warnings_list) > 0) {
                            print_r($this->warnings_list);
                        }
                    }
                    return $this;
                } else {
                    array_push($this->warnings_list, "cost of funds of curve missing or invalid");
                }
            } else {
                    array_push($this->warnings_list, "periods is missing or invalid");
            }
        } else {
            array_push($this->warnings_list, "interest rate missing or invalid");
        }
        echo json_encode($this->warnings_list);
    }
    
    function set_non_interest_expense() {
        $servicing_slope = .0002;
        $version_path = realpath("../");
        include ($version_path . "/types/index.php");
        if (count($types) > 0) {
            if ($this->type > 0) {
                $this->kind = $types[$this->type]["name"];
                $this->risk_weight = $types[$this->type]["risk_weight"];
                $y_int = $this->config["efficiency"] * 1000 * $types[$this->type]["origin_weight"];
                if ($this->average_outstanding > 0) {
                    $origination_cost = $this->average_outstanding * $types[$this->type]["origin_m"] + $y_int;
                    if ($this->periods > 0) {
                        $origination_cost = $origination_cost / min(60, $this->periods) * min(12, $this->periods);
                        $servicing_cost = ($this->average_outstanding * $servicing_slope) + (1000 * $this->config["efficiency"] * $types[$this->type]["service_weight"]) / 12 * min(12, $this->periods); 
                        $this->non_interest_expense = $servicing_cost + $origination_cost;
                        return $this;
                    } else {
                         array_push($this->warnings_list, "term/periods is missing or invalid");
                    }
                } else {
                     array_push($this->warnings_list, "average outstanding is missing or invalid");
                }
            } else {
                array_push($this->warnings_list, "line type is missing or invalid");    
            }
        } else {
            array_push($this->warnings_list, "cannot load loan types");
        }
        echo json_encode($this->warnings_list);
    }
    
    function set_requiredCapital_LLR() {
        $collateral_value = $this->loan_to_value > 0 ? $this->principal / ($this->loan_to_value * .01) : 0;
        $collateral_exposure_mitigation = $collateral_value * $this->drivers["collateral_recovery_rate"];
        //@@
        $guarantee_coverage = $this->guarantee_coverage_rate * $this->principal;
        $sigma_principal = 0;
        $sigma_loss_reserve = 0;
        $sigma_required_capital = 0;
        $period_principal = $this->principal;
        $table_array = array(["Period", "Interest", "Paydown", "Principal","Collateral Exposure Mitigation","Exposure at Default (EAD)","Guarantee Coverage","Guarantee Risk","Unmitigated Exposure","Credit Capital%","Credit Capital","Operations Capital","Economic Capital","Regulatory Capital","Required Capital","Loan Loss%","Personal Guarantee Loss%","Loan Loss Reserve","Minimum Loan Loss"]);
        for ($period = 1; $period <= $this->periods; $period++) {
            $line_temp = array();
            $sigma_principal += $period_principal;
            $period_interest = $period_principal * $this->rate *.01 / 12;
	        $period_paydown = $this->payment - $period_interest;
	        $exposure_at_default = max(0, $period_principal - $collateral_exposure_mitigation);
	        $unmitigated_exposure = max(0, $exposure_at_default - $guarantee_coverage);
	        $guarantee_at_risk = intval(max(7000, 10000 - (120 - min(120, $this->periods - ($period - 1))) * 27.52293578)) / 10000;
            $credit_capital_rate = intval(max(1000, 4000 - (120 - min(120, $this->periods - ($period - 1))) * 27.52293578)) / 10000; 
            $credit_capital = intval(($guarantee_coverage * $guarantee_at_risk * $credit_capital_rate + $unmitigated_exposure * $credit_capital_rate)*100)/100;
	        $operations_capital = intval($this->drivers["operations_capital_rate"] * $period_principal * 100) / 100;
            $economic_capital = $credit_capital + $operations_capital;
            $regulatory_capital = $period_principal * $this->config["capital_floor"];
            $required_capital = max($economic_capital, $regulatory_capital);
            $sigma_required_capital += $required_capital;
            //@@
            $personal_guarantee_reserve = $this->guarantee == 2 ? $this->config["loan_loss"] : 0;
            //@@
            $loss_reserve = intval(($this->config["loan_loss"] * $unmitigated_exposure + ($guarantee_coverage * $personal_guarantee_reserve * $this->config["loan_loss"]))/12*100)/100;
            $min_loss_reserve = intval(($this->config["min_loan_loss"] * $period_principal)/12*100)/100;
            $sigma_loss_reserve += max($loss_reserve, $min_loss_reserve);
            array_push($line_temp, $period, strval($this->convert2real($period_interest)), strval($this->convert2real($period_paydown)), strval($this->convert2real($period_principal)), strval($this->convert2real($collateral_exposure_mitigation)), strval($this->convert2real($exposure_at_default)), $guarantee_coverage,  $guarantee_at_risk, strval($this->convert2real($unmitigated_exposure)));
            array_push($line_temp, $credit_capital_rate, $credit_capital, $operations_capital, strval($this->convert2real($economic_capital)), strval($this->convert2real($regulatory_capital)), strval($this->convert2real($required_capital)), $this->config["loan_loss"], $personal_guarantee_reserve, $loss_reserve, max($loss_reserve, $min_loss_reserve));
            array_push($table_array, $line_temp);
	        $period_principal -= $period_paydown;
        }
        $this->average_outstanding = $sigma_principal / $this->periods; 
        $this->capital_percent = $sigma_required_capital / $this->periods / $this->average_outstanding;
        $this->capital_assigned = $sigma_required_capital / $this->periods; 
        $this->loan_loss_rate = $sigma_loss_reserve / $this->periods * 12 / $this->average_outstanding;
        $this->loan_loss_provision = $sigma_loss_reserve / $this->periods * 12;
        if ($this->report == "capital_LLR") {
            $summary_array = array (
                "payment" => number_format($this->payment, 2, '.', ''),
                "average" => number_format($this->average_outstanding, 2, '.', ''),
                "cap" => number_format($this->capital_percent, 2, '.', ''),
                "llr" => number_format($this->loan_loss_rate, 4, '.', '')
            );
            echo json_encode($summary_array) . "\n";
            echo $this->array2csv($table_array);
            if (count($this->warnings_list) > 0) {
                print_r($this->warnings_list);
            }
        }
        return $this;
    }
    
    function add_dda_netincome() {
        if ($this->balance > 0) {
            //capital_expense = balance X expected loss X capital expense (default: 10%) - annualized
            $capital_expense = .25 * $this->capital_percent;
            if (isset($this->cof_curve)) {
                $this->funding_credit_rate = $this->cof_curve[11] - $capital_expense; //credit for funding rate
                //@@volumes may be introduced in future versions
                //electronic deposit, deposits, electronic wd, check, deposit item, dda monthly
                //$deposit_expense = array(.03, .23, .11, .23, .16, 9);
                $dda_operating_cost = 400;
        		$this->dda_funding_credit = ($this->balance * (1-.10)) * $this->funding_credit_rate * .01; //credit for funding loan
        		$this->funding_credit += $this->dda_funding_credit - $dda_operating_cost;
            } else {
                array_push($this->warnings_list, "cost of funds of curve missing or invalid");
            }
        }
        return $this;
    }
    
    function add_sweep_netincome() {
        if ($this->sweep > 0  && $this->sweep_rate > 0) {
            //capital_expense = balance X expected loss X capital expense (default: 10%) - annualized
            $capital_expense = .25 * $this->capital_percent;
            if (isset($this->cof_curve)) {
                $this->funding_credit_rate = $this->cof_curve[11] - $capital_expense; //credit for funding rate
                //@@volumes may be introduced in future versions
                //electronic deposit, deposits, electronic wd, check, deposit item, dda monthly
                //$deposit_expense = array(.03, .23, .11, .23, .16, 9);
                $sweep_operating_cost = 108;
        		$this->sweep_funding_credit = ($this->sweep * (1-.10)) * $this->funding_credit_rate * .01; //credit for funding loan
        		$sweep_interest =  $this->sweep * $this->sweep_rate *.01;
        		$this->funding_credit += $this->sweep_funding_credit - $sweep_operating_cost - $sweep_interest;
            } else {
                array_push($this->warnings_list, "cost of funds of curve missing or invalid");
            }
        }
        return $this;
    }
    
    
    function set_netincome() {
        $this->pretax_income = $this->interest_income + $this->non_interest_income - $this->cost_of_funds - $this->non_interest_expense + $this->funding_credit;
        if ($this->tax_exempt == 1) {
            $this->tax_expense = ($this->cost_of_funds * $this->config["tax_rate"]) + ($this->non_interest_expense * $this->config["tax_rate"]) * -1;      
        } else {
            $this->tax_expense = $this->pretax_income * $this->config["tax_rate"];        
        }
        $this->net_income = $this->pretax_income - $this->tax_expense;
        $this->net_income -= $this->loan_loss_provision;
        $this->raroc = $this->net_income / ($this->capital_assigned * $this->risk_weight) * 100;
        $grade_number = 3;
        $key = 0;
        for ( $threshold = $this->config["roe_target"]; $threshold > 0; $threshold -= floor($this->config["roe_target"]/4) ) {
            if ( $this->raroc >= $threshold ) {
                $grade_number = $key;
                $threshold = 0;
            }
            $key++;
        }
        $grade_number = min(max($grade_number, 0), 3);
	    $this->grade = array('A', 'B', 'C', 'D')[$grade_number];
        return $this;
    }
    
    function target_delta() {
        if (isset($this->raroc)) {
            if ($this->tax_exempt == 1) {
        		$this->taget_delta = round($this->capital_assigned * $this->risk_weight * ( $this->config["roe_target"] / 100 ) - $this->net_income);
        	} else {
        		$this->target_delta = round(($this->capital_assigned * $this->risk_weight * ( $this->config["roe_target"] / 100 ) + $this->loan_loss_provision) / (1 - $this->config["tax_rate"]) - $this->pretax_income);
            }
            return $this;
        } else {
            array_push($this->warnings_list, "missing RAROC");
        }
    }
    
    function return_all_json() {
        if ($this->report == "") {
            $return = array();
            $observations = array();
            
            /*
    		foreach ($return->parameters as $key => $value) {
                $return->parameters[$key] = is_numeric($value) ? strval($this->convert2real($value,4)) : $value;
    		}
    		foreach ($return->config as $key => $value) {
                $return->config[$key] = strval($this->convert2real($value,4));
    		}
    		foreach ($return->drivers as $key => $value) {
                $return->drivers[$key] = strval($this->convert2real($value,4));
    		}
    		
    		*/
    		
    		foreach ($this as $key => $value) {
    		    if (!is_array($value)) {
                    $observations[$key] = !is_string($value) ? strval($this->convert2real($value, 2)) : $value;
    		    }
    		}
    		$observations["cost_of_funds_rate"] = strval($this->convert2real($this->cost_of_funds_rate*100, 2));
    		$observations["loan_loss_rate"] = strval($this->convert2real($this->loan_loss_rate*100, 2));
    		$observations["product"] = $observations["type"];
    		unset($observations["type"]);
    		$return["observations"] = $observations;
    		$return["proposed_scheduled"] = $this->proposed_scheduled;
    		$return["proposed_projected"] = $this->proposed_projected;
    		//print_r($return);
    		return json_encode($return);
        }
    }

    function get_interestSchedule() {
        if ($this->properties_set(["rate", "payment", "periods", "principal", "original"]) >= 4) {
            if (floatval($this->rate) != 0 ) {
                $this->monthly_rate = floatval(str_replace('$', '', str_replace(',', '', $this->rate))) * .01 / 12;
                $this->months_early = $this->months_early === 0 || $this->months_early > $this->periods ? $this->periods : $this->months_early;
                //@@
                /*
                if ($this->avg_outstanding !== 0) {
                    
                    $decline_principal = $this->avg_outstanding;
                    $this->payment = $this->payment === 0 ? $this->avg_outstanding * $this->monthly_rate : $this->payment;
                }
                */
                if ($this->original !== 0) {
                    $decline_principal = $this->original;  
                }
                else {
                    $decline_principal = $this->principal;
                }
                $interest_expense = 0;
                $proj_interest = 0;
                
                //@@
                $baseline_stream = array();
            	$proj_stream = array();
            	$mrate_low = $this->monthly_rate; 
            	$mrate_high = $this->monthly_rate;
            	$mrate_avg = $this->monthly_rate;
            	//prime + .75 at compression
            	$mrate_floor = .04 / 12;
            	//up to 200 bps (25 bps per year)
            	$mrate_ceiling = $this->monthly_rate  + .02 / 12;
                
                $period = 0;
                $early_target = $this->original !== 0 ? $this->principal : 0;
                while ($period < $this->periods  && $decline_principal > 0) {
                    $interest = $decline_principal * $this->monthly_rate;
                    //@@if($this->avg_outstanding === 0) { //its a line
                    $decline_principal -= ($this->payment - $interest);
                    //}
                    //@@if ($decline_principal > 0) { 
                        //array_push($principal_schedule, round($decline_principal, 2));
                    
                    //@@ if ($decline_principal > $early_target || ($decline_principal <= 0 && $interest > 0) ) { 
                    if ( ($decline_principal > $early_target  || ($period + 1 == $this->periods && $interest > 0)) && ($period < $this->months_early) ) { 
                        array_push($baseline_stream, floatval(preg_replace('/[^\d.]/', '', number_format(max(0, $interest)))));
                        $interest_expense += $interest;
                    }
                    
                    //@@
                    $mrate_low = $mrate_low > $mrate_floor ? $this->monthly_rate - intval(max(1, $period - $this->months_early + 11) / 12) * .0025 / 12  : $mrate_low; 
    		        $mrate_high = $mrate_high < $mrate_ceiling ? $this->monthly_rate + intval(max(1, $period - $this->months_early + 11) / 12) * .0025 / 12  : $mrate_high; 
    		        $mrate_avg = ($mrate_low + $mrate_high) / 2;
    		        array_push($proj_stream, floatval(preg_replace('/[^\d.]/', '', number_format($decline_principal * $mrate_avg))));    
    		        $proj_interest += $decline_principal * $mrate_avg;
                    //@@array_push($thinkable_schedule, floatval(preg_replace('/[^\d.]/', '', number_format(max(0, $interest))))); 
                    //@@$thinkable_interest += $interest;
                
                    $period++;
                }
                
                //@@ may not be needed
                /*
                $early_schedule = array();
                $early_interest = 0;
                for ($period = count($schedule) - $this->months_early; $period < count($schedule); $period++) {
                    array_push($early_schedule, floatval(preg_replace('/[^\d.]/', '', number_format($schedule[$period]))));
                    $early_interest += $schedule[$period];
                }
                */
        
                //@@$interest_expense = $interest_expense / $this->periods * 12;
 
                $return["rate"] = number_format($this->rate, 2, '.', '');
                $return["periods"] = number_format($this->periods, 2, '.', ',');
                $return["principal"] = number_format($this->principal, 2, '.', ',');
                $return["original"] = number_format($this->original, 2, '.', ',');
                $return["avg_outstanding"] = number_format($this->avg_outstanding, 2, '.', ',');
                $return["base_months"] = count($baseline_stream);
                $return["base_interest"] = number_format($interest_expense, 2, '.', ',');
                $return["payment"] = number_format($this->payment, 2, '.', ',');
                $return["interest_proj"] = number_format($proj_interest, 2, '.', ',');
                $return["scheduled"] = $baseline_stream;
                $return["projected"] = $proj_stream;
                return json_encode($return);
            } else {
                $return["director"] = "rate object is null";
                return json_encode($return);
            }
        } else {
            $return["director"] = "missing parameters";
            return json_encode($return);
        }
    }
    

    function get_presentValue() {
        if ($this->properties_set(["rate", "payment", "periods"]) === 3) {
            $this->monthly_rate = $this->rate * .01 / 12;
            $this->present_value = ($this->payment / $this->monthly_rate) * (1-1/pow(1 + $this->monthly_rate, $this->periods));
            return $this->present_value;
        } else {
            return "missing parameter";
        }
    }
    
    function get_originalPayment() {
        if ($this->properties_set(["rate", "original", "periods"]) === 3) {
            $this->monthly_rate = $this->rate * .01 / 12;
            $this->payment = $this->original * $this->monthly_rate / (1 - (pow(1/(1 + $this->monthly_rate), $this->periods)));     
            return $this->payment;
        } else {
            return "missing parameter";
        }
    }
    
    function get_payment() {
        if ($this->properties_set(["rate", "principal", "periods"]) === 3) {
            $this->monthly_rate = $this->rate * .01 / 12;
            $this->payment = $this->principal * $this->monthly_rate / (1 - (pow(1/(1 + $this->monthly_rate), $this->periods)));
            return $this->payment;
        } else {
            return "missing parameter";
        }
    }
    
    function get_originPrincipal() {
        if ($this->properties_set(["rate", "payment", "periods"]) === 3) {
            $this->monthly_rate = $this->rate * .01 / 12;
            $pow = 1;
            for ($loop = 0; $loop < $this->periods; $loop++) {
                $pow = $pow * (1 + $this->monthly_rate);
            }
            $this->principal = (($pow - 1) * $this->payment) / ($pow * $this->monthly_rate);
            return $this->principal;
        } else {
            return "missing parameter";
        }
    }
    
    function get_periods() {
        if ($this->properties_set(["rate", "payment", "principal"]) === 3) {
            $this->monthly_rate = $this->rate * .01 / 12;
            $count = 0;
            $decline_principal = $this->principal;
            $interest_port = 0;
            $principal_port = 0;
            while ($decline_principal > 0) {
                $interest_port = $decline_principal * $this->monthly_rate;
                $principal_port = $this->payment - $interest_port;
                $decline_principal -= $principal_port;
                $count++;
            }
            $this->periods = $count;
            return $this->periods;
        } else {
            return "missing parameter";
        }
    }
    
    function get_lineRate() {
        if ($this->properties_set(["avg_outstanding", "payment"]) === 2) {
            $this->rate = floor($this->payment * 12 / $this->avg_outstanding  * 1000) / 10;
            return $this->rate;
        } else {
            return "missing parameter";
        }
    }
    
    function get_rate() {
        if ($this->properties_set(["periods", "payment", "principal"]) === 3) {
            /*       
            function computeIntRate(myNumPmts, myPrin, myPmtAmt, myGuess) {
            var myDecRate = 0;
            if(myGuess.length == 0 || myGuess == 0) {
               var myDecGuess = 10;
               } else {
               var myDecGuess = myGuess;
               if(myDecGuess >= 1) {
                  myDecGuess = myDecGuess /100;
                  }
               }
            */
            $decGuess = 10; 
            //var myDecRate = myDecGuess / 12;
            $decRate = $decGuess / 12;
            //var myNewPmtAmt = 0;
            //var pow = 1;
            $pow = 1;
            //var j = 0;
            //for (j = 0; j < myNumPmts; j++) {
            for ($loop = 0; $loop < $this->periods; $loop++) {
                //pow = pow * (eval(1) + eval(myDecRate));
                $pow = $pow * (1 + $decRate);
            }
            //myNewPmtAmt = (myPrin * pow * myDecRate) / (pow - 1);
            $newPmtAmt = ($this->principal * $pow * $decRate) / ($pow - 1);
            
            //2 DEC PLACE AMOUNT
            //var decPlace2Rate = (eval(myDecGuess) + eval(.01)) / 12;
            $decPlace2Rate = ($decGuess + .01) / 12;
            //var decPlace2Amt = 0;
            //pow = 1;
            $pow = 1;
            //j=0;
            //for (j = 0; j < myNumPmts; j++) {
            for ($loop = 0; $loop < $this->periods; $loop++) {
                //pow = pow * (eval(1) + eval(decPlace2Rate));
                $pow = $pow * (1 + $decPlace2Rate);
            }
            //var decPlace2PmtAmt = (myPrin * pow * decPlace2Rate) / (pow - 1);
            $decPlace2PmtAmt = ($this->principal * $pow * $decPlace2Rate) / ($pow - 1);
            //decPlace2Amt = eval(decPlace2PmtAmt) - eval(myNewPmtAmt);
            $decPlace2Amt = $decPlace2PmtAmt - $newPmtAmt;    
        
            //3 DEC PLACE AMOUNT
            //var decPlace3Rate = (eval(myDecGuess) + eval(.001)) / 12;
            $decPlace3Rate = ($decGuess + .001) / 12;
            //var decPlace3Amt = 0;
            //pow = 1;
            $pow = 1;
            //j=0;
            //for (j = 0; j < myNumPmts; j++) {
            for ($loop = 0; $loop < $this->periods; $loop++) {
                //pow = pow * (eval(1) + eval(decPlace3Rate));
                $pow = $pow * (1 + $decPlace3Rate);
            }
            //var decPlace3PmtAmt = (myPrin * pow * decPlace3Rate) / (pow - 1);
            $decPlace3PmtAmt = ($this->principal * $pow * $decPlace3Rate) / ($pow - 1);
            $decPlace3Amt = $decPlace3PmtAmt - $newPmtAmt;
        
            //4 DEC PLACE AMOUNT
            //var decPlace4Rate = (eval(myDecGuess) + eval(.0001)) / 12;
            $decPlace4Rate = ($decGuess + .0001) / 12;
            //var decPlace4Amt = 0;
            //pow = 1;
            $pow = 1;
            //j=0;
            //for (j = 0; j < myNumPmts; j++) {
            for ($loop = 0; $loop < $this->periods; $loop++) {
                //pow = pow * (eval(1) + eval(decPlace4Rate));
                $pow = $pow * (1 + $decPlace4Rate);
            }
            //var decPlace4PmtAmt = (myPrin * pow * decPlace4Rate) / (pow - 1);
            $decPlace4PmtAmt = ($this->principal * $pow * $decPlace4Rate) / ($pow - 1);
            //decPlace4Amt = eval(decPlace4PmtAmt) - eval(myNewPmtAmt);
            $decPlace4Amt = $decPlace4PmtAmt - $newPmtAmt;
            
            //5 DEC PLACE AMOUNT
            //var decPlace5Rate = (eval(myDecGuess) + eval(.00001)) / 12;
            $decPlace5Rate = ($decGuess + .00001) / 12;
            //var decPlace5Amt = 0;
            //pow = 1;
            $pow = 1;
            //j=0;
            //for (j = 0; j < myNumPmts; j++) {
            for ($loop = 0; $loop < $this->periods; $loop++) {
                //pow = pow * (eval(1) + eval(decPlace5Rate));
                $pow = $pow * (1 + $decPlace5Rate);
            }
            //var decPlace5PmtAmt = (myPrin * pow * decPlace5Rate) / (pow - 1);
            $decPlace5PmtAmt = ($this->principal * $pow * $decPlace5Rate) / ($pow - 1);
            //decPlace5Amt = eval(decPlace5PmtAmt) - eval(myNewPmtAmt);
            $decPlace5Amt = $decPlace5PmtAmt - $newPmtAmt;
        
            //var myPmtDiff = 0;
            $pmtDiff = 0;
            
            //if(myNewPmtAmt < myPmtAmt) {
            if($newPmtAmt < $this->payment) {
                //while(myNewPmtAmt < myPmtAmt) {
                while ($newPmtAmt < $this->payment) {
                    //myPmtDiff = eval(myPmtAmt) - eval(myNewPmtAmt);
                    $pmtDiff = $this->payment - $newPmtAmt;
                    //if(myPmtDiff > decPlace2Amt) {
                    if($pmtDiff > $decPlace2Amt) {
                        //myDecRate = eval(myDecRate) + eval(.01 / 12);
                        $decRate = $decRate + (.01 / 12);
                    //} else
                    //if(myPmtDiff > decPlace3Amt) {
                    }
                    else if($pmtDiff > $decPlace3Amt) {
                        //myDecRate = eval(myDecRate) + eval(.001 / 12);
                        $decRate = $decRate + (.001 / 12);
                    //} else
                    }
                    //if(myPmtDiff > decPlace4Amt) {
                    else if($pmtDiff > $decPlace4Amt) {
                        //myDecRate = eval(myDecRate) + eval(.0001 / 12);
                        $decRate = $decRate + (.0001 / 12);
                    //} else
                    //if(myPmtDiff > decPlace5Amt) {
                    }
                    else if($pmtDiff > $decPlace5Amt) {
                        //myDecRate = eval(myDecRate) + eval(.00001 / 12);
                        $decRate = $decRate + (.00001 / 12);
                    } else {
                        //myDecRate = eval(myDecRate) + eval(.000001 / 12);
                        $decRate = $decRate + (.000001 / 12);
                    }
                    //pow = 1
                    $pow = 1; 
                    //j = 0;
                    //for (j = 0; j < myNumPmts; j++) {
                    for ($loop = 0; $loop < $this->periods; $loop++) {
                        //pow = pow * (eval(1) + eval(myDecRate));
                        $pow = $pow * (1 + $decRate);
                    }
                    $newPmtAmt = ($this->principal * $pow * $decRate) / ($pow - 1);
                }
            } else {
                //while(myNewPmtAmt > myPmtAmt) {
                while ($newPmtAmt > $this->payment) {
                    //myPmtDiff = eval(myNewPmtAmt) - eval(myPmtAmt);
                    $pmtDiff = $newPmtAmt - $this->payment;
                    //if(myPmtDiff > decPlace2Amt) {
                    if ($pmtDiff > $decPlace2Amt) {
                        //myDecRate = eval(myDecRate) - eval(.01 / 12);
                        $decRate = $decRate - (.01 / 12);
                    //} else
                    //if(myPmtDiff > decPlace3Amt) {
                    }
                    else if ($pmtDiff > $decPlace3Amt) { 
                        //myDecRate = eval(myDecRate) - eval(.001 / 12);
                        $decRate = $decRate - (.001 / 12);
                    //} else
                    //if(myPmtDiff > decPlace4Amt) {
                    }
                    else if ($pmtDiff > $decPlace4Amt) { 
                        //myDecRate = eval(myDecRate) - eval(.0001 / 12);
                        $decRate = $decRate - (.0001 / 12);
                        
                    //} else
                    //if(myPmtDiff > decPlace5Amt) {
                    }
                    else if ($pmtDiff > $decPlace5Amt) {
                        //myDecRate = eval(myDecRate) - eval(.00001 / 12);
                        $decRate = $decRate - (.00001 / 12);
                    } else {
                        //myDecRate = eval(myDecRate) - eval(.000001 / 12);
                        $decRate = $decRate - (.000001 / 12);
                    }
                    //pow = 1
                    $pow = 1;
                    //j = 0;
                    //for (j = 0; j < myNumPmts; j++) {
                    for ($loop = 0; $loop < $this->periods; $loop++) {
                        //pow = pow * (eval(1) + eval(myDecRate));
                        $pow = $pow * (1 + $decRate);
                    }
                    //myNewPmtAmt = (myPrin * pow * myDecRate) / (pow - 1);
                    $newPmtAmt = ($this->principal * $pow * $decRate) / ($pow - 1);
                }
            }
            $decRate = $decRate * 12 * 100;
            $this->rate = floor($decRate * 1000) / 1000;
            return $this->rate;
        } else {
            return "missing parameter";
        }
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
echo "\n<br> Payment = " . $loan1->get_payment();
echo "\n<br>" . $loan1->get_principalAmortization();
*/

?>