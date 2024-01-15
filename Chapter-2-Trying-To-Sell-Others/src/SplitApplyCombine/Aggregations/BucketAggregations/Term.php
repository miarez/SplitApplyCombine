<?php

namespace Chapter2\SplitApplyCombine\Aggregation;

class Term extends BucketAggregation {

    public function __construct(
        string $field,
        string $alias = NULL
    )
    {
        parent::__construct([$field], $alias);
        $this->aggregations = (Object) [];
    }

}
