<?php 
    require_once "../s1/index.php";
    require_once dirname(__FILE__) . "/types/index.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-store" />
    <title>Smart bank innovation</title>
    <link rel="icon" type="/image/png" sizes="16x16" href="../img/favicon-16x16.png">
    <link rel="icon" type="/image/png" sizes="32x32" href="../img/favicon-32x32.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/sdex.css"> 
    <script src="../js/icons.js"></script>
    <script src="../js/chart.min.js"></script>
</head>
<body>
    <div class="row" style="padding: 5px;">
        <div class="col-3 col-sm-3"><span class="fas fa-arrow-circle-left back arrow" onclick="go_back()"></span></div>
        <div class="col-2 col-sm-3">
            <span>
                <svg class="circle-chart" viewbox="0 0 90 90" width="90" height="90">
                    <circle class="circle-chart__background" stroke="#efefef" stroke-width="10" fill="none" cx="45" cy="45" r="40" />
                    <circle class="circle-chart__circle" stroke="#370393" stroke-width="10" stroke-dasharray="63, 251" stroke-linecap="round" fill="none" cx="45" cy="45" r="40" />
                    <g>
                        <text class="circle-chart__percent" x="45" y="45" alignment-baseline="central" text-anchor="middle" font-size="48" id="grade">&#8734;️</text>
                    </g>
                </svg>
            </span>
        </div>
        <div class="col-2 col-sm-3">
            <span>
                <svg class="circle-chart" viewbox="0 0 90 90" width="90" height="90">
                    <circle class="circle-chart__background" stroke="#efefef" stroke-width="10" fill="none" cx="45" cy="45" r="40" />
                    <circle class="circle-chart__circle" stroke="#0086f5" stroke-width="10" stroke-dasharray="63, 251" stroke-linecap="round" fill="none" cx="45" cy="45" r="40" />
                    <g>
                        <text class="circle-chart__percent_2" x="45" y="42" alignment-baseline="central" text-anchor="middle" font-size="30" id="raroc"></text>
                        <text class="circle-chart__subline" x="45" y="62" alignment-baseline="central" text-anchor="middle" font-size="12">ROE</text>
                    </g>
                </svg>
            </span>
        </div>
        <div class="col-4 col-sm-3"><span class="fas fa-arrow-circle-right forward arrow" onclick="go_forward()"></span></div>
    </div>
    <form id="IQform">
<?php
    echo "\t<input type=\"hidden\" id=\"user_id\" name=\"user_id\" value=\"" . $_SESSION["id"] . "\">";
    echo "\t<input type=\"hidden\" id=\"session_id\" name=\"session_id\" value=\"" . session_id() . "\">";
?>
    <div id="tab1" class="tabcontent" style="display: block;">
        <h3>Starting point</h3>
        <div class="row">
            <div class="col-6 col-sm-12">
                <fieldset class="form-group">
                    <label class="form-label" for="type">Loan type:</label>
                    <select class="form-control" id="type" name="type" autofocus="true">
<?php
    foreach($types as $option_key => $array) {
        if (isset($array["name"])) {
	        echo "\t\t\t\t\t\t<option value=\"$option_key\">" . $array["name"] . "</option>\n";
        } else {
            echo "\t\t\t\t\t\t<option value=\"$option_key\"></option>\n";
        }
    }
?>
                    </select>
              </fieldset>
            </div>
		
            <div class="col-6 col-sm-12">
                <fieldset class="form-group">
                    <label class="form-label" for="principal">Request:</label>
                    <input type="text" class="form-control" name="request" id="request" placeholder="Dollars" autocomplete="off" required>
                </fieldset>
            </div>
        </div>
  		<div class="row">
  		    <div class="col-6 col-sm-12">
                <fieldset class="form-group">
                    <label class="form-label" for="term">Contractual term:</label>
                    <input type="text" class="form-control" name="periods" id="periods" placeholder="Months" autocomplete="off" required>
                </fieldset>
            </div>
			<div class="col-6 col-sm-12">
                <fieldset class="form-group">
                    <label class="form-label" for="amort">Amortization:</label>
                    <input type="text" class="form-control" name="amortization" id="amortization" placeholder="Months (Optional)" autocomplete="off">
                </fieldset>
            </div>
  		</div>
  		<div class="row">
  		    <div class="col-6 col-sm-12">
                <fieldset class="form-group">
                    <label class="form-label" for="fees">Collected fees:</label>
                    <input type="text" class="form-control" name="fees" id="fees" placeholder="Dollars" autocomplete="off">
                </fieldset>
            </div>
  		    <div class="col-6 col-sm-12">
                <fieldset class="form-group">
                    <label class="form-label" for="rate">Proposed loan rate:</label>
                    <div class="input-group-append">
                        <input type="text" class="form-control" name="rate" id="rate" placeholder="Interest rate" autocomplete="off" required>
  						<button class="btn btn-primary" id="up" type="button"><i class="fas fa-arrow-alt-circle-up"></i></button>
						<button class="btn btn-primary" id="down" type="button"><i class="fas fa-arrow-alt-circle-down"></i></button>
					</div>
                </fieldset>
            </div>
        </div>
        <div class="row">
            <div class="col-6 col-sm-12"></div>
            <div class="col-6 col-sm-12">
                <fieldset class="form-group">
                    <input type="checkbox" name="variable" id="variable" onClick="toggleReprice();">
                    <label for="variable" style="font-size: 16px;">Structure: variable rate</label>
                </fieldset>
                <fieldset class="form-group" id="reprice_group" style="display: none;">
                    <label class="form-label" for="reprice_period">Set months until reprice:</label>
                    <input type="text" class="form-control" name="reprice_period" id="reprice_period" placeholder="Months" autocomplete="off">
                </fieldset>
            </div>    
        </div>
        <div class="row">
            <div class="col-12 col-sm-12">
                <a href="https://bankersiq.com/logout"><i class="fas fa-sign-out-alt" style="color: #0086F5; font-size: 20px;"></i></a>    
            </div>    
        </div>
    </div>
    <div id="tab2" class="tabcontent">
        <h3>Risk profile</h3>
        <div class="row">
            <div class="col-6 col-sm-12">
                <fieldset class="form-group">
                    <label class="form-label" for="loan_to_value">Collateral evaluation:</label>
                    <label class="radio_container">70% Loan-to-Value
                        <input type="radio" name="loan_to_value" id="ltv1" value="70" autocomplete="off">
                        <span class="checkmark"></span>
                    </label>
                    <label class="radio_container">80% Loan-to-Value
                        <input type="radio" name="loan_to_value" id="ltv2" value="80" autocomplete="off" checked>
                        <span class="checkmark"></span>
                    </label>
                    <label class="radio_container">90% Loan-to-Value
                        <input type="radio" name="loan_to_value" id="ltv3" value="90" autocomplete="off">
                        <span class="checkmark"></span>
                    </label>
                    <label class="radio_container">100% Loan-to-Value
                        <input type="radio" name="loan_to_value" id="ltv4" value="100" autocomplete="off">
                        <span class="checkmark"></span>
                    </label>
                    <label class="radio_container">110% Loan-to-Value
                        <input type="radio" name="loan_to_value" id="ltv5" value="110" autocomplete="off">
                        <span class="checkmark"></span>
                    </label>
                    <label class="radio_container">Unsecured
                        <input type="radio" name="loan_to_value" id="ltv6" value="0" autocomplete="off">
                        <span class="checkmark"></span>
                    </label>
                </fieldset>
            </div>
            <div class="col-6 col-sm-12">
                <fieldset class="form-group">
                    <label class="form-label" for="mgmt">Managment evaluation:</label>
                    <label class="radio_container">Weak
                        <input type="radio" name="mgmt" id="option1" value="-1" autocomplete="off">
                        <span class="checkmark"></span>
                    </label>
                    <label class="radio_container">Satisfactory
                        <input type="radio" name="mgmt" id="option2" value="0" autocomplete="off" checked>
                        <span class="checkmark"></span>
                    </label>
                    <label class="radio_container">Strong
                        <input type="radio" name="mgmt" id="option3" value="1" autocomplete="off">
                        <span class="checkmark"></span>
                    </label>
                </fieldset>
                <fieldset class="form-group">
                    <label class="form-label" for="guarantee">Guarantee evaluation:</label>
                    <label class="radio_container">No Guarantee
                        <input type="radio" name="guarantee" id="gntee1" value="0" autocomplete="off" checked>
                        <span class="checkmark"></span>
                    </label>
                    <label class="radio_container">Government
                        <input type="radio" name="guarantee" id="gntee2" value="1" autocomplete="off">
                        <span class="checkmark"></span>
                    </label>
                    <label class="radio_container">Personal
                        <input type="radio" name="guarantee" id="gntee3" value="2" autocomplete="off">
                        <span class="checkmark"></span>
                    </label>
                </fieldset>
            </div>
        </div>
        <div class="row">
            <div class="col-3 col-sm-12">
                <fieldset class="form-group">
                    <label class="form-label" for="dscr">Debt-service coverage ratio:</label>
                    <input type="text" class="form-control" name="dscr" id="dscr" placeholder="Ratio (example: 1.25)" autocomplete="off">
                </fieldset>
            </div>
            <div class="col-9 col-sm-12">
                <fieldset class="form-group">
                    <label class="form-label" for="naics">Industry evaluation:</label>
                    <select name="naics" id="naics" class="form-control">
          	            <option value="">Select NAICS code</option>
                        <option value="11">Agriculture, Forestry, Fishing, and Hunting</option>
                        <option value="21">Mining</option>
                        <option value="22">Utilities</option>
                        <option value="23">Construction - General</option>
                        <option value="31">Manufacturing - Food/Clothing</option>
                        <option value="32">Manufacturing - Wood/Paper/Petroleum/Stone/Chemical/Plastic/Glass</option>
                        <option value="33">Manufacturing - Iron/Steel/Equipment</option>
                        <option value="42">Wholesale Trade</option>
                        <option value="44">Retail Trade - Vehicle/Boat/Home Furnishing/Hardware/Convenience Store/Food/Drugs/Gas/Clothing/Jewelry</option>
                        <option value="45">Retail Trade - Books/Games/Musical/Florists/Office Supplies/Pet Stores/Art Dealers/Vending Machine Operations/Heating Oil Dealers/Mobile Home Dealers/Direct Selling Establishments</option>
                        <option value="48">Transportation and Warehousing</option>
                        <option value="51">Information</option>
                        <option value="52">Finance and Insurance</option>
                        <option value="53">Real Estate and Rental and Leasing</option>
                        <option value="54">Professional, Scientific and Technical Services</option>
                        <option value="56">Administrative and Support and Waste Management and Remediation Services(including janitorial and landscaping)</option>
                        <option value="61">Educational Services</option>
                        <option value="62">Health Care and Social Assistance</option>
                        <option value="71">Arts, Entertainment and Recreation</option>
                        <option value="72">Accomodation and Food Services</option>
                        <option value="81">Other Services (Except Public Administration)</option>
                          <option value="92">Public Administration</option>
                    </select> 
                </fieldset>
            </div>
        </div>
    </div>
    <div id="tab3" class="tabcontent">
        <h3>More options</h3>
        <div class="row">
      	    <div class="col-6">
                <fieldset class="form-group">
                    <input type="checkbox" disabled>
                    <label for="interest_only" style="font-size: 16px;"> Interest only (future feature)</label>
                </fieldset>
                <fieldset class="form-group">
                    <input type="checkbox" disabled>
                    <label for="new_money" style="font-size: 16px;"> New money (future feature)</label>
                </fieldset>
            </div>
    		<div class="col-6">
                <fieldset class="form-group">
                    <label class="form-label" for="average_usage">If line of credit - estimate average usage:</label>
                    <input type="text" class="form-control" name="average_usage" id="average_usage" placeholder="Average Dollars" autocomplete="off">
                </fieldset>
            </div>
      	</div>
      	<div class="row">
      	    <div class="col-6">
                <fieldset class="form-group">
                    <label class="form-label" for="balance"><b><u>New</u></b> Operating deposits - estimated average balance:</label>
                    <input type="text" class="form-control" name="balance" id="balance" placeholder="Average Dollars" autocomplete="off">
                </fieldset>
            </div>
    		<div class="col-6">
                <fieldset class="form-group">
                    <label class="form-label" for="sweep"><b><u>New</u></b> Sweep account deposits - estimated average balance:</label>
                    <input type="text" class="form-control" name="sweep" id="sweep" placeholder="Average Dollars" autocomplete="off">
                    <label class="form-label" for="sweep_rate">Sweep interest rate:</label>
                    <input type="text" class="form-control" name="sweep_rate" id="sweep_rate" placeholder="Interest rate" autocomplete="off">
                </fieldset>
            </div>
      	</div>
    </div>
    <input type="hidden" name="include_schedule" value="1">
    <input type="hidden" id="report_type" value="0">
    </form>
    <div id="tab4" class="tabcontent">
        <h3>Results</h3>
        <div class="row">
            <div class="col-6 col-sm-12">
                <div style="padding-bottom: 0.5em; font-size: 14px; font-weight: 600;">Deal Value</div>
                <div class="progress-bar-whole">
                    <span class="bar-step" style=" width: 4em; background-color: #ff3333; border-top-left-radius: 5px;
            border-bottom-left-radius: 5px;">
                        warn
                    </span>
                    <span class="bar-step" style="width: 5em; background-color: #ffd700;">
                        under
                    </span>
                    <span class="bar-step" style="width: 12em; background-color: #85bb65;">
                        good
                    </span>
                    <span class="bar-step" style="width: 3em; background-color: #ffe662; border-bottom-right-radius: 5px;
            border-top-right-radius: 5px;">
                        over
                    </span>
                </div>
                <div id="indicator" class="indicator-div">
                      <span class="indicator-line"></span>
              	</div>
            </div>
            <div class="col-6 col-sm-12">
                <div style="padding: 0.5em 0em; font-size: 14px; font-weight: 600;"><img src="../img/favicon-32x32.png"> Deal Guidance</div>
                <ul>
                    <li class="iq-item"><i class="fas fa-chevron-right bullet"></i> Increase initial rate to <span id="guidance_rate"></span></li>
                    <li class="iq-item"><i class="fas fa-chevron-right bullet"></i> Add <span id="guidance_fees"></span> to the initial fees</li>
                    <li class="iq-item"><i class="fas fa-chevron-right bullet"></i> Add  <span id="guidance_deposits"></span> new commerical deposits</li>
                    <li class="iq-item"><i class="fas fa-chevron-right bullet"></i> Lower LTV to <span id="guidance_ltv"></span></li>
                    <li class="iq-item"><i class="fas fa-chevron-right bullet"></i> Change loan term to <span id="guidance_maturity"></span> months</li>
                    <li class="iq-item"><i class="fas fa-chevron-right bullet"></i> Set reprice to <span id="guidance_reprice"></span> months</li>
                    <li class="iq-item"><i class="fas fa-chevron-right bullet"></i> </span>Consolidate <span id="guidance_consolidate"></span> loan balances from competitor</li>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
    	        <table class="table">
    				<tr>
    					<th>Type</th><td id="kind"></td>
    					<th>Principal</th><td id="principal"></td>
    				</tr>
                    <tr>
                        <th>Average</th><td id="average_outstanding"></td>
    				    <th>Rate</th><td id="report_rate"></td>
    				</tr>
    				<tr>
                        <th>Fees</th><td id="non_interest_income"></td>
    					<th>Term (months)</th><td id="report_periods"></td>
    				</tr>
                    <tr>
                        <th>Amortization</th><td id="report_amortization"></td>
                        <th>Fixed for (months)</th><td id="reprice_period"></td>
    				</tr>
                    <tr>
                        <th>Interest</th><td id="interest_income"></td>
        				<th></th><td></td>
    				</tr>
    				<tr>
                        <th>Funds rate</th><td id="cost_of_funds_rate"></td>
        				<th>Net income (yr)</th><td id="net_income"></td>
    				</tr>
                    <tr>
    					<th>Reserve rate</th><td id="loan_loss_rate"></td>
                        <th>Payment</th><td id="payment"></td>
    				</tr>
                    <tr>
    					<th></th><td>&nbsp;</td>
                        <th></th><td id="date"></td>
    				</tr>
      			</table>
    		</div>
        </div>
    </div>
    <div id="tab5" class="tabcontent">
        <h3 class="overline">Smart Proposal</h3>
        <div class="row">
            <div class="col-12">
                <button class="btn-primary" onClick="openModal();"><i class="fas fa-plus"></i></button>
            </div>
            <div class="col-12" id="IQchartDiv">
                <canvas id="IQchart" height="100"></canvas>
            </div>
        </div>
        <div class="row">
            <div class="col-2 col-sm-6">
                Interest Expense
            </div>
            <div class="col-7 col-sm-12">
                <div class="progress-bar-whole" style="border-style: solid; border: 4px solid rgb(134, 244, 0); border-radius: 5px;">
                    <span id="bar_proposed_sched" class="bar-compare" style="width: 40%; background-color: rgb(49, 162, 255);"></span>
                    <span id="bar_proposed_proj" class="bar-compare" style="width: 10%; background-color: rgb(135, 180, 217); border-top-right-radius: 5px;
border-bottom-right-radius: 5px; "></span>
                </div>
                <div id="div_comp_proj" class="progress-bar-whole" style="display: none;">
                    <span id="bar_comp_proj" class="bar-compare" style="width: 100%; background-color: rgb(186, 197, 173, .6); border-top-right-radius: 5px;
    border-bottom-right-radius: 5px; margin-top: .3em;"></span>
                </div>
            </div>
            <div class="col-3 col-sm-6 pl-1">
                Savings <span id="interest_save"></span>
            </div>
        </div>
        <div class="row">
            <div class="col-2 col-sm-6">
                Monthly Payment
            </div>
            <div class="col-7 col-sm-12">
                <div class="progress-bar-whole">
                    <span id="bar_proposed_payment" class="bar-step" style="width: 40%; background-color: rgb(49, 162, 255); border-top-left-radius: 7px; border-bottom-left-radius: 7px; border-style: solid; border: 4px solid rgb(134, 244, 0); height: 2.1em;"></span>
                    <span id="bar_comp_payment" class="bar-step" style="width: 60%; background-color: rgb(186, 197, 173, .6); border-top-right-radius: 7px; border-bottom-right-radius: 7px; border-style: solid; border: 3px solid #fff; height: 2.1em; display: none;"></span>
                </div>
            </div>
            <div class="col-3 col-sm-6 pl-1">
                Payment Reduction <span id="payment_reduce"></span>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <div class="wrapper">
                    <div class="top" style="bottom: 7.2em; left: 40px; height: 3em;"></div>
                    <div class="label" style="bottom: 7.2em; left: 70px; height: 3em;">2,000 Savings</div>
                    <div class="line" style="bottom: 7em;"></div>
                    <div class="base" style="left: 0px; height: 7em;"></div>
                    <div class="base" style="left: 40px; height: 7em;"></div>
                </div>
            </div>
        </div>
        <div id="thinkableModal" class="modal">
            <div class="modal-content" >
                <div id="modal-close" class="close" title="Close" onClick="closeModal();">&times;</div>
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div id="modal_title" class="modal-title">
                            Make a Customer, not the sale
                        </div>
                    </div>
                </div>
                <form id="optionsForm">  
                    <div class="row">
                        <div class="col-4">
                            <label class="form-label" for="rate">Rate:</label>
                            <input type="text" class="form-control" name="rate" id="optionsRate" placeholder="Interest rate" autocomplete="off">
                        </div>
                        <div class="col-4">
                            <label class="form-label" for="payment">Payment:</label>
                            <input type="text" class="form-control" name="payment" id="optionsPayment" placeholder="Dollars" autocomplete="off">
                        </div>
                        <div class="col-4">
                            <label class="form-label" for="term">Original term:</label>
                            <input type="text" class="form-control" name="periods" id="optionsPeriods" placeholder="Months" autocomplete="off">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <label class="form-label" for="original">Original request:</label>
                            <input type="text" class="form-control" name="original" id="optionsOriginal" placeholder="Dollars" autocomplete="off">
                        </div>
                        <div class="col-4">
                            <label class="form-label" for="months_early">Months to payoff:</label>
                            <input type="text" class="form-control" name="months_early" id="optionsEarly" placeholder="Months" autocomplete="off">
                        </div>
                        <div class="col-4">
                            <label class="form-label" for="months_early">Average Outstanding(line):</label>
                            <input type="text" class="form-control" name="avg_outstanding" id="optionsAverage" placeholder="Dollars" autocomplete="off">
                        </div>
                    </div>
                </form>
            </div>
        </div>
  	</div>
    <script src="js/sdex.js"></script>
</body>
</html> 
