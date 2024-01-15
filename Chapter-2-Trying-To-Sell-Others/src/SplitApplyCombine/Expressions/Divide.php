<?php
namespace Chapter2\SplitApplyCombine\Expression;

class Divide extends Expression {

    public function express(
        $value
    ) : int
    {
        if($this->operand > 0){
            return $value / $this->operand;
        } else {
            return 0;
        }
    }

}