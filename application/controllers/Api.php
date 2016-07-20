<?php

# 获取 DB 处理函数名称:
// domain_meta_query()->域名查询并处理
// traffic_meta_query()->流量查询并处理
// interface_meta_query()->网络接口查询并处理
//
//
# 封装接口 API 名称:
// domain()->单位时间内 http domain 访问 top10
// traffic()->单位时间内网络接口流量情况 (默认满额为 top 10)
//
//

class Api extends CI_Controller {
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
    
    
    private function interface_meta_query()
    {
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
    
    
    public function traffic_meta_query()
    {
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
    
    //封装域名接口 json
    public function domain(){
        $response = $this->domain_meta_query();
        $this->output
        ->set_status_header(200)
        ->set_header('Access-Control-Allow-Origin: *')
        ->set_content_type('application/json', 'utf-8')
        ->set_output(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        ->_display();
        exit;
    }
    
    //封装流量接口 json
    public function traffic(){
        $response = $this->traffic_meta_query();
        $this->output
        ->set_status_header(200)
        ->set_header('Access-Control-Allow-Origin: *')
        ->set_content_type('application/json', 'utf-8')
        ->set_output(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        ->_display();
        exit;
    }
    public function index(){
        $this->load->view('status');
    }
    
}