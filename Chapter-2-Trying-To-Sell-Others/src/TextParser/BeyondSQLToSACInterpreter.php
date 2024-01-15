<?php
namespace Chapter2\Parser;

use Chapter2\SplitApplyCombine\Core\Builder;
use Chapter2\SplitApplyCombine\Filter\Filter;
//use Chapter2\SplitApplyCombine\Filter\Gte;

//use Chapter2\SplitApplyCombine\Filter\Gte;
//use Chapter2\SplitApplyCombine\Aggregation;



class BeyondSQLToSACInterpreter
{

    public function __construct()
    {
    }

    function interpret(
        $lexTree
    )
    {
        $sacQuery = (new Builder());

        foreach($lexTree["WHERE"] as $where)
        {
            $class_name = "\\Chapter2\\{$where['OPERATOR']}";
            $sacQuery->bindFilter(
                (new $class_name($where["COL_REF"], $where["CONSTANT"]))
            );
        }


        foreach($lexTree["SELECT"] as $select)
        {

            if(isset($select["AGG_FUNCTION"]))
            {
                $class_name = "\\Chapter2\\{$select['AGG_FUNCTION']}";

                $sacQuery->bindAggregation(
                    (new $class_name($select["COL_REF"], $select["ALIAS"]))
                );
            } else {
                $sacQuery->bindColumnSelector(
                    [$select["COL_REF"]]
                );
            }
        }


        foreach($lexTree["METRICS"] as $metricAlias=>$metric)
        {
            if($metric["GROUP"])
            {
                $bucketAggregation = (new Aggregation\Terms($metric["GROUP"], $metricAlias));

                foreach($metric["SELECT"] as $select)
                {
                    $bucketAggregation->bindAggregation((new $select["AGG_FUNCTION"]($select["COL_REF"], $select["ALIAS"])));
                }

                foreach($metric["WHERE"] as $where)
                {
                    $bucketAggregation->bindFilter((new $where["OPERATOR"]($where["COL_REF"], $where["CONSTANT"])));
                }

                $sacQuery->bindAggregation(
                    $bucketAggregation
                );
            } else {

                foreach($metric["SELECT"] as $select)
                {
                    $aggFunc = (new $select["AGG_FUNCTION"]($select["COL_REF"], $select["ALIAS"]));
                }
                $sacQuery->bindAggregation(
                    $aggFunc
                );

            }
        };
        return $sacQuery;
    }

}