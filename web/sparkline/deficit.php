<?php

# build sparkline using standard flow: construct, set, render, output

/*
Instantiate appropriate Sparkline subclass
Load data, set parameters, all Set* calls
Render
  convert coordinates
  calculate image size
  create image handle
  set colors
  fill background
  draw graph
  draw features
Optionally call Draw* functions
Output(File)
*/

# $data = array(1=>10, 2=>5, 3=>3.33333, 4=>2.5, 5=>2, 6=>1.66666, 7=>10/7, 8=>10/8, 9=>10/9, 10=>1);

/* require_once('Sparkline_Bar.php');
$sparkline = new Sparkline_Bar();
$sparkline->SetDebugLevel(DEBUG_NONE); # $sparkline->SetDebugLevel(DEBUG_ERROR | DEBUG_WARNING | DEBUG_STATS | DEBUG_CALLS, '../log.txt');
$sparkline->SetBarWidth(4);
$sparkline->SetBarSpacing(2);
foreach ($data as $k=>$v) {
	if ($v<3) {
		$color = 'red';
	} else {
		$color = 'black';
	}
	$sparkline->SetData($k, $v, $color);
}
$sparkline->Render(32); // height only for Sparkline_Bar
$sparkline->Output();
*/

require_once('Sparkline_Line.php');
$sparkline = new Sparkline_Line();
$sparkline->SetDebugLevel(DEBUG_NONE); // $sparkline->SetDebugLevel(DEBUG_ERROR | DEBUG_WARNING | DEBUG_STATS | DEBUG_CALLS);
# $sparkline->SetColorHtml('background', $_GET['b']);
# $sparkline->SetColorBackground('background');

$data = array(
0 => 0,
1 => 0,
2 => 1,
3 => 3,
4 => 3,
5 => 4,
6 => 6
);

foreach ($data as $k=>$v) {
	$sparkline->SetData($k, $v);
}

# $sparkline->SetYMin(0);

if ($_GET['m']) {
	$sparkline->RenderResampled($_GET['x'], $_GET['y']);
} else {
	$sparkline->Render($_GET['x'], $_GET['y']);
}
#  $sparkline->SetLineSize(6); // for renderresampled, linesize is on virtual image

$sparkline->Output();

/*
Bar
  function SetBarWidth($value) {
  function SetBarSpacing($value) {
  function SetBarColorDefault($value) {
  function SetBarColorUnderscoreDefault($value) {
  function SetData($x, $y, $color = null, $underscore = false, $series = 1) {
  function SetYMin($value)
  function SetYMax($value)
  function ConvertDataSeries($series, $xBound, $yBound) {
  function CalculateImageWidth() {
  function Render($y) {

Line
  function SetData($x, $y, $series = 1) {
  function SetYMin($value) {
  function SetYMax($value) {
  function ConvertDataSeries($series, $xBound, $yBound) {
  function Render($x, $y) {
  function RenderResampled($x, $y) {

Global
  function Init($x, $y) {
  function SetColor($name, $r, $g, $b) {
  function SetColorHandle($name, $handle) {
  function SetColorHex($name, $r, $g, $b) {
  function SetColorHtml($name, $rgb) {
  function SetColorBackground($name) {
  function GetColor($name) {
  function GetColorHandle($name) {
  function SetColorDefaults() {
  function SetLineSize($size) {
  function GetLineSize() {
  function CreateImageHandle($x, $y) {
  function DrawBackground($handle = false) {
  function DrawColorAllocate($color, $handle = false) {
  function DrawFill($x, $y, $color, $handle = false) {
  function DrawLine($x1, $y1, $x2, $y2, $color, $thickness = 1, $handle = false) {
  function DrawPoint($x, $y, $color, $handle = false) {
  function DrawRectangle($x1, $y1, $x2, $y2, $color, $handle = false) {
  function DrawRectangleFilled($x1, $y1, $x2, $y2, $color, $handle = false) {
  function DrawCircleFilled($x, $y, $radius, $color, $handle = false) {
  function DrawText($string, $x, $y, $color, $font = 1, $handle = false) {
  function DrawImageCopyResampled($dhandle, $shandle, $dx, $dy, $sx, $sy, $dw, $dh, $sw, $sh) {
  function CyGdToSl($y, $handle) {
  function GetGraphWidth() {
  function GetGraphHeight() {
  function GetWidth() {
  function GetHeight() {
  function Output($file = '') {
  function OutputToFile($file) {
*/
?>