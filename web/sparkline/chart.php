<?php

$_GET['y'] = 4;

$m  = date('n') - 1;
$d  = date('d');
$y2 = date('Y');
$y1 = $y2 - $_GET['y'];

$url = "http://ichart.finance.yahoo.com/table.csv?s=MSFT&a=$m&b=$d&c=$y1&d=$m&e=$d&f=$y2&g=d&ignore=.csv";
if (!$data = @file($url)) {
  die('error fetching stock data; verify ticker symbol');
}

require_once('Sparkline_Line.php');
$sparkline = new Sparkline_Line();
$sparkline->SetDebugLevel(DEBUG_NONE);

$data = array_reverse($data);
$i = 0;
while (list(, $v) = each($data)) {
  $elements = explode(',', $v);
  if (ereg('^[0-9\.]+$', trim($elements[6]))) {
    $sparkline->SetData($i, $elements[6]);
    $i++;
  }
}

$sparkline->SetYMin(0);

if (isset($_GET['m']) &&
    $_GET['m'] == '0') {
  $sparkline->Render(100, 15);
} else {
  $sparkline->SetLineSize(6);
  $sparkline->RenderResampled(100, 15);
}

$sparkline->Output();

?>