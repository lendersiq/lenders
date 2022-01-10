<?php
    $types = array (
        array(),
        array(
            "name" => "Commercial",
            "risk_weight" => 1,
            "origin_weight" => 1,
            "origin_m" => 0.0025,
            "service_weight" => 1
        ),
        array(
            "name" => "Commercial Real Estate",
            "risk_weight" => 1,
            "origin_weight" => 1.1,
            "origin_m" => 0.0025,
            "service_weight" => 0.95
        ),
        array(
            "name" => "Residential Real Estate",
            "risk_weight" => 0.5,
            "origin_weight" => 2.65,
            "origin_m" => 0.0015,
            "service_weight" => 0.55
        ),
        array(
            "name" => "Consumer",
            "risk_weight" => 1,
            "origin_weight" => 0.4,
            "origin_m" => 0.000425,
            "service_weight" => 0.30
        ),
        array(
            "name" => "Equipment",
            "risk_weight" => 1,
            "origin_weight" => 0.5,
            "origin_m" => 0.00075,
            "service_weight" => 0.40
        ),
        array(
            "name" => "Home Equity",
            "risk_weight" => 1,
            "origin_weight" => 1.65,
            "origin_m" => 0.00225,
            "service_weight" => 0.45
        ),
        array(
            "name" => "Municipal",
            "risk_weight" => .5,
            "origin_weight" => 0.175,
            "origin_m" => 0.001625,
            "service_weight" => 0.45
        )
    );
    $service_m = 0.0002;
?>