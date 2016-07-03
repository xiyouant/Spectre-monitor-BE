<?php
// 将一个文件读入数组。
//读取时间戳文件为一维数组
$urlsArray = file('server/Timestamp/hourly/1467540807');

//时间戳一维数组长度
$timeStampArrayLength = count($urlsArray);
print_r('<pre>');

// 获取分割后的开始时间
$startTimestamp=explode(':',$urlsArray[0])[1];

// 结束时间
$endTimestamp=explode(':',$urlsArray[1])[1];

echo "start : $startTimestamp";
echo "end : $endTimestamp";

for ($i = 2; $i < count($urlsArray); $i++ )
{
  // 依次将一维数组 [2 analy.qq.com] 按照 " "分割获得[2][analy.qq.com]
  $i_length = count(explode(' ',$urlsArray[$i]));
  $urlCount=explode(' ',$urlsArray[$i])[$i_length-2];
  $url=explode(' ',$urlsArray[$i])[$i_length-1];

  print_r("num: $urlCount");
  print_r('<pre>');
  print_r("url: $url");
  print_r('<pre>');



}

?>
