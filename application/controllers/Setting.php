<?php
# 获取 DB 处理函数名称:
// domain_meta_query()->域名查询并处理
// traffic_meta_query()->流量查询并处理
// interface_meta_query()->网络接口查询并处理
// getServiceStatus()->Spectre 状态查询并处理

# 封装接口 API 以及返回格式:
/*
/setting/getProfile
-->说明:服务(ss-tunnel||ss-redir)重新启动接口
-->请求方式: get
-->post 请求参数: restartService=重新启动的服务名称
-->返回格式: json(对象数组)

[{id: "1",
profile_name: "config-no-1",
server_address: "159.203.225.72",
server_port: "9128",
password: "985265",
local_port: "1080",
timeout: "600",
method: "aes-256-cfb",
auth: "0"}]

/setting/createProfile
-->说明:新建 profile
-->请求方式: post
-->post 请求参数: json
-->返回格式: json(新创建的对象数组)
{
"profile_name": "config-no-2",
"server_address": "<scrip></scrip>",
"server_port": "9128",
"password": "985265",
"local_port": "1080",
"timeout": "600",
"method": "aes-256-cfb",
"auth": "0"
}


/setting/updateProfile
-->说明:更新 profile
-->请求方式: post
-->post 请求参数: json
-->返回格式: json(修改后的对象数组)
{
"profile_name": "config-no-2",
"server_address": "<scrip></scrip>",
"server_port": "9128",
"password": "985265",
"local_port": "1080",
"timeout": "600",
"method": "aes-256-cfb",
"auth": "0"
}

/setting/deleteProfile
-->说明:删除 profile 接口
-->请求方式: post
-->post 请求参数
profileName=将要删除的 profile 名字
truncate=boolean 是否要重置整个配置
-->返回格式:json()
{
"deleteProfile"="将要删除的 profile 名字",
"truncate" = boolean,
"status" = 0||1
}
*/


class Setting extends CI_Controller {
    
    /************************ utils function ********************************************/
    
    //处理 PUT 请求
    public function resolvePut(){
        if ($this->input->method() == "put") {
            // $jsonArray = $this->input->input_stream();
            // print_r ($jsonArray);
            // return $jsonArray;
        }
        else{
            return 0;
        }
    }
    
    
    //处理 Delete 请求
    public function resolveDel(){
        if ($this->input->method() == "delete") {
            print_r ($this->input->raw_input_stream);
        }
        else{
            return 0;
        }
    }
    
    //处理 Post 和 Get 请求函数
    //返回参数与 ip 构成的关联数组
    public function resolveRequest(){
        $ip=array('ip' => $this->input->ip_address());
        if ($this->input->method() == "get") {
            $params = $this->input->get(NULL, TRUE);
        }
        elseif ($this->input->method() == "post" && $this->input->get_request_header('Content-Type', TRUE) == "application/json") {
            $params = $this->security->xss_clean($this->input->raw_input_stream);
            return json_decode($params,true);
        }
        elseif ($this->input->method() == "post") {
            $params = $this->input->post(NULL, TRUE);
            return array_merge_recursive($params,$ip);
        }
        else{
            return 0;
        }
        
    }
    
    
    public function updateDB($data){
        $this->db->replace('socks_config', $data);
        return $data;
    }
    
    
    //DB 插入函数
    public function insertDB($data){
        if (isset($data['profile_name']) && isset($data['server_address'])) {
            $insertData= array(
            'profile_name' => $data['profile_name'],
            'server_address' => $data['server_address'],
            'server_port' => $data['server_port'],
            'password' => $data['password'],
            'local_port' => $data['local_port'],
            'timeout' => $data['timeout'],
            'method' => $data['method'],
            'auth' => $data['auth']
            );
            $this->db->insert('socks_config', $insertData);
            return $data;
        }
        
    }
    
    
    //DB 查询 参数:$params 调用 $this->db->get 方法
    public function queryDB($params){
        if (isset($params)) {
            $objectArray = [];
            $query = $this->db->get($params);
            foreach ($query->result() as $row)
            {
                array_push($objectArray,$row);
            }
            return $objectArray;
        }
        else{
            echo "need params";
        }
        
    }
    
    
    public function searchDB($prop, $params){
        $this->db->select('*');
        $this->db->from('socks_config');
        $this->db->where($prop, $params);
        $query = $this->db->get();
        $objectArray = [];
        foreach ($query->result() as $row)
        {
            array_push($objectArray,$row);
        }
        return count($objectArray);
    }
    
    
    public function deleteDB($params)
    {
        if(isset($params['profileName']) && $params['truncate'] == "false"){
            if ($this->searchDB("profile_name",$params['profileName'])){
                $this->db->delete('socks_config', array('profile_name' => $params['profileName']));
                return array('deleteProfile'=> $params['profileName'] , 'truncate' => false, 'status' => 1);
            }
            else {
                return array(
                'deleteProfile' =>$params['profileName'],
                'truncate'=>false,
                'status'=>0 );
                
            }
            
        }
        else if(isset($params['truncate']) && $params['truncate'] == "true"){
            $this->db->truncate('socks_config');
            return array('truncate' => true, 'status' => 1);
        }
    }
    
    
    public function jsonOutput($data){
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
    
    
    /************************ API function ********************************************/
    
    //查询 profile 接口
    public function getProfile(){
        $objectArray = $this->queryDB('socks_config');
        $this->jsonOutput($objectArray);
    }
    //创建 profile 接口
    public function createProfile(){
        $data = $this->resolveRequest();
        $objectArray = $this->insertDB($data);
        $this->jsonOutput($objectArray);
    }
    
    //更新 profile 接口
    public function updateProfile(){
        $data = $this->resolveRequest();
        $objectArray = $this->updateDB($data);
        $this->jsonOutput($objectArray);
        
    }
    //删除 profile 接口
    public function deleteProfile(){
        $data = $this->resolveRequest();
        $objectArray = $this->deleteDB($data);
        $this->jsonOutput($objectArray);
        
    }
    //视图区域
    public function index(){
        $this->load->view('setting_view');
    }
    
}