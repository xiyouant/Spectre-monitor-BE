<?php
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
            // $jsonArray = $this->input->input_stream();
            print_r ($this->input->raw_input_stream);
            // return $jsonArray;
        }
        else{
            return 0;
        }
    }
    //处理 Post 和 Get 请求函数
    //返回参数与 ip 构成的关联数组
    public function resolveRequest()
    {
        if ($this->input->method() == "get") {
            $params = $this->input->get(NULL, TRUE);
        }
        elseif ($this->input->method() == "post") {
            $params = $this->input->post(NULL, TRUE);
        }
        else{
            return 0;
        }
        $ip=array('ip' => $this->input->ip_address());
        return array_merge_recursive($params,$ip);
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
    //视图区域
    
    public function index(){
        $this->load->view('setting_view');
    }
    
}