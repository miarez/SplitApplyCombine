<?php

namespace Chapter2\SplitApplyCombine\Aggregation;

abstract class Aggregation {

    public function __construct(
        string $field,
        string $alias = NULL
    )
    {
        $this->alias = $alias ?: $field;
    }

}
