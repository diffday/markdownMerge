<?php
if (!defined('LOG_LEVEL')) {
	define('LOG_LEVEL', 1); //debug=0
}

if (!defined('NEW_LINE')) {
	$newLine = isset($_SERVER['QUERY_STRING']) ? "<br />" : "\n";
	define('NEW_LINE', $newLine);
}
if (!defined('Trim_Quotation')) {
	define('Trim_Quotation', TRUE);
}

function getInvokeStrace() {
	$trc = debug_backtrace();
	$func_stack = array_reverse($trc);
	array_pop($func_stack);
	array_pop($func_stack);
	array_pop($func_stack);
	array_shift($trc);
	array_shift($trc);
	
	return array('trace' => $trc,'inverseTrace' => $func_stack);
}

function doLogPrint($content,$trimQuotation=TRUE) {
	$str = var_export($content,true);
	$str = $trimQuotation ? trim($str, "'") : $str;
	$fun_clue='';
	$trace = getInvokeStrace();
	$traceTop = $trace['trace'][0];
	
	$func_stack = $trace['inverseTrace'];
	foreach($func_stack as $t) {
		$fun_clue .= "[{$t['function']}]";
	}
	//调用行所在文件（天蓝色）
	$file = "\033[36m[{$traceTop['file']}]\033[0m";
	//方法栈（黄色）
	$funcs = "\033[33m{$fun_clue}\033[0m";
	//调用行的行号（蓝色）
	$line = "\033[34m[{$traceTop['line']}]\033[0m";

	$s = '[' . date('H:i:s') . "]{$file}{$funcs}{$line}\t{$str}";
	print("{$s}" . NEW_LINE);
}

function debug($content)
{
	if (LOG_LEVEL > 0) return;
	doLogPrint($content,Trim_Quotation);
}

function info($content)
{
	doLogPrint($content,Trim_Quotation);
}

//封装能力提供日期所在的月内周数
function getDateInfo($dateStr=NULL)
{
	$time = $dateStr ? strtotime($dateStr) : time();
	return getDateInfoFromTime($time);
}

function getDateInfoFromTime($timeStamp) {
	$day = date("d", $timeStamp) - (8- date("w",strtotime(date("Y-m-1 00:00:00",$timeStamp))));
	return array (
		'Y' => date("Y",$timeStamp),
		'm' => ltrim(date('m',$timeStamp),'0'),
		'week' => $day<=0 ? 1 :ceil($day/7)+1
	);
}

function getDiffDateInfo($dateStr, $dayDiff) {
	$time = strtotime($dateStr);
	$resultTime = $time + $dayDiff * 86400;
	return getDateInfoFromTime($resultTime);
}