# Spectre-monitor-dashboard
### Spectre-monitor 后端数据处理模块


### 封装接口 API 以及返回格式:

**/api/query?query=domain**

 - -->说明:单位时间内 domain 访问 top10 (http)
 - -->请求方式: get
 - -->返回格式:ObjectArray

**/api/query?query=traffic**

 - -->说明:单位时间内网络接口流量情况 (默认配额为 10 次取样时间点)
 - -->请求方式: get
 - -->返回格式:ObjectArray


**/api/query?query=serviceStatus**

 - -->说明:Spectre 运行状态查询
 - -->请求方式: get
 - -->返回格式: json
 
```
    {
    "redir": false,
    "tunnel": false,
    "memInfo": 
            {
            "available": 8279168,
            "free": 4971220,
            "total": 12253692
        }
    }
```

**/api/ping?host=`domain||IP`**

 - -->说明:ping
 - -->请求方式: get
 - -->返回格式: json

ping 成功结果:

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

ping 失败:

    {
    "status": 0,
    "message": "ping failed"
    }


**/api/mtr?host=`domain||IP`**

 - -->说明:mytraceroute
 - -->请求方式: get
 - -->返回格式: json
 - -->以 baidu.com 为例

```
{
  "baidu.com": [
    {
      "host": "gateway",
      "loss": 0,
      "snt": 5,
      "last": 0.6,
      "avg": 0.5,
      "best": 0.4,
      "wrst": 0.6
    },
    {
      "host": "222.24.51.1",
      "loss": 0,
      "snt": 5,
      "last": 1,
      "avg": 1.2,
      "best": 0.7,
      "wrst": 2
    },
    {
      "host": "222.24.63.25",
      "loss": 0,
      "snt": 5,
      "last": 0.9,
      "avg": 0.9,
      "best": 0.7,
      "wrst": 1.2
    },
    {
      "host": "10.224.91.201",
      "loss": 0,
      "snt": 5,
      "last": 2.4,
      "avg": 1.7,
      "best": 1.3,
      "wrst": 2.4
    },
    {
      "host": "10.224.91.5",
      "loss": 0,
      "snt": 5,
      "last": 1.5,
      "avg": 1.6,
      "best": 1.5,
      "wrst": 1.6
    },
    {
      "host": "117.36.240.49",
      "loss": 0,
      "snt": 5,
      "last": 4.8,
      "avg": 4.1,
      "best": 2.8,
      "wrst": 4.8
    },
    {
      "host": "202.97.65.77",
      "loss": 0,
      "snt": 5,
      "last": 24.1,
      "avg": 24.1,
      "best": 23.9,
      "wrst": 24.3
    },
    {
      "host": "202.97.88.234",
      "loss": 20,
      "snt": 5,
      "last": 20.6,
      "avg": 20.5,
      "best": 20.4,
      "wrst": 20.6
    },
    {
      "host": "221.183.15.17",
      "loss": 0,
      "snt": 5,
      "last": 24.6,
      "avg": 24.8,
      "best": 24.3,
      "wrst": 25.6
    },
    {
      "host": "221.176.21.161",
      "loss": 60,
      "snt": 5,
      "last": 29.9,
      "avg": 30.2,
      "best": 29.9,
      "wrst": 30.6
    },
    {
      "host": "221.183.18.130",
      "loss": 60,
      "snt": 5,
      "last": 33.2,
      "avg": 33.2,
      "best": 33.2,
      "wrst": 33.2
    },
    {
      "host": "111.13.98.249",
      "loss": 0,
      "snt": 5,
      "last": 29.6,
      "avg": 29.9,
      "best": 29.4,
      "wrst": 30.4
    },
    {
      "host": "111.13.108.26",
      "loss": 0,
      "snt": 5,
      "last": 33.5,
      "avg": 33.7,
      "best": 33.3,
      "wrst": 34.9
    },
    {
      "host": "???",
      "loss": 100,
      "snt": 5,
      "last": 0,
      "avg": 0,
      "best": 0,
      "wrst": 0
    },
    {
      "host": "???",
      "loss": 100,
      "snt": 5,
      "last": 0,
      "avg": 0,
      "best": 0,
      "wrst": 0
    },
    {
      "host": "111.13.101.208",
      "loss": 0,
      "snt": 5,
      "last": 29.7,
      "avg": 29.6,
      "best": 29.4,
      "wrst": 29.7
    }
  ]
}
```

**/api/httpStat?host=`domain||IP`**

 - -->说明: HTTP 信息统计，包括 DNS 查询时间、TCP 连接时间、SSL 握手时间、服务端响应时间、内容传输时间和总耗时
 - -->请求方式: get
 - -->返回格式: json
 - -->以 baidu.com 为例

```
{
  "time_namelookup": 28,
  "time_connect": 50,
  "time_appconnect": 0,
  "time_pretransfer": 50,
  "time_redirect": 0,
  "time_starttransfer": 75,
  "time_total": 76,
  "speed_download": 1.047,
  "speed_upload": 0,
  "range_connection": 22,
  "range_ssl": 0,
  "range_server": 25,
  "range_transfer": 1
}

```
**/api/realTimeTraffic()**

 - -->说明:各网口实时流量 KB/s,网络接口根据实际接口动态更新
 - -->请求方式:get 
 - -->返回格式: json

 ```
 {
  "receive": {
    "eth0": 4.84,
    "wlan0": 0,
    "lo": 0,
    "timeStamp": 1474879700
  },
  "transmit": {
    "eth0": 0.44,
    "wlan0": 0,
    "lo": 0,
    "timeStamp": 1474879700
  }
}
 ```

**/api/serviceReload**

 - -->说明:服务(ss-tunnel||ss-redir)重新启动接口
 - -->请求方式: post
 - -->post 请求参数: restartService=重新启动的服务名称
 - -->返回格式: json

重启成功:

    {
    "restartService": "ss-redir",
    "status": 1
    }

重启失败

    {
    "restartService": "ss-redir",
    "status": 0
    }


**/setting/getProfile**

 - -->说明:服务(ss-tunnel||ss-redir)重新启动接口
 - -->请求方式: get
 - -->post 请求参数: restartService=重新启动的服务名称
 - -->返回格式: ObjectArray


    [{
    `"id"`: "1",
    `"profile_name"`: "config-no-1",
    `"server_address"`: "",
    `"server_port"`: "",
    `"password"`: "",
    `"local_port"`: "",
    `"timeout"`: "",
    `"method"`: "",
    `"auth"`: "0"}]

**/setting/createProfile**

 - -->说明:新建 profile
 - -->请求方式: post
 - -->post 请求参数: json
 - -->返回格式: json(新创建的 profile)

新建成功:

    {
    "profile_name": "新创建的 profile",
    "server_address" : "",
    "server_port": "",
    "password": "",
    "local_port": "",
    "timeout": "",
    "method": "",
    "auth": "0"
    }


**/setting/updateProfile**

 - -->说明:更新 profile
 - -->请求方式: post
 - -->post 请求参数: json
 - -->返回格式: json(修改后的 profile)

更新成功(返回更新后的配置):

    {
    "profile_name": "",
    "server_address": "",
    "server_port": "",
    "password": "",
    "local_port": "",
    "timeout": "",
    "method": "",
    "auth": "0"
    }

更新失败:

    {
    "updateProfile": "con9",
    "status": 0
    }

**/setting/deleteProfile**

 - -->说明:删除 profile 接口
 - -->请求方式: post
 - -->post 请求参数

    `profileName` = 将要删除的 profile 
    `truncate` = boolean 是否要重置整个配置

-->返回格式:json()

删除成功:

    {
    "deleteProfile"="将要删除的 profile",
    "truncate" = boolean,
    "status" = 1
    }

删除失败:

    {
    "deleteProfile"="将要删除的 profile",
    "truncate" = boolean,
    "status" = 1
    }


**/setting/activateProfile**

 - -->说明:激活 profile 接口
 - -->请求方式: post
 - -->post 请求参数

    `profileName` = 将要激活的 profile

 - -->返回格式:json()

激活成功:

    {
    "activeProfile"="已经激活的 profile",
    "status" = 1
    }

激活失败:

    {
    "activeProfile"="将要激活的 profile",
    "status" = 0,
    "message" = "错误信息"
    }



