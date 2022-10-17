<?php
include_once "util.php";
function mergeOneByFileName() {
    global $fileContent,$group,$weekReportDir;
    $reportSummary = "";
    foreach($group as $groupName => $nameArray) {
        $reportSummary .= "# {$groupName}\n";
        foreach($nameArray as $userName) {
            $reportSummary .= "## {$userName}\n";
            if (array_key_exists($userName. ".md",$fileContent)) {
                $reportSummary.=$fileContent[$userName . ".md"] ."\n";
            }
        }
    }
    file_put_contents("$weekReportDir"."文件续接汇总.md",$reportSummary);
    info("Finish Merge By FileName");

}