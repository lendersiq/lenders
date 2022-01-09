const tabcontent = document.getElementsByClassName("tabcontent");
const back = document.getElementsByClassName("back")[0];
const forward = document.getElementsByClassName("forward")[0];
const form = document.getElementById("IQform");
const modal = document.getElementById("thinkableModal");
const up = document.getElementById("up");
const down = document.getElementById("down");
const rate = document.getElementById("rate");
const border = document.getElementsByClassName("progress-border")[0];
const indicator = document.getElementById("indicator");
const options_form = document.getElementById("optionsForm");
const block_canvas = document.getElementById("IQchart");
const div_canvas = document.getElementById("IQchartDiv");
const payment = document.getElementById("payment");
const proposed_interest = document.getElementById("proposed_interest");
const interest_save = document.getElementById("interest_save");
const payment_reduce = document.getElementById("payment_reduce");
const months_early = document.getElementById("months_early");
const bar_proposed_sched = document.getElementById("bar_proposed_sched");
const bar_proposed_proj = document.getElementById("bar_proposed_proj");
const div_comp_proj = document.getElementById("div_comp_proj");
const bar_comp_proj = document.getElementById("bar_comp_proj");
const bar_comp_payment = document.getElementById("bar_comp_payment");
const bar_proposed_payment = document.getElementById("bar_proposed_payment");
const bar_remain = document.getElementById("bar_remain");
const reprice = document.getElementById("reprice");
const reprice_group = document.getElementById("reprice_group");
var sched_interest_global = 0;
var proj_interest_global = 0;
var proposed_sched_global = [];
var proposed_proj_global = [];
var interest_obj_global = '';

var active = 0;
var first_percent = 0;
var remain_percent = 0;
var touchstartX = 0;
var touchendX = 0;
const slider = document.getElementsByTagName("BODY")[0];
var type_id = 0;

function time_out(responseText) {
    var resArr = JSON.parse(responseText);  
    console.log(resArr.director);
    window.location = resArr.director;
}

function closeModal() {
    modal.style.display="none";
}

function openModal() {
    modal.style.display='block';
}

function toggleReprice() {
    if (reprice_group.style.display === "none") {
        reprice_group.style.display = "block";
    } else {
        reprice.value = '';
        reprice_group.style.display = "none";
    }
}

function updateProgress(percentage) {
    percentage = Math.max(0, Math.min(percentage, 100));
    var numerator = 251 * percentage * .01;
    numerator = parseInt(Math.max(0, Math.min(numerator, 251)));
    var sd_string = numerator + ", 251";
    document.getElementsByClassName('circle-chart__circle')[0].setAttribute('stroke-dasharray', sd_string);
    document.getElementsByClassName('circle-chart__circle')[1].setAttribute('stroke-dasharray', sd_string);
}

function render_smart_chart(sched_proposed=[], proj_proposed=[], comp_sched = [], comp_proj = []) {
    proposed_sched_global = sched_proposed.length !== 0 ? sched_proposed : proposed_sched_global;
    proposed_proj_global = proj_proposed.length !== 0 ? proj_proposed : proposed_proj_global;
    var labels = [];
    for (month = 1; month < Math.max(proposed_proj_global.length, proposed_sched_global.length, comp_sched.length, comp_proj.length) + 1; month++) {
        labels.push("Month: " + month);
    }
    const data = {
        labels: labels,
        datasets: [{
            fill: {
                target: 'origin',
                above: 'rgb(49, 162, 255)',
                below: 'rgb(255, 0, 0)'
            },
            label: 'Proposed - scheduled',
            backgroundColor: 'rgb(49, 162, 255)',
            borderColor: '#370393',
            data : proposed_sched_global
        },
        {
            fill: {
                target: 'origin',
                above: 'rgb(135, 180, 217)',
                below: 'rgb(255, 0, 0)' 
            },
            label: 'Proposed - projected',
            backgroundColor: 'rgb(135, 180, 217)',
            borderColor: '#a9a9a9',
            data: proposed_proj_global
        },
        {
            fill: {
                target: 'origin',
                above: 'rgb(134, 244, 0, .6)', 
                below: 'rgb(255, 0, 0)' 
            },
            label: 'Comp - scheduled',
            backgroundColor: 'rgb(134, 244, 0, .6)',
            borderColor: '#a9a9a9',
            data: comp_sched
        },
        {
            fill: {
                target: 'origin',
                above: 'rgb(186, 197, 173, .6)', 
                below: 'rgb(255, 0, 0)' 
            },
            label: 'Comp - projected',
            backgroundColor: 'rgb(186, 197, 173, .6)',
            borderColor: '#a9a9a9',
            data: comp_proj
        }]
    };
    
    const config = {
        type: 'line',
        data: data,
        options: {
        },
    };
    
    delete block_canvas;
    div_canvas.innerHTML = '<canvas id="IQchart" height="100"></canvas>';
    var smart_chart = new Chart(
        document.getElementById('IQchart'),
        config
    );
}

const inputHandler = function(e) {
    get_form();
}

form.addEventListener('change', inputHandler); // for IE8
// Firefox/Edge18-/IE9+ don’t fire on <select><option>

const inputHandler2 = function(e) {
    post_optionsForm();
}
options_form.addEventListener('change', inputHandler2);

function populate(arr) {
    /*
    var base = arr["base"];
    for (let key in base) {
        let value = base[key];
        if (key == 'rate') {
            rate.value = value;
            post_form();
        }
        else {
            if (document.getElementById(key) !== null) {
                document.getElementById(key).innerHTML = value;
            }
        }
    }
    */
    var obs = arr["observations"];
    for (let key in obs) {
        let value = obs[key];
        if (document.getElementById(key) !== null) {
            document.getElementById(key).innerHTML = value;
        }
    }
    
    console.log(arr["iq"]);

    var iq = arr["iq"];
    for (var key in iq) {
        var value = iq[key];
        document.getElementById(key).closest('li').style.display = 'none'; 
        if (document.getElementById(key) !== null) {
            document.getElementById(key).innerHTML = value;
            document.getElementById(key).closest('li').style.display = 'block';
        }
    }
    //@@ document.getElementById("report_principal").innerHTML = arr.observations.principal;
    document.getElementById("report_rate").innerHTML = arr.observations.rate;
    document.getElementById("report_periods").innerHTML = arr.observations.periods;
    document.getElementById("report_amortization").innerHTML = arr.observations.amortization;
    type_id = arr.observations.product;
}

function get_form() {
    if (document.querySelector("#type").value !== '' && document.querySelector("#principal").value !== ''  && document.querySelector("#periods").value !== '' && document.querySelector("#rate").value !== '') { 
        var url;
        if (document.querySelector("#average_usage").value !== '') {
            url = "https://bankersiq.com/v4/line/?";
        } else {
            url = "https://bankersiq.com/v4/loan/?";
        }
        var xhttp = new XMLHttpRequest();
        const queryString = new URLSearchParams(new FormData(form)).toString();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4) {
                if (this.status == 440) {
                    time_out(this.responseText);
                }
                if (this.status == 200) {
                    var resArr = JSON.parse(this.responseText);  
                    populate(resArr);
          	        if (resArr.observations.raroc !== undefined) {
          	            var bar_position = Math.min(parseInt(resArr.observations.raroc), 24);
          	            bar_postion = Math.max(0, bar_position);
          	            indicator.style.paddingLeft = bar_position + "em";
          	            updateProgress(Math.round(bar_position / 20 * 100));
          	        }
                    var schedStream_arr=[];
                    sched_interest_global = 0;
                    for(var key in resArr.proposed_scheduled) {
                        schedStream_arr.push(resArr.proposed_scheduled[key]);
                        sched_interest_global += parseFloat(resArr.proposed_scheduled[key]);
                    }
                    
                    var projStream_arr=[];
                    proj_interest_global = 0;
                    for(var key in resArr.proposed_projected) {
                        projStream_arr.push(resArr.proposed_projected[key]);
                        proj_interest_global += parseFloat(resArr.proposed_projected[key]);
                    }
                    proj_interest_global = proj_interest_global !== 0 ? proj_interest_global - sched_interest_global : 0;
                    
                    var proposed_interest = parseFloat(document.getElementById("interest_income").innerHTML.replace(/,/g, '')) * parseFloat(document.getElementById("periods").innerHTML.replace(/,/g, '')) / 12;
                    bar_proposed_payment.innerHTML = payment.innerHTML;
                    bar_proposed_sched.style.width = parseInt(sched_interest_global / (sched_interest_global + proj_interest_global) * 100) - 2 + "%";
                    bar_proposed_proj.style.width = parseInt(proj_interest_global / (sched_interest_global + proj_interest_global) * 100) + "%";
                    interest_obj_global = proposed_interest.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                    if (parseInt(bar_proposed_proj.style.width.replace(/%/g, '')) > 20 ) {
                        bar_proposed_proj.innerHTML = proposed_interest.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                        bar_proposed_sched.innerHTML = '';
                    } else {
                        bar_proposed_sched.innerHTML = proposed_interest.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                        bar_proposed_proj.innerHTML = '';
                    }
                    render_smart_chart(schedStream_arr, projStream_arr, [], [] );
                }
            }   
        };
        xhttp.open("GET", url + queryString, true);
        xhttp.send();
    }
}

up.onclick = function() {
    if (rate.value !== "") {
        rate.value = parseFloat(rate.value) + 0.125;
        rate.focus();
        get_form();
    }
};

down.onclick = function() {
    if (rate.value !== "") {
        if (parseFloat(rate.value) > 0.125) {
            rate.value = parseFloat(rate.value) - 0.125;
            rate.focus();
            get_form();
        }
    }
};

show_hide_arrows();

function delta (obj_p, obj_c) {
    obj_p = parseFloat(obj_p.replace(/,/g, ''));
    obj_c = parseFloat(obj_c.replace(/,/g, ''));
    var return_string = (obj_c - obj_p).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') + " (" + ((obj_c - obj_p) / obj_c * 100).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') + "%) <span class=\"star-rating\">";
    for (var i = 0; i < parseInt((obj_c - obj_p) / obj_c * 100 / 8) && i < 5; i++) {
        return_string += String.fromCharCode(9734);
    }
    return return_string + "</span>";
}

function percentage (obj_p, obj_c) {
    obj_p = parseFloat(obj_p.replace(/,/g, '').replace(/$/g, ''));
    obj_c = parseFloat(obj_c.replace(/,/g, '').replace(/$/g, ''));
    return (obj_p / (obj_p + obj_c)).toFixed(2);
}

function post_optionsForm() {
    var data = new FormData(options_form);
    var valid_parameters = ["rate", "payment", "periods", "principal", "months_early", "original", "avg_outstanding"];
    var count = 0;
    var url = "";
    for (i = 0; i < options_form.elements.length; i++) {
        if (options_form.elements[i].value !== '' && valid_parameters.includes(options_form.elements[i].name)) {
            url += options_form.elements[i].name + "=" + options_form.elements[i].value + "&";
            count++;
        }
    }
    //@@
    if (document.getElementById("optionsPeriods").value === '')  {
        url += "periods=" + document.getElementById("report_periods").innerHTML + "&";
        count++;
    }
    //@@
    if (document.getElementById("principal").innerHTML !== null)  {
        url += "principal=" + document.getElementById("principal").innerHTML + "&";
        count++
    }
    //@@
        url += "type=" + type_id + "&";
        count++
    
    if (count > 3) {
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4) {
                if (this.status == 440) {
                    time_out(this.responseText);
                }
                if (this.status == 200) {
                    resArr = JSON.parse(this.responseText);
                    //if (keyExists(resArr.director)) {
                    //    //time_out(this.responseText);
                    //}
                    //else {
                        //@@
                        div_comp_proj.style.display ="block";
                        bar_comp_proj.innerHTML = resArr.interest_proj;
                        render_smart_chart([], [], resArr.scheduled, resArr.projected);
                        bar_proposed_sched.style.width = parseInt(sched_interest_global / parseFloat(resArr.interest_proj.replace(/,/g, '').replace(/$/g, '')) * 100) + "%";
                        bar_proposed_proj.style.width = parseInt(proj_interest_global / parseFloat(resArr.interest_proj.replace(/,/g, '').replace(/$/g, '')) * 100) + "%";
                        payment_reduce.innerHTML = delta(payment.innerHTML, resArr.payment);
                        bar_proposed_payment.style.width = parseInt(percentage(payment.innerHTML, resArr.payment) * 100) + "%";
                        bar_comp_payment.style.display ="block";
                        bar_comp_payment.innerHTML = resArr.payment;
                        bar_comp_payment.style.width = 100 - parseInt(percentage(payment.innerHTML, resArr.payment) * 100) + "%";
                        interest_save.innerHTML = delta(interest_obj_global, bar_comp_proj.innerHTML);
                    //}
                }
            }
        }
        url = url.substring(0, url.length - 1);
        xhttp.open("POST", "../v4/loan/");
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send(url);
    }
}

function show_hide_arrows() {
    if (active == tabcontent.length - 1) {
        forward.style.display = "none";
        //@@
        post_optionsForm();
    } else {
        forward.style.display = "block";
    }
    if (active === 0) {
        back.style.display = "none";
    }
    else {
        back.style.display = "block";
    }
}

function go_forward() {
    if (active < tabcontent.length - 1) {
        active++;
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tabcontent[active].style.display = "block";
    }
    show_hide_arrows();
}

function go_back() {
    if (active > 0) {
        active--;
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tabcontent[active].style.display = "block";
    }
    show_hide_arrows();
}

function handleGesture() {
  if (touchendX < touchstartX) go_forward();
  if (touchendX > touchstartX) go_back();
}

slider.addEventListener('touchstart', e => {
  touchstartX = e.changedTouches[0].screenX
})

slider.addEventListener('touchend', e => {
  touchendX = e.changedTouches[0].screenX
  handleGesture()
})


