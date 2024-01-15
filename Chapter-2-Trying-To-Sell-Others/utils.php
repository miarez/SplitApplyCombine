<?php


function pp(
    $a,
    int     $exit   =0,
    string  $label  =''
) : void
{
    echo "<PRE>";
    if($label) echo "<h5>$label</h5>";
    if($label) echo "<title>$label</title>";
    echo "<pre>";
    print_r($a);
    echo '</pre>';
    if($exit) exit();
}

function LoadSplitApplyCombine() : void
{
    $includes = ["Core/Builder.php", "Core/Execute.php", "Aggregations/Aggregation.php", "Aggregations/BucketAggregations/BucketAggregation.php", "Aggregations/BucketAggregations/Term.php", "Aggregations/BucketAggregations/Terms.php","Aggregations/BucketAggregations/DateHistogram.php", "Aggregations/MetricAggregations/MetricAggregation.php", "Aggregations/MetricAggregations/Avg.php", "Aggregations/MetricAggregations/Count.php", "Aggregations/MetricAggregations/Distinct.php", "Aggregations/MetricAggregations/Last.php", "Aggregations/MetricAggregations/Sum.php", "Filters/Filter.php", "Filters/Term.php", "Filters/Terms.php", "Filters/Eq.php", "Filters/Neq.php", "Filters/Lt.php", "Filters/Lte.php", "Filters/Gt.php", "Filters/Gte.php", "Filters/In.php","Filters/Nin.php", "Expressions/Expression.php", "Expressions/Divide.php", "Expressions/Multiply.php", "Expressions/Add.php", "Expressions/Subtract.php"];
    foreach($includes as $include)
    {

        require_once "src/SplitApplyCombine/$include";
    }
}
function LoadParser() : void
{
    $includes = ["BeyondSQLSACLexer.php", "BeyondSQLToSACInterpreter.php", "Tokenizer.php"];
    foreach($includes as $include)
    {

        require_once "src/TextParser/$include";
    }
}

function load_demo_data() : array
{
    return [
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

}
