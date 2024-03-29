<?php
require_once "../../utils.php";
require_once "SplitApplyCombine.php";

error_reporting(E_ERROR);

use Chapter1\SplitApplyCombine;





$sac = new SplitApplyCombine();
$out = $sac->execute(
    get_transactional_records(),
    ['client_code', 'country', 'region'],
    [
        ['count', 'id', 'total_events', []],
        ['sum', 'revenue', 'click_revenue', [['_event', '=', 'click']]],
        ['sum', 'revenue', 'apply_revenue', [['_event', '=', 'apply']]],
    ]
    , true
    , "client_code"
);

pp($out, 1);



function get_transactional_records() : array
{
    $data = [
        ["id" => 0, "client_code" => 123, "_event" => "click", "country"=>"us", "revenue" => 100, "region" => "North"],
        ["id" => 1, "client_code" => 123, "_event" => "click", "country"=>"us", "revenue" => 90, "region" => "South"],
        ["id" => 2, "client_code" => 123, "_event" => "click", "country"=>"us", "revenue" => 110, "region" => "South"],
        ["id" => 3, "client_code" => 123, "_event" => "apply", "country"=>"us", "revenue" => 10, "region" => "South"],

        ["id" => 4, "client_code" => 987, "_event" => "click", "country"=>"us", "revenue" => 150, "region" => "North"],
        ["id" => 5, "client_code" => 987, "_event" => "click", "country"=>"us", "revenue" => 135, "region" => "North"],
        ["id" => 6, "client_code" => 987, "_event" => "apply", "country"=>"ca", "revenue" => 10, "region" => "East"],

        ["id" => 7, "client_code" => 456, "_event" => "click", "country"=>"us", "revenue" => 10, "region" => "North"],
        ["id" => 8, "client_code" => 456, "_event" => "apply", "country"=>"us", "revenue" => 10, "region" => "West"],
        ["id" => 9, "client_code" => 456, "_event" => "click", "country"=>"us", "revenue" => 10, "region" => "North"],
        ["id" => 10, "client_code" => 456, "_event" => "apply", "country"=>"us", "revenue" => 10, "region" => "South"],
        ["id" => 11, "client_code" => 456, "_event" => "click", "country"=>"us", "revenue" => 10, "region" => "North"],

        ["id" => 12, "client_code" => NULL, "_event" => "click", "country"=>"ca", "revenue" => 200, "region" => "North"],
        ["id" => 13, "client_code" => NULL, "_event" => "click", "country"=>"ca", "revenue" => 520, "region" => "North"],
        ["id" => 14, "client_code" => NULL, "_event" => "click", "country"=>"us", "revenue" => 120, "region" => "North"],
    ];
    usort($data, function ($x, $y) {
        return strcmp($x["client_code"], $y["client_code"]);
    });
    return $data;
}