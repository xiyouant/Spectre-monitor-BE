<?php
class Network extends CI_Controller {
    //数据库数据插入函数
    private function meta_insert($interface,$method,$timeStamp,$trafficUsage){
	     $data = array(
       'interface' => $interface,
       'method' => $method,
       'timeStamp' => $timeStamp,
       'trafficUsage' => $trafficUsage
       );
       // 生成这样的SQL代码:
       // INSERT INTO mytable (title, name, date) VALUES ('{$title}', '{$name}', '{$date}')
       $this->db->insert('traffic', $data);
	  }

    //单位转换函数
    private function unit_convert($bytes,$unit){

      if($unit == "MB"){
          $result = round($bytes/1000/1000, 1);
          }
      else if ($unit == "GB"){
          $result = round($bytes/1000/1000/1000, 5);
          }
          return $result;
      }

    //数据处理并插入数据库函数
    private function collect_traffic($trafficArray){
      if ($trafficArray !== null) {
          $method_receive = $trafficArray[0]['method'];
          $method_transmit = $trafficArray[1]['method'];
          //公用timestamp
          $timeStamp = $trafficArray[0]['timeStamp'];
          array_splice($trafficArray[0],-2);
            foreach($trafficArray[0] as $key=>$value)
            {
              $this->meta_insert($key,$method_receive,$timeStamp,$value);
            }
          array_splice($trafficArray[1],-2);
            foreach($trafficArray[1] as $key=>$value)
            {
              $this->meta_insert($key,$method_transmit,$timeStamp,$value);
            }
      }
      else {
        echo "Datasource error";
      }
    }

    public function traffic(){
      //网卡流量
      $strs = file("/proc/net/dev");
      $interfaces = [];
      $transmit = [];
      $receive =[];
      $combineArray=[];
      for ($i = 2,$j=0; $i < count($strs); $i++,$j++ )
      {
      	preg_match_all( "/([^\s]+):[\s]{0,}(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/", $strs[$i], $info );
        // interfaces
        $interfaces[$j] = $info[1][0];
        // transmit
        $transmit[$interfaces[$j]] = $this->unit_convert($info[10][0],"MB");
        // receive
        $receive[$interfaces[$j]] = $this->unit_convert($info[2][0],"MB");
      }
        $transmit['timeStamp'] = time();
        $transmit['method'] = "transmit";
        $receive['timeStamp'] = time();
        $receive['method'] = "receive";
        $trafficArray = array($receive,$transmit);
        return $trafficArray;
    }

        public function index(){
            $data = $this->traffic();
            $this->collect_traffic($data);
            // $this->load->view('spectre');
        }
}
