<?php
date_default_timezone_set("PRC");
error_reporting(E_ALL);
$fileContent = array();
$anotherFileContent = array();

$dirPrefix = ".";

$weekReportDir= $dirPrefix . "markdownMerge/files/";

$weekReportDir2=$dirPrefix . "markdownMerge/files2/";
$group = array(
	"项目组A" => array("文档名A1","文档名A2"),
	"项目组B" => array("文档名B1","文档名B2")
);
define('LOG_LEVEL',1);//debug 0
include_once "titleMerge.php";
include_once "fileNameMerge.php";
include_once "util.php";

$newLine = isset($_SERVER['QUERY_STRING']) ? "<br />" : "\n";
$dd = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : NULL;
if (!$dd) {
	$dd = isset($_REQUEST['date']) ? $_REQUEST['date'] : NULL;
}

function readAllFile(&$fileContent, $weekReportDir) {
	global $group,$dd;
	
	info($weekReportDir);
	if (!is_dir($weekReportDir)) {
		info("目录不存在");
		return;
	}
	if ($dh=opendir($weekReportDir)) {
		while(($file = readdir($dh))!==false) {
			if ($file != '.' && $file != '..' && strpos($file,"汇总.md") === false) {
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
function renameKey(&$item, $key, $renameMap) {
	if (array_key_exists($item['rawTitle'], $renameMap)) {
		$item['title'] = str_replace($item['rawTitle'],$renameMap[$item['rawTitle']],$item['title']);
	}
}

function mergeReportByTitle() {
	global $groupFormatContent,$weekReportDir,$outArray;
	$reportSummary = "";
	foreach($groupFormatContent as $groupName => $userTitleContent) {
		$reportSummary .= "# {$groupName}\n";

		$outArray = array();
		$initKey = key($userTitleContent);
		array_walk($userTitleContent[$initKey],'extractTitle',$initKey);
		info('如上---------' . $initKey); //initKey为group小分组里第一个碰到的成员
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
/* 当需要按天来读取合并目录时，可开启这一段
$date = getDateInfo($dd);
$weekReportDir=$weekReportDir.$date['Y'].'/'.$date['m'].'/'.$date['week'].'/';
*/
readAllFile($fileContent,$weekReportDir);
if (empty($fileContent)) {
	info("待合并内容为空");
	die;
}

/* 读取另一个目录的结构文档
$date7DaysBefore= getDateInfo($dd,-7);
$lastWeekReportDir=$weekReportDir.$date7DaysBefore['Y'].'/'.$date7DaysBefore['m'].'/'.$date7DaysBefore['week'].'/';
readAllFile($anotherFileContent,$lastWeekReportDir);
*/
readAllFile($anotherFileContent,$weekReportDir2);

$groupFormatContent = array();
$anotherGroupFormatContent = array();
foreach($group as $groupName => $nameArray) {
	//只提取另一个目录的部分标题下的内容
	$anotherUserTitleContent = formatGroupTitleUserContent($anotherFileContent,$nameArray,array("## 下周计划"));
	//标题更名
	foreach ($anotherUserTitleContent as $fileName => &$titleContentArr) {
		array_walk($titleContentArr, 'renameKey', array("## 下周计划" => "## 上周计划"));
	}
	$anotherGroupFormatContent[$groupName]=$anotherUserTitleContent;
}

foreach($group as $groupName => $nameArray) {
	$userTitleContent = formatGroupTitleUserContent($fileContent,$nameArray);
	//目录内容合并
	$groupFormatContent[$groupName]=array_merge_recursive($anotherGroupFormatContent[$groupName], $userTitleContent);
}

mergeReportByTitle();
//mergeOneByFileName();
