<?php
require_once "../utils.php";
LoadSplitApplyCombine();
LoadParser();

$query = "
SELECT region, sum(ppc) as revenue
FROM my_table
WHERE region <> 'North'
AND country = 'us'
";

$tokens     = (new Chapter2\Parser\Tokenizer())->run($query);
$lexTree    = (new Chapter2\Parser\BeyondSQLSACLexer())->lex($tokens);
$sacQuery   = (new Chapter2\Parser\BeyondSQLToSACInterpreter())->interpret($lexTree);
pp($sacQuery, 1);


