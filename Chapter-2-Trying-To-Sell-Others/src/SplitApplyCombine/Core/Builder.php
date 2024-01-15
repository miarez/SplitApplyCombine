<?php
namespace Chapter2\SplitApplyCombine\Core;

use Chapter2\SplitApplyCombine\Filter\Filter;
use Chapter2\SplitApplyCombine\Aggregation\Aggregation;

class Builder {

    public function __construct()
    {
        $this->aggregations     = (Object) [];
        $this->selectedColumns  = [];
        $this->filters          = [];
    }

    public function bindColumnSelector(
        array $columnSelectors
    ) : self
    {
        foreach($columnSelectors as $columnSelector)
        {
            $this->selectedColumns[] = $columnSelector;
        }
        return $this;
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