<?php


namespace Chapter2\SplitApplyCombine\Aggregation;

use Chapter2\SplitApplyCombine\Aggregation\Aggregation;
use Chapter2\SplitApplyCombine\Filter\Filter;

abstract class BucketAggregation extends Aggregation {

    public function __construct(
        array $fields,
        string $alias = NULL
    )
    {
        parent::__construct(implode("|", $fields), $alias);
        $this->fields       = $fields;
        $this->aggregations = (Object) [];
        $this->filters      = [];
    }

    public function bindAggregation(
        Aggregation $aggregation
    ) : self
    {
        $this->aggregations->{$aggregation->alias} = $aggregation;
        return $this;
    }

    public function bindFilter(
        Filter $filter
    ) : self
    {
        $this->filters[] = $filter;
        return $this;
    }

}