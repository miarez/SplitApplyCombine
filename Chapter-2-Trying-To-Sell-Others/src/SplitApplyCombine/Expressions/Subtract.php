<?php
namespace Chapter2\SplitApplyCombine\Expression;

class Subtract extends Expression {

    public function express(
        int $value
    ) : int
    {
        return $value - $this->operand;
    }

}