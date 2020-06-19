# MongoDB Slowquery慢查询日志分析平台

# 简介
   MongoDB的慢SQL日志是记录到业务库的system.profile表里，当线上DB运行缓慢时，开发通常联系DBA去排查问题，那么可以将这种机械化的工作，做成一个平台化、可视化的工具出来，让开发在网页里点点鼠标即可查看数据库运行状况，这将大大提高工作效率，降低对DBA的依赖。
    
   参考了Percona pt-mongodb-query-digest工具抓取分析的展示思路，并用PHP重构，将分析结果插入MySQL表里，用前端页面展现出来，方便开发定位问题。

每次抓取最近的1000条超过1秒的慢SQL记录入库。

执行

    php check_mongo_slowsql.php 

相当于执行：

    db.getSiblingDB("samples").system.profile.find({millis:{$gte:2000}},    
    {millis:1,ns:1,query:1,ts:1,client:1,user:1}).sort({ts:-1}).limit(1000)


采用远程连接方式获取慢SQL，所以无需要在数据库服务器端部署相关agent或计划任务。

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
    
 
# 二、mongo_slowquery部署
