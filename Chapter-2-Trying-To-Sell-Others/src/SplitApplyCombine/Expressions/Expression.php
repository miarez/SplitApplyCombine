<?php
namespace Chapter2\SplitApplyCombine\Expression;

abstract class Expression {

    public function __construct(
        int $operand
    )
    {
        $this->operand = $operand;
    }

}
