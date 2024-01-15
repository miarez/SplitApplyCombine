<?php
error_reporting(E_ERROR);
require_once "../utils.php";
LoadSplitApplyCombine();
LoadParser();

$query = "
SELECT count(id) as total
FROM my_table
WHERE region = 'North'
";

$tokens     = (new Chapter2\Parser\Tokenizer())->run($query);
$lexTree    = (new Chapter2\Parser\BeyondSQLSACLexer())->lex($tokens);
$sacQuery   = (new Chapter2\Parser\BeyondSQLToSACInterpreter())->interpret($lexTree);
$data       = (new Chapter2\SplitApplyCombine\Core\Execute(load_demo_data(), $sacQuery))->calculate();

pp($data, 1);

