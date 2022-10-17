<?php
function simplifyTitle($title) {
    $title_levelClue = explode("==",$title);
    $levelArr = explode("_",$title_levelClue[1]);
    return $title_levelClue[0] . "_" . count($levelArr) . "_" . end($levelArr);
}

function getMergeNodeIndex($a,$b,$bTitle,$parentBTitle, &$index) {
    $max = count($a) - 1;
    for ($i = 0;$i<=$max;++$i) {
        //非精确匹配，可以适配处理标题结构级别相同，但排版位置不同的情况（部分缺失也能容忍）
        if (simplifyTitle($a[$i]['title']) == simplifyTitle($bTitle)) {
        //if ($a[$i]['title'] == $bTitle) {
            if (getParentTitleClue($a,$i) == $parentBTitle) {//强化了约束，避免两者的模版很大差异也能合并
                $index = $i;
                return true;
            }
        }
    }
    return false;
}

function getParentTitleClue($tree,$start) {
    $title_level = explode("==",$tree[$start]['title']);
    $levelClue = explode('_',$title_level[1]);

    $clue = $title_level[0];
    $level = count($levelClue);

    for ($i = $start -1;$i>=0;--$i) {
        $t = explode("==",$tree[$i]['title']);
        $c = explode('_',$t[1]);
        if (count($c) < $level) {
            $clue .= '<--' . $t[0];
            $level = count($c);
        }
    }
    return $clue;
}

function getParentLastChildIndex2() {
    $max = count($tree) - 1;
    for ($i = $start + 1;$i<=$max;++$i) {
        $b = explode("==",$tree[$i]['title']);
        $c = explode("_",$b[1]);
        //当标题目标层级变动，代表已找到该标题范围的尾端
        if (count($c) <= $level) {
            return $i;
        }
    }
    return $max + 1;
}

function getAppendNodeIndex($a,$b,$parentBTitle,&$index) {
    $max = count($a) -1;
    $parentTitleMatch = false;
    for ($i=0;$i<=$max;++$i) {
        $pClue = getParentTitleClue($a,$i);
        if ($pClue == $parentBTitle) {
            $parentTitleMatch = true;
            $title_level = explode("==",$a[$i]['title']);
            $levelClue = explode("_",$title_level[1]);

            $index = getParentLastChildIndex2($a,$i,count($levelClue));
            break;
        }
    }
    if (!$parentTitleMatch) {
        $index = $max + 1;
    }
}

function getParentTitle($b,$start) {
    $b1 = explode("==",$b[$start]['title']);
    $c2 = explode("_",$b1[1]);

    for($i=$start -1;$i>=0;--$i) {
        $t = explode("==",$b[$i]['title']);
        $c = explode("_",$t[1]);
        if (count($c) < count($c2)) {
            return $b[$i]['title'];
        }
    }
    return "";
}

function mergeTreeArray(&$a,$b,$userName) {
    for ($j =0;$j<count($b);++$j) {
        $bPT = getParentTitleClue($b,$j);
        $bclue = implode('<--',array_slice(explode("<--",$bPT),1));
        $bTitle = $b[$j]['title'];

        $index = 0;
        if (getMergeNodeIndex($a,$b,$bTitle,$bPT,$index)) {
            $a[$index]['data'][$userName] = $b[$j]['data'][$userName];
        }else {
            getAppendNodeIndex($a,$b,$bclue,$index);
            array_slice($a,$index,0,array(array('title' => $bTitle,'data'=>array($userName => $b[$j]['data'][$userName]))));
        }
        debug("MERGE:源标题[{$bTitle}]---源父标题线索[{$bPT}]---To标题下标[{$index}]");
    }
    return $a;
}

function getMinLevel($titlePregRaw) {
    $minLevel = 100;
    for ($i =0;$i<sizeof($titlePregRaw);++$i) {
        $rawTitle = $titlePregRaw[$i][0];

        $patternLevel = "/^#+ /";
        preg_match_all($patternLevel,$rawTitle,$match);
        $level = substr_count($match[0][0],"#");
        $minLevel = ($level < $minLevel) ? $level : $minLevel;
    }

    return $minLevel;
}

//为个人周报文档提取标题结构
function formatGroupTitleUserContent($nameArray) {
    global $fileContent,$group,$weekReportDir;
    //info($fileContent);
    $userTitleContent = array();
    foreach($nameArray as $userName) {
        $tieleContent = array();
        $titles = array();
        if (array_key_exists($userName. ".md",$fileContent)) {
            $pattern = "/#+ (.+)/";
            $match = array();
            info('=======' . $userName . '======');
            preg_match_all($pattern,$fileContent[$userName . ".md"],$match,PREG_OFFSET_CAPTURE);
            $rootIndex = 0;
            $clue = array();//目录层级线索
            if (!empty($match)) {
                $lastLevel = 0;
                $titles = $match[0];
                $rawMinLevel = getMinLevel($titles);
                $minLevel = $rawMinLevel < 2 ? 2 : $rawMinLevel;
                for ($i=0;$i<sizeof($titles);++$i) {
                    $rawTitle = $titles[$i][0];
                    $titleLen = strlen($rawTitle);
                    if (strpos($rawTitle,' ') < 2) {
                        $titleLen = $titleLen + (2- strpos($rawTitle,' '));
                    }
                    //保证从第二级title开始
                    $title = str_pad($rawTitle,$titleLen,"#",STR_PAD_LEFT);
                    $patternLevel = "/^#+ /";
                    preg_match_all($patternLevel,$title,$match);
                    $level = substr_count($match[0][0],"#");

                    if ($level == $minLevel) {
                        $diff = $lastLevel - $level;
                        for($m = 0;$m<=$diff;++$m) {
                            array_pop($clue);
                        }
                    }
                    if ($lastLevel != $level || (count($clue) == 1)) {
                        $clue[] = $level;
                    }

                    $lastLevel = $level;
                    $titleCode = implode("_",$clue); //文档树目录层级路径遍历

                    $startPos = $titles[$i][1];
                    if ($i != sizeof($titles) -1) {
                        $len = ($titles[$i+1][1] - $titles[$i][1] - strlen($rawTitle));
                    }
                    else {
                        $len = strlen($fileContent[$userName.".md"]) - ($titles[$i][1]+strlen($rawTitle));
                    }
                    $cont = substr($fileContent[$userName.".md"],$startPos+strlen($rawTitle)+1,$len-1);
                    $titleWithClue = $title.'=='.$titleCode;
                    $data = array(
                        'title'=>$titleWithClue,
                        'content'=>empty($cont) ? "" : $cont
                    );
                    $titleContent[] = $data;
                }
                if ($rawMinLevel >2) {
                    foreach($tieleContent as &$titleInfo) {
                        $title = substr($titleInfo['title'],1,strrpos($titleInfo['title'],"==") +1);
                        $levelCode = explode("_",substr($titleInfo['title'],strrpos($titleInfo['title'],"==")+2));
                        foreach($levelCode as $k => $v) {
                            if($k==0) continue;
                            $levelCode[$k]=$v-1;
                        }
                        $titleInfo['title'] = $title.implode("_",$levelCode);
                    }
                }
                $userTitleContent[$userName] = $titleContent;
            }
        }
        
    }
    return $userTitleContent;
}