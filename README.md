# Spectre-monitor-dashboard
### Spectre-monitor 后端数据处理模块

### 安装:

切换至 webserver 目录:
```
cd /home/wwwroot
```
Git clone:
```shell
git clone https://github.com/xiyouant/Spectre-monitor-BE.git
```
覆盖 `default` 内的原始文件:
```shell
mv Spectre-monitor-BE/* default
```

### 启动

浏览器访问:

`homepage`:
```shell
192.168.2.1
```




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



