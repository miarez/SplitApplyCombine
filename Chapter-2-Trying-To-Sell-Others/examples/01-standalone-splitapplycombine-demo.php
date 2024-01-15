<?php
require_once "../utils.php";
LoadSplitApplyCombine();

use Chapter2\SplitApplyCombine\Aggregation\Count;
use Chapter2\SplitApplyCombine\Aggregation\Sum;
use Chapter2\SplitApplyCombine\Aggregation\Term;
use Chapter2\SplitApplyCombine\Core\Builder;
use Chapter2\SplitApplyCombine\Expression\Divide;
use Chapter2\SplitApplyCombine\Filter\Eq;
use Chapter2\SplitApplyCombine\Filter\In;

$builder = (new Builder())
    ->bindColumnSelector(
        [
            new Count("_id", "total_events"),
            new Sum("ppc", "total_revenue"),
        ]
    )
    ->bindFilter(
        new Eq("status", "premium_client")
    )
    ->bindAggregation(
        (new Term("country"))
            ->bindFilter(
                new In("source", ["email", "organic"])
            )
            ->bindAggregation(
                (new Sum("ppc", "country_total_revenue"))
                    ->expression(
                        new Divide(100)
                    )
            )
    )
;

pp($builder, 1);



