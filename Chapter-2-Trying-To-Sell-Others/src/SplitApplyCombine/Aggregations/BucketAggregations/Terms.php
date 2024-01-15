<?php

namespace Chapter2\SplitApplyCombine\Aggregation;

class Terms extends BucketAggregation {

    public function __construct(
        array $fields,
        string $alias = NULL
    )
    {

        parent::__construct($fields, $alias);
        $this->aggregations = (Object) [];
    }

}


