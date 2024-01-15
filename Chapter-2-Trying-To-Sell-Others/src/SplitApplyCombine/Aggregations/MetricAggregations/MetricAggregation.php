<?php

namespace Chapter2\SplitApplyCombine\Aggregation;

abstract class MetricAggregation extends Aggregation {

    public function __construct(
        string $field,
        string $alias = NULL
    )
    {
        parent::__construct($field, $alias);

        if($alias == NULL){
            $type = strtoupper((new \ReflectionClass($this))->getShortName());
            $this->alias = "$type($field)";
        }
        $this->field = $field;
    }

}

