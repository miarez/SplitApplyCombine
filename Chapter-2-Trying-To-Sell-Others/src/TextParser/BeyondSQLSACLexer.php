<?php
namespace Chapter2\Parser;

class BeyondSQLSACLexer {

    private CONST FILTER_BASE               = "SplitApplyCombine\Filter\\";
    private CONST AGGREGATION_FUNCTION_BASE = "SplitApplyCombine\Aggregation\\";

    private CONST OPERATOR_MAPPING = [
            '='         => "Eq",
            '>'         => "Gt",
            '>='        => "Gte",
            'IN'        => "In",
            '<'         => "Lt",
            '<='        => "Lte",
            '<>'        => "Neq",
            'NOT IN'    => "Nin",
        ];

    public function __construct()
    {
    }


    public function lex(
        array $tokens
    ) : array
    {

        $out = [];
        $hold = self::splitArrayByDelimiter($tokens, "METRIC", true);


        $baseSection        = array_shift($hold);
        $baseSectionTypes   = $this->splitTokensByClause($baseSection);


        $out["WHERE"]       =  self::extractWhereClauses($baseSectionTypes["WHERE"]);

        $out["SELECT"]      =  self::extractSelectClause($baseSectionTypes["SELECT"]);


        $out["METRICS"] = [];
        foreach($hold as $sectionIndex=>$sectionTokens)
        {
            $tokensByClause                             = $this->splitTokensByClause($sectionTokens);
            $metricName                                 = str_replace(["(", ")"], "", $tokensByClause['METRIC'][0]);
            $out["METRICS"][$metricName]["SELECT"]      = self::extractSelectClause($tokensByClause["SELECT"]);
            $out["METRICS"][$metricName]["WHERE"]       = self::extractWhereClauses($tokensByClause["WHERE"]);
            $out["METRICS"][$metricName]["GROUP"]       = self::extractGroupByClause($tokensByClause["GROUP"]);
        }
        return $out;
    }


    private static function splitArrayByDelimiter(
        array $tokens,
        string $delimiter,
        bool $keepDelimiter = false
    ) : array
    {
        $hold       = [];
        $clauses    = 0;
        foreach($tokens as $token)
        {
            if(trim(strtoupper($token), " ") === $delimiter) {
                $clauses++;
                if($keepDelimiter !== true){
                    continue;
                }
            }
            $hold[$clauses][] = $token;
        }
        return $hold;
    }



    private static function extractSelectClause(
        $selectTokens
    )
    {
        if(empty($selectTokens)) return [];
        $hold = self::splitArrayByDelimiter($selectTokens, ",");
        foreach($hold as $clause)
        {
            foreach($clause as $key=>$value)
            {
                $clause[$key] = trim($value, "'\"");
                if(self::isEnclosedWithinArray($value)){
                    $clause[$key] = explode(",",trim(trim($value, "("), ")"));
                }
            }

            if(isset($clause[1])){
                # todo fucked shit up here maybe?
                $ALIAS = "{$clause[0]}({$clause[1][0]})";
            } else {
                $ALIAS = "{$clause[0]}";
            }
            if(isset($clause[2]) && strtolower($clause[2]) == "as" && !empty($clause[3]))
            {
                $ALIAS = $clause[3];
            }

            if(in_array(strtoupper($clause[0]), ["COUNT", "SUM", "AVG", "DISTINCT", "LAST"]))
            {
                $tokens[] = [
                    "AGG_FUNCTION"   => self::AGGREGATION_FUNCTION_BASE.strtoupper($clause[0]),
                    "COL_REF"        => ($clause[1][0] === "*") ? "_id" : $clause[1][0],
                    "ALIAS"          => $ALIAS,
                ];
            } else {
                $tokens[] = [
                    "COL_REF"        => $clause[0],
                ];
            }

        }
        return $tokens;
    }



    private static function extractGroupByClause(
        $groupByTokens
    )
    {
        if(empty($groupByTokens)) return [];
        $tokens = [];
        foreach($groupByTokens as $token)
        {
            if(trim($token, "'\"") === ",") continue;
            if(trim($token, "'\"") === "\n") continue;
            $tokens[] = $token;
        }
        return $tokens;
    }


    private static function extractWhereClauses(
        $whereTokens
    ) : array
    {
        if(empty($whereTokens)) return [];
        $tokens  = [];

        $hold = self::splitArrayByDelimiter($whereTokens, "AND");

        foreach($hold as $whereClause)
        {
            foreach($whereClause as $key=>$value)
            {
                $whereClause[$key] = trim($value, "'\"");
                if(self::isEnclosedWithinArray($value)){
                    $subArray = [];
                    foreach(explode(",",trim(trim($value, "("), ")")) as $subValue)
                    {
                        $subArray[] = str_replace(["'", '"', ' '], "", $subValue);
                    }
                    $whereClause[$key] = $subArray;
                }
            }


            if(strtoupper($whereClause[1]) === "NOT" && strtoupper($whereClause[2]) === "IN"){
                $whereClause[1] = "NOT IN";
                $whereClause[2] = $whereClause[3];
                unset($whereClause[3]);
            }


            $tokens[] = [
                "COL_REF"   => $whereClause[0],
                "OPERATOR"  => self::FILTER_BASE.self::OPERATOR_MAPPING[strtoupper($whereClause[1])],
                "CONSTANT"  => $whereClause[2],
            ];
        }
        return $tokens;
    }


    private function splitTokensByClause(
        array $tokens
    ) : array
    {
        $currentTokenCategory   = "";
        $previousTokenCategory  = "";
        $hold                   = [];

        foreach($tokens as $tokenNumber=>$token)
        {
            $token  = $tokens[$tokenNumber];
            $trim   = trim($token); # this removes also \n and \t!
            $upper  = strtoupper($trim);
            switch ($upper) {
                /* Tokens that get their own sections. These keywords have subclauses. */
                case 'METRIC':
                case 'SELECT':
                case 'ORDER':
                case 'LIMIT':
                case 'GROUP':
                case 'WHERE':
                    $currentTokenCategory = $upper;
                    break;
                case 'BY':
                    continue 2;
                    break;
                case 'AS':
                    break;
                case '':
                case ',':
                case ';':
                    break;
                default:
                    break;
            }


            # primary loop to re-assemble everything into the correct order
            # essentially keep adding things to the prior category as long the current category is the same as the previous
            if ($currentTokenCategory !== "" && ($previousTokenCategory === $currentTokenCategory))
            {
                $hold[$currentTokenCategory][] = $token;
            }

            # to keep the loop above working, set the previous category to the current category
            $previousTokenCategory = $currentTokenCategory;
        }


        return $hold;
    }


    private static function isEnclosedWithinArray(
        string $token
    ) : bool
    {
        return (
        ($token[0] === "(" && substr($token, -1) === ")")
        );
    }





}