<?php

# 获取 DB 处理函数名称:
// domain_meta_query()->域名查询并处理
// traffic_meta_query()->流量查询并处理
// interface_meta_query()->网络接口查询并处理
// getServiceStatus()->Spectre 状态查询并处理

class Api extends CI_Controller {
    
    /************************ utils function ********************************************/
    //输出 json
    private function jsonOutput($data){
        $this->output
        ->set_status_header(200)
        ->set_header('Access-Control-Allow-Origin: *')
        ->set_header('Cache-Control: no-store, no-cache, must-revalidate')
        ->set_header('Pragma: no-cache')
        ->set_content_type('application/json', 'utf-8')
        ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        ->_display();
        exit;
    }
    
    // 网卡流量元数据
    private function traffic_KB(){
        //网卡流量
        $strs = file("/proc/net/dev");
        $interfaces = [];
        $transmit = [];
        $receive = [];
        $combineArray = [];
        for ($i = 2,$j = 0; $i < count($strs); $i++,$j++ )
        {
            preg_match_all( "/([^\s]+):[\s]{0,}(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/", $strs[$i], $info );
            // interfaces
            $interfaces[$j] = $info[1][0];
            // transmit
            $transmit[$interfaces[$j]] = $this->unit_convert($info[10][0],"KB");
            // receive
            $receive[$interfaces[$j]] = $this->unit_convert($info[2][0],"KB");
        }
        $transmit['timeStamp'] = time();
        $transmit['method'] = "transmit";
        $receive['timeStamp'] = time();
        $receive['method'] = "receive";
        $trafficArray = array($receive,$transmit);
        return $trafficArray;
    }
    
    //单位转换函数
    private function unit_convert($bytes,$unit){
        
        if($unit == "MB"){
            $result = round($bytes/1000/1000, 1);
        }
        else if ($unit == "GB"){
            $result = round($bytes/1000/1000/1000, 5);
        }
        
        else if ($unit == "KB"){
            $result = round($bytes/1000, 2);
        }
        return $result;
    }
    
    //处理 Post 和 Get 请求函数
    //返回参数与 ip 构成的关联数组
    //接受所有参数并经过 XSS 过滤 params= TRUE
    private function resolveRequest(){
        if ($this->input->method() == "get") {
            $params = $this->input->get(NULL, TRUE);
            $method = array('method' => 'get');
        }
        elseif ($this->input->method() == "post") {
            $params = $this->input->post(NULL, TRUE);
            $method = array('method' => 'post');
        }
        else{
            return 0;
        }
        $ip=array('ip' => $this->input->ip_address());
        return array_merge_recursive($method,$params,$ip);
    }
    
    private function curl($host){
        $params = '-o /dev/null -s -w "
        {
            \"time_namelookup\":%{time_namelookup},
            \"time_connect\":%{time_connect},
            \"time_appconnect\":%{time_appconnect},
            \"time_pretransfer\":%{time_pretransfer},
            \"time_redirect\":%{time_redirect},
            \"time_starttransfer\":%{time_starttransfer},
            \"time_total\":%{time_total},
            \"speed_download\": %{speed_download},
            \"speed_upload\": %{speed_upload}
        }"' . " " . $host;
        $this->load->library('command');
        // 命令
        $curl = new Command('curl' );
        $curl ->setArgs($params);
        if ($curl->execute()) {
            $result_string = $curl->getOutput();
            $curl_meta = json_decode($result_string, true);
            $curl_meta[range_connection] = $curl_meta['time_connect'] - $curl_meta['time_namelookup'];
            $curl_meta[range_ssl] = $curl_meta['time_pretransfer'] - $curl_meta['time_connect'];
            $curl_meta[range_server] = $curl_meta['time_starttransfer'] - $curl_meta['time_pretransfer'];
            $curl_meta[range_transfer]=$curl_meta['time_total'] - $curl_meta['time_starttransfer'];
            foreach ($curl_meta as $key => $value) {
                $time_pattern="/(time_*)|(range_*)/";
                $byte_pattern="/(speed_*)/";
                // ms
                if (preg_match($time_pattern, $key)) {
                    $curl_meta[$key]=($value * 1000);
                }
                // KiB
                if (preg_match($byte_pattern, $key)){
                    $curl_meta[$key]=round(($value / 1024),3);
                }
            }
            return $curl_meta;
        } else {
            return $exitCode = array(
            'status' => 0,
            'message'=>"host resolve failed"
            );
        }
    }
    
    private function pong($host){
        $this->load->library('command');
        // 命令
        $ping = new Command('ping');
        // 设置参数
        $ping->setArgs('-c 1 ' . $host);
        if ($ping->execute()) {
            $result = $ping->getOutput();
            //成功执行
            if($ping->getExecuted()){
                //This regex will grab each ping line.
                $pingLineRegex = "/([0-9]+) bytes from (.+): icmp_seq=([0-9]+) ttl=([0-9]+) time=([0-9\.]+) ms/";
                //This regex grabs the aggregated results at the bottom.
                $pingResultRegex = $re = "/--- (.+) ping statistics ---\\n([0-9]+) packets transmitted, ([0-9]+) received, ([0-9\\.]+)% packet loss, time ([0-9]+)ms\\nrtt min\\/avg\\/max\\/mdev = ([0-9\\.]+)\\/([0-9\\.]+)\\/([0-9\\.]+)\\/([0-9\\.]+) ms/";
                
                preg_match_all($pingLineRegex, $result, $pingLineMatches);
                $pingsLineResult= array(
                'bytes' => $pingLineMatches[1][0],
                'ip' => $pingLineMatches[2][0],
                'icmp_seq' => $pingLineMatches[3][0],
                'ttl' => $pingLineMatches[4][0],
                'time' => $pingLineMatches[5][0],
                );
                
                preg_match_all($pingResultRegex, $result, $pingResultMatches);
                $pingStatistics = array(
                'host' => $pingResultMatches[1][0],
                'tx' => $pingResultMatches[2][0],
                'rx' => $pingResultMatches[3][0],
                'loss' => $pingResultMatches[4][0],
                'static_time' => $pingResultMatches[5][0],
                'min' => $pingResultMatches[6][0],
                'avg' => $pingResultMatches[7][0],
                'max' => $pingResultMatches[8][0],
                'mdev' => $pingResultMatches[9][0],
                );
                return array_merge_recursive($pingsLineResult,$pingStatistics);
            }
        }
        else {
            $exitCode = $ping->getExitCode();
            return array(
            'errorCode' => 0 ,
            'message' => 'ping failed'
            );
            
        }
    }
    
    /************************ DB and file process function ********************************************/
    
    private function domain_meta_query(){
        $query = $this->db->query('SELECT id,url,count,startTimestamp,endTimestamp FROM metadata');
        // 定义查询结果数组
        $queryArray = $query->result_array();
        // 查询结果数组长度
        $queryArraylength = count($queryArray);
        if ($queryArraylength !== 0) {
            // 二维关联数组按照序号倒序排序
            foreach ($queryArray as $key => $value) {
                $temp[$key]=$value['id'];
            }
            // 二维关联数组按照序号倒序排序
            array_multisort($temp,SORT_DESC,$queryArray);
            // 切片取得 top 10
            $sliceArray = array_splice($queryArray,0,10);
            // 按照出现次数(小->大)重新分配 id Top:10
            for ($i=0; $i < count($sliceArray); $i++) {
                $sliceArray[$i]['id'] = $i+1;
            }
            return $sliceArray;
        }
        else {
            $errorArray = array("Server data error" => "true","Status code" => 201 );
            return $errorArray;
        }
    }
    
    
    private function interface_meta_query(){
        $query = $this->db->query('SELECT interface FROM `interface`');
        // 定义查询结果数组
        $queryArray = $query->result_array();
        $queryArraylength = count($queryArray);
        //定义新 interface 索引数组
        $interfacesArray=array();
        if ($queryArraylength !== 0) {
            for ($i=0; $i < $queryArraylength; $i++) {
                $interfacesArray[] = $queryArray[$i]['interface'];
            }
        } else {
            return "table interface empty";
        }
        return $interfacesArray;
    }
    
    
    private function traffic_meta_query(){
        //调用 interface_meta_query 查询结果
        $interfacesArray = $this->interface_meta_query();
        for ($i=0; $i < count($interfacesArray); $i++) {
            // 定义下载流量数组
            $download = [];
            // 定义上传流量数组
            $upload = [];
            // 定义时间戳数组
            $timeStamp = [];
            $receiveArray = $this->db->query("SELECT trafficUsage FROM `traffic` WHERE interface='{$interfacesArray[$i]}' AND method ='receive' ORDER BY id DESC LIMIT 10");
            $transmitArray = $this->db->query("SELECT trafficUsage FROM `traffic` WHERE interface='{$interfacesArray[$i]}' AND method ='transmit' ORDER BY id DESC LIMIT 10");
            $timeStampArray = $this->db->query("SELECT timeStamp FROM `traffic` WHERE interface='{$interfacesArray[$i]}' AND method ='transmit' ORDER BY id DESC LIMIT 10");
            $downloadArray = $receiveArray->result_array();
            $uploadArray = $transmitArray->result_array();
            $timeArray = $timeStampArray->result_array();
            foreach ($downloadArray as $value) {
                foreach($value as $name => $receive){
                    $download[] = (int)$receive;
                }
            }
            
            foreach ($uploadArray as $value) {
                foreach($value as $name => $transmit){
                    $upload[] = (int)$transmit;
                }
            }
            
            foreach ($timeArray as $value) {
                foreach($value as $name => $time){
                    $timeStamp[] = (int)$time;
                }
            }
            
            $download = array('download' => $download );
            $upload = array('upload' => $upload );
            $timeStamp = array('timeStamp' => $timeStamp );
            $jsonArray=array($download,$upload,$timeStamp);
            $json[] = array($interfacesArray[$i] => $jsonArray);
        }
        return array($json);
        
    }
    
    
    private function getServiceStatus(){
        $this->load->library('command');
        // 命令
        $getMemInfo = new Command('cat');
        // 设置参数
        $getMemInfo->setArgs("/proc/meminfo");
        // 查询 tunnel 状态
        $checkTunnelStatus = new Command('ps');
        $checkTunnelStatus->setArgs("-aux|grep ss-tunnel");
        // 查询 redir 状态
        $checkRedirStatus = new Command('ps');
        $checkRedirStatus->setArgs("-aux|grep ss-redir");
        
        if ($getMemInfo->execute()) {
            // 去除空格
            $memInfoStr = preg_replace("/\s|　/","",$getMemInfo->getOutput());
            //分割
            $memInfoStrArray = explode("kB", $memInfoStr);
            $memInfoArray =[];
            for ($i=0; $i < 3; $i++) {
                //分割
                $memInfoArray[$i] = explode(":", $memInfoStrArray[$i]);
            }
            if ($memInfoArray[0][0] == "MemTotal" && $memInfoArray[1][0] == "MemFree") {
                $memInfo=array("available"=>intval($memInfoArray[2][1]),"free"=>intval($memInfoArray[1][1]),"total"=>intval($memInfoArray[0][1]));
                // print_r($memInfo);
            }
        } else {
            // echo $getMemInfo->getError();
            $exitCode = $getMemInfo->getExitCode();
        }
        
        
        if ($checkTunnelStatus->execute()) {
            // echo $checkTunnelStatus->getOutput();
            $tunnelAlive = substr_count($checkTunnelStatus->getOutput(), 'ss-tunnel') > 2 ? true: false;
            
        } else {
            // echo $checkTunnelStatus->getError();
            $exitCode = $checkTunnelStatus->getExitCode();
        }
        
        if ($checkRedirStatus->execute()) {
            // echo $checkRedirStatus->getOutput();
            $redirAlive = substr_count($checkRedirStatus->getOutput(), 'ss-redir') > 2 ? true: false;
            
        } else {
            // echo $checkRedirStatus->getError();
            $exitCode = $checkRedirStatus->getExitCode();
        }
        
        $serviceStatus = array('redir' => $redirAlive ,'tunnel' => $tunnelAlive ,'memInfo' => $memInfo);
        return $serviceStatus;
        
    }
    
    /************************ setting process function ********************************************/
    //服务重新启动
    // nohup /usr/local/bin/ss-redir -c $configfile_path -d start >/dev/null 2>&1&
    // nohup /usr/local/bin/ss-tunnel -c $configfile_path -l $ss_tunnel_port -u -L $ss_tunnel_address > /dev/null 2>&1&
    // process_name configfile_path 参数必须给出,ss-tunnel 地址和 port 可选
    private function serviceRestart($process_name,$configfile_path,$ss_tunnel_address="",$ss_tunnel_port=""){
        $this->load->library('command');
        if( $process_name == "ss-tunnel" && isset($configfile_path)){
            $restartService = new Command('nohup /usr/local/bin/' . $process_name . '-c' . $configfile_path . '-l' . $ss_tunnel_port . '-u -L' . $ss_tunnel_address . '> /dev/null 2>&1&');
            if ($restartService->execute()) {
                echo $checkRedirStatus->getOutput();
                //执行成功
                return 1;
            }
            else {
                echo "ss-tunnel start failed  \n";
                echo $restartService->getError();
                $exitCode = $restartService->getExitCode();
                //执行失败
                return 0;
            }
        }
        else if( $process_name == "ss-redir" && isset($configfile_path)){
            $restartService = new Command('nohup /usr/local/bin/' . $process_name . '-c' . $configfile_path . '-d start' . '> /dev/null 2>&1&');
            if ($restartService->execute()) {
                echo $checkRedirStatus->getOutput();
                //执行成功
                return 1;
            }
            else {
                echo "ss-redir start failed \n";
                echo $restartService->getError();
                $exitCode = $restartService->getExitCode();
                //执行失败
                return 0;
            }
        }
        else {
            echo "service name is empty";
        }
        
    }
    
    
    //封装重启服务回调接口 json
    private function callback($statusCode,$restartService){
        $response = array('restartService' => $restartService, 'status' => $statusCode);
        $this->output
        ->set_status_header(200)
        ->set_header('Access-Control-Allow-Origin: *')
        ->set_content_type('application/json', 'utf-8')
        ->set_output(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        ->_display();
        exit;
    }
    
    /************************ json output function ********************************************/
    //封装域名接口 json
    private function domain(){
        $response = $this->domain_meta_query();
        $this->jsonOutput($response);
    }
    
    
    //封装流量接口 json
    private function traffic(){
        $response = $this->traffic_meta_query();
        $this->jsonOutput($response);
    }
    
    
    //封装 ServiceStatus 接口 json
    private function serviceStatus(){
        $response = $this->getServiceStatus();
        $this->jsonOutput($response);
    }
    
    /************************ API function ********************************************/
    //query 接口
    public function query()
    {
        $params = $this->resolveRequest();
        if ($params['method'] == 'get' && count($params) > 2){
            if ($params['query']== 'traffic') {
                $this->traffic();
            }
            elseif ($params['query'] == 'domain') {
                $this->domain();
            }
            elseif ($params['query'] == 'serviceStatus') {
                $this->serviceStatus();
            }
        }
    }
    
    
    //重启服务接口
    public function serviceReload(){
        $params = $this->resolveRequest();
        if (isset($params['restartService'])) {
            //TODO
            $statusCode = $this->serviceRestart($params['restartService'],"");
            $this->callback($statusCode,$params['restartService']);
        }
        else {
            echo "service name parameter is empty";
            return 0;
        }
    }
    
    //ping 接口
    public function ping()
    {
        $params = $this->resolveRequest();
        if ($params['method'] == 'get' && count($params) > 2){
            if (isset($params['host'])) {
                $this->jsonOutput($this->pong($params['host']));
            }
        }
        
    }
    // 视图
    public function index(){
        $this->load->view('status');
    }
    
    //实时流量接口
    public function realTimeTraffic(){
        $data_before = $this->traffic_KB();
        usleep(1000000);
        $data_after = $this->traffic_KB();
        $interface_count=count($data_before[0])-2;
        foreach($data_before[0] as $key=>$value){
            $receive_per_second[$key] = round($data_after[0][$key] - $data_before[0][$key],2);
        }
        foreach($data_before[1] as $key=>$value){
            $transmit_per_second[$key] = round($data_after[1][$key] - $data_before[1][$key],2);
        }
        $response['receive']=array_splice($receive_per_second,0,$interface_count);
        $response['transmit']=array_splice($transmit_per_second,0,$interface_count);
        $this->jsonOutput($response);
    }
    
    //http 信息统计
    public function httpStat(){
        $params = $this->resolveRequest();
        if ($params['method'] == 'get' && count($params) > 2){
            if (isset($params['host'])) {
                $this->jsonOutput($this->curl($params['host']));
            }
        }
    }
}