<?php
namespace Chapter2\SplitApplyCombine\Expression;

class Multiply extends Expression {

    public function express(
        int $value
    ) : int
    {
        return $value * $this->operand;
    }

}