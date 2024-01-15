<?php

namespace Chapter2\SplitApplyCombine\Aggregation;

class Sum extends MetricAggregation {

    const OPERATION_DIVIDE = "/";

    public function expression(
        $expression
    ) : self
    {
        $this->expression = $expression;
        return $this;
    }

    public function express(
        int $value
    )
    {
        echo get_class($this->expression);
        pp($this, 1, 'hurrr');
        return $value / $this->operation["operand"];
    }



}
