<?php
// 将一个文件读入数组。
//读取时间戳文件为一维数组
$urls = file('Timestamp/1467523890');

//时间戳一维数组长度
$timeStampArrayLength = count($urls);
print_r('<pre>');

// 获取分割后的开始时间
$startTimestamp=explode(':',$urls[0])[1];

//结束时间
$endTimestamp=explode(':',$urls[1])[1];

echo "start : $startTimestamp";
echo "end : $endTimestamp";

for ($i = 2; $i < count($urls); $i++ )
{
  $i_length = count(explode(' ',$urls[$i]));
  print_r("num:" .explode(' ',$urls[$i])[$i_length-2]);
  print_r('<pre>');
  print_r("url:".explode(' ',$urls[$i])[$i_length-1]);
  print_r('<pre>');
}

?>
