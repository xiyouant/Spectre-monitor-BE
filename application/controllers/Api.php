<?php

# 获取 DB 处理函数名称:
// domain_meta_query()->域名查询并处理
// traffic_meta_query()->流量查询并处理
// interface_meta_query()->网络接口查询并处理
// getServiceStatus()->Spectre 状态查询并处理

# 封装接口 API 以及返回格式:
/*
/api/query?query=domain
-->说明:单位时间内 http domain 访问 top10
-->请求方式: get
-->返回格式:对象数组

/api/query?query=traffic
-->说明:单位时间内网络接口流量情况 (默认满额为 top 10)
-->请求方式: get
-->返回格式:对象数组

/api/query?query=serviceStatus
-->说明:Spectre 运行状态查询
-->请求方式: get
-->返回格式: json
{
"redir": false,
"tunnel": false,
"memInfo": {
"available": 8279168,
"free": 4971220,
"total": 12253692
}
}

/api/ping?host=domain||IP
-->说明:ping
-->请求方式: get
-->返回格式: json
{
"bytes": "64",
"ip": "ip",
"icmp_seq": "1",
"ttl": "48",
"time": "318",
"host": "host",
"tx": "1",
"rx": "1",
"loss": "0",
"static_time": "0",
"min": "318.499",
"avg": "318.499",
"max": "318.499",
"mdev": "0.000"
}

/api/serviceReload
-->说明:服务(ss-tunnel||ss-redir)重新启动接口
-->请求方式: post
-->post 请求参数: restartService=重新启动的服务名称
-->返回格式: json
成功重启
{
"restartService": "ss-redir",
"status": 1
}
重启失败
{
"restartService": "ss-redir",
"status": 0
}
*/

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
            'errorCode' => 1000 ,
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
    
}