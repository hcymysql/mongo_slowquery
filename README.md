# MongoDB Slowquery慢查询日志分析平台

# 简介
   MongoDB的慢SQL日志是记录到业务库的system.profile表里，当线上DB运行缓慢时，开发通常联系DBA去排查问题，那么可以将这种机械化的工作，做成一个平台化、可视化的工具出来，让开发在网页里点点鼠标即可查看数据库运行状况，这将大大提高工作效率，降低对DBA的依赖。
    
   参考了Percona pt-mongodb-query-digest工具抓取分析的展示思路，并用PHP重构，将分析结果插入MySQL表里，用前端页面展现出来，方便开发定位问题。

每次抓取最近的1000条超过1秒的慢SQL记录入库。

执行

    php check_mongo_slowsql.php 

相当于执行：

    db.getSiblingDB("samples").system.profile.find({millis:{$gte:1000}},    
    {millis:1,ns:1,query:1,ts:1,client:1,user:1}).sort({ts:-1}).limit(1000)


采用远程连接方式获取慢SQL，所以无需要在数据库服务器端部署相关agent或计划任务。

注：监控环境为MongoDB 3.2以上版本，2.X版本未测试。

![image](https://raw.githubusercontent.com/hcymysql/mongo_slowquery/master/images/1.png)

首页汇总了生产业务库31天内的慢SQL集合。

![image](https://raw.githubusercontent.com/hcymysql/mongo_slowquery/master/images/2.png)

点击《选择数据库标签》可以查看具体的业务库慢SQL趋势走向。

![image](https://raw.githubusercontent.com/hcymysql/mongo_slowquery/master/images/3.png)

点击抽象语句栏目的✚号，会弹出一个新连接，展示慢SQL的来源用户名，IP，集合的大小，集合的索引信息，以及SQL的Explain执行计划。

# 一、环境搭建

1、php-mysql驱动安装

shell> yum install -y php-pear php-devel php gcc openssl openssl-devel cyrus-sasl cyrus-sasl-devel httpd mysql php-mysql

2、php-mongo驱动安装：

shell> pecl install mongo

把extension=mongo.so加入到/etc/php.ini最后一行。

重启httpd服务，service httpd restart

（注：如果通过pecl安装报错，请参考以下链接，进行源码安装。PHP 5.4版本对应的驱动版本是mongodb-1.3.4.tgz

https://www.runoob.com/mongodb/mongodb-install-php-driver.html ）


3、创建mongodb管理员用户权限（监控采集数据时使用）

首先我们在被监控的数据库端创建授权帐号，允许采集器服务器能连接到Mongodb数据库。由于需要执行命令db.runCommand()，所以需要授予管理员角色，授权方式如下所示：

    > use yourdb
    > db.createUser({user:"monitor_slowsql",pwd:"123456",roles:[{role:"dbOwner",db:"yourdb"}]})
    
创建用户成功后，可以用客户端测试一下登陆是否正常，命令如下：

    mongo -u monitor_slowsql -p 123456 127.0.0.1:27017  --authenticationDatabase yourdb

# 二、MongoDB Slowquery部署

把https://github.com/hcymysql/mongo_slowquery/archive/master.zip安装包解压缩到 /var/www/html/目录下

1、导入MongoDB Slowquery慢查询监控工具表结构（mongo_slowsql库）

cd /var/www/html/mongo_slowquery/schema/

    mysql -uroot -p123456 < mongo_slowsql_schema.sql

2、录入被监控Mongo主机的信息

    INSERT INTO mongo_status_info(ip,tag,user,pwd,port,dbname,threshold_slow_ms)
    VALUES('10.10.159.31','MongoDB测试机1','monitor_slowsql','123456','27017','yourdb',1000);

注，以下字段可以按照需求变更：

ip字段含义：输入被监控Mongo的IP地址

tag字段含义：输入被监控Mongo的业务名字

user字段含义：输入被监控Mongo的用户名（dbOwner管理员角色）

pwd字段含义：输入被监控Mongo的密码

port字段含义：输入被监控MySQL的端口号

dbname字段含义：输入被监控Mongo的数据库登录权限认证库名

threshold_slow_ms字段含义：输入慢查询的阈值，当查询时间超过设定的阈值时，该SQL语句会被agent端抓取到平台里，单位毫秒

3、修改conn.php配置文件

# vim /var/www/html/mongo_slowquery/conn.php

      $con = mysqli_connect("127.0.0.1","admin","123456","mongo_slowsql","3306") or die("数据库链接错误".mysql_error());

改成你的MongoDB Slowquery慢查询监控工具表结构（mongo_slowsql库）连接信息（用户权限最好是管理员）

4、Agent定时任务每10分钟抓取一次慢日志

# crontab -l
      */10 * * * * cd /var/www/html/mongo_slowquery; /usr/bin/php /var/www/html/mongo_slowquery/check_mongo_slowsql.php  > /dev/null 2 >&1
   
5、页面访问

http://yourIP/mongo_slowquery/mongo_slowquery.php

加一个超链接，可方便地接入你们的自动化运维平台里。   

6、MongoDB开启慢查询
     
     db.setProfilingLevel(1,1000); 

查看是否开启慢查询
    
    db.getProfilingStatus()
