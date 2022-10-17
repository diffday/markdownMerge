<?php
date_default_timezone_set("PRC");
error_reporting(E_ALL);
$fileContent = array();

$weekReportDir="/Users/chenclyde/Documents/GitHub/markdownMerge/files/";
$group = array(
	"项目组A" => array("文档名A1","文档名A2"),
	"项目组B" => array("文档名B1","文档名B2")
);
define('LOG_LEVEL',1);//debug 0
include_once "titleMerge.php";
include_once "fileNameMerge.php";
include_once "util.php";

$newLine = isset($_SERVER['QUERY_STRING']) ? "<br />" : "\n";
//$fileDir = ;
$dd = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : NULL;
if (!$dd) {
	$dd = isset($_REQUEST['date']) ? $_REQUEST['date'] : NULL;
}

function readAllFile() {
	global $fileContent,$group,$weekReportDir,$dd;
	//$date = getDateInfo($dd);
	//$weekReportDir=$weekReportDir.$date['Y'].'/'.$date['m'].'/'.$date['week'].'/';
	info($weekReportDir);
	if ($dh=opendir($weekReportDir)) {
		while(($file = readdir($dh))!==false) {
			if ($file != '.' && $file != '..' && $file != "周报汇总.md") {
				$fContent = file_get_contents($weekReportDir.$file);
				$fileContent[$file] = $fContent;
			}
		}
		closedir($dh);
	}
}

$outArray = array();
function extractTitle($item,$key,$name) {
	global $outArray;
	debug($item['title']);
	$outArray[]=array('title' => $item['title'],'data'=>array($name=>$item['content']));
}

function mergeReportByTitle() {
	global $fileContent,$group,$weekReportDir,$outArray;
	$reportSummary = "";
	foreach($group as $groupName => $nameArray) {
		$reportSummary .= "# {$groupName}\n";
		$userTitleContent = formatGroupTitleUserContent($nameArray);
		$outArray = array();
		$initKey = key($userTitleContent);
		array_walk($userTitleContent[$initKey],'extractTitle',$initKey);
		info('如上---------' . $initKey);
		$titleArray = $outArray;

		$titleUserContent = array();
		foreach($userTitleContent as $name => $titleContentArr) {
			if ($name == $initKey) continue;
			$outArray = array ();
			array_walk($userTitleContent[$name],'extractTitle',$name);
			$b=$outArray;
			info("如上========" . $name);
			mergeTreeArray($titleArray, $b, $name);
		}

		foreach($titleArray as $titleNameContent) {
			$subContent = "";
			foreach($titleNameContent['data'] as $name => $content) {
				$pattern = "/(\S+)/";
				$match = array();
				preg_match_all($pattern,str_replace(array("\n"," ","\t"),"",$content),$match);

				//剔除过短的内容
				if (empty($match[0]) || strlen($match[0][0]) < 10) continue;
				$subContent .= '> ' . $name . "\n\n";
				$subContent .= $content . "\n";
			}
			$showTitle = subStr($titleNameContent['title'],0,strrpos($titleNameContent['title'],"=="));
			$reportSummary .= "{$showTitle}\n";
			$showTitleLevel = explode("_",substr($titleNameContent['title'],strrpos($titleNameContent['title'],"==")+2));
			end($showTitleLevel);
			$showTitleLevel = current($showTitleLevel);
			if (empty($subContent)) continue;
			$reportSummary .= $subContent . "\n";
		}
	}

	$bytesCount = file_put_contents("{$weekReportDir}汇总.md",$reportSummary);
	info("Finish Merge By Title:{$weekReportDir}汇总.md");

}

readAllFile();
mergeReportByTitle();
//mergeOneByFileName();
