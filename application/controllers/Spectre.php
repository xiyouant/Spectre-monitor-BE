<?php
class Spectre extends CI_Controller {
    //数据库数据插入函数
    private function meta_insert($url,$count,$startTimestamp,$endTimestamp){
        $data = array(
        'url' => $url,
        'count' => $count,
        'startTimestamp' => $startTimestamp,
        'endTimestamp' => $endTimestamp
        );
        // 生成这样的SQL代码:
        // INSERT INTO mytable (title, name, date) VALUES ('{$title}', '{$name}', '{$date}')
        $this->db->insert('metadata', $data);
    }
    
    //获取缓存文件夹文件函数
    private function get_folder_files(){
        $this->load->helper('file');
        $this->load->helper('date');
        $timeStampfiles = get_filenames('server/Timestamp/');
        if (count($timeStampfiles) != 0 && count($timeStampfiles) >=2) {
            // 文件名排序
            sort($timeStampfiles);
            $arrLength = count($timeStampfiles);
            // 取最近一次收集时间
            $finaTimestampfile = $timeStampfiles[$arrLength - 2];
            $now = time();
            // 最近一次文件与当前的时间间隔
            print_r("now:" .$now);
            $period = $now - $finaTimestampfile;
            print_r('<pre>');
            print_r("file to get :" .$finaTimestampfile);
            print_r('<pre>');
            print_r($period);
            print_r('<pre>');
            return $finaTimestampfile;
            //TODO
            // if ($period === 0 || $period === 1) {
            //   // print_r($finaTimestampfile);
            //   return $finaTimestampfile;
            // }
            // else {
            //   print_r("period not fit");
            //   return null;
            // }
        }
        else {
            print_r("Cache folder is empty");
            return null;
        }
    }
    
    //数据处理并插入数据库函数
    private function collect_timestamp($timeStampfile){
        if ($timeStampfile !== null) {
            // 读取时间戳文件为一维数组
            $urlsArray = file('server/Timestamp/hourly/'.$timeStampfile);
            // 时间戳一维数组长度
            $timeStampArrayLength = count($urlsArray);
            // 获取分割后的开始时间
            $startTimestamp = explode(':',$urlsArray[0])[1];
            // 结束时间
            $endTimestamp = explode(':',$urlsArray[1])[1];
            // 满足 10 个则读取前十个数据
            if ($timeStampArrayLength >= 12 && $timeStampArrayLength !=0) {
                // 遍历前 10 个数据
                for ($i = 2; $i < 12; $i++ ){
                    // 依次将一维数组 [2 analy.qq.com] 按照 " "分割获得[2][analy.qq.com]
                    $i_length = count(explode(' ',$urlsArray[$i]));
                    // url 出现次数
                    $urlCount = explode(' ',$urlsArray[$i])[$i_length-2];
                    // url
                    $url=explode(' ',$urlsArray[$i])[$i_length-1];
                    // 调用 insert 函数
                    $this->meta_insert($url,$urlCount,$startTimestamp,$endTimestamp);
                }
            }
            
            //不满足 10 个则读取大于 0 其余数据用空填充
            else if($timeStampArrayLength == 2 || $timeStampArrayLength < 12){
                if ($timeStampArrayLength > 2) {
                    //忽略时间戳标记从第三个元素开始
                    for ($i = 2,$j=0; $i < $timeStampArrayLength; $i++,$j++){
                        // 依次将一维数组 [2 analy.qq.com] 按照 " "分割获得[2][analy.qq.com]
                        $i_length = count(explode(' ',$urlsArray[$i]));
                        // url 出现次数
                        $urlCount = explode(' ',$urlsArray[$i])[$i_length-2];
                        // url
                        $url = explode(' ',$urlsArray[$i])[$i_length-1];
                        $newArray[$j]['url']=$url;
                        $newArray[$j]['urlCount']=$urlCount;
                        $newArray[$j]['startTimestamp']=$startTimestamp;
                        $newArray[$j]['endTimestamp']=$endTimestamp;
                    }
                }
                else if ($timeStampArrayLength == 2) {
                    $newArray=array();
                }
                
                // 不足 10 则其余用空值填充形成新数组 newArray
                for ($i=count($newArray); $i < 10 ; $i++) {
                    $newArray[$i]['url']="null";
                    $newArray[$i]['urlCount']=0;
                    $newArray[$i]['startTimestamp']=$startTimestamp;
                    $newArray[$i]['endTimestamp']=$endTimestamp;
                }
                
                for ($i=0; $i < 10; $i++) {
                    // 调用 insert 函数
                    $this->meta_insert($newArray[$i]['url'],
                    $newArray[$i]['urlCount'],
                    $newArray[$i]['startTimestamp'],
                    $newArray[$i]['endTimestamp']);
                }
                
            }
            
        }
        else {
            print_r("Can not get Cache folder's files");
        }
    }
    
    
    public function collect(){
        $get_folder_files = $this->get_folder_files();
        $this->collect_timestamp($get_folder_files);
    }
    
    public function index(){
        $this->load->view('spectre');
    }
    
    
}