<?php

namespace Chapter2\SplitApplyCombine\Aggregation;

class DateHistogram extends BucketAggregation {

    const FORMAT_MONTH      = "MONTH";
    const FORMAT_DAY        = "DAY";
    const FORMAT_HOUR       = "HOUR";
    const FORMAT_MINUTE     = "MINUTE";

    const VALID_FORMATS = [
        self::FORMAT_MONTH,
        self::FORMAT_DAY,
        self::FORMAT_HOUR,
        self::FORMAT_MINUTE
    ];

    public function __construct(
        string $field,
        string $alias   = NULL,
        string $format  = self::FORMAT_DAY
    )
    {
        parent::__construct([$field], $alias);
        $this->aggregations = (Object) [];

        $this->format($format);
    }

    public function format(
        string $format  = self::FORMAT_DAY
    ) : self
    {
        $format = strtoupper($format);
        if(!in_array($format, self::VALID_FORMATS)){
            Throw New \UnexpectedValueException("[{$format}] Is Not A Valid DateHistogram Format");
        }
        $this->format = $format;
        return $this;
    }

}
