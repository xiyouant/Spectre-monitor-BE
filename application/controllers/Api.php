<?php
class Api extends CI_Controller {

    public function meta_query(){
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
    //封装 json
    public function meta_json(){
        $response = $this->meta_query();
        $this->output
        ->set_status_header(200)
        ->set_header('Access-Control-Allow-Origin: *')
        ->set_content_type('application/json', 'utf-8')
        ->set_output(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        ->_display();
        exit;
    }


    public function fuck_json(){
      $response=array('1'=>"one", '2'=>"two", '3'=>"three");
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
