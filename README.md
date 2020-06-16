# MongoDB Slowquery平台可视化慢查询工具（开发中）

agent客户端：check_mongo_slowsql.php——慢日志采集分析工具，参考了Percona pt-mongodb-query-digest展示思路，并用PHP重写定制化，将结果插入MySQL表里，用前端页面展现出来，方便开发定位问题。

每次抓取最近的1000条超过2秒的慢SQL记录入库。

执行

    php check_mongo_slowsql.php 

相当于执行：

    db.getSiblingDB("samples").system.profile.find({millis:{$gte:2000}},    
    {millis:1,ns:1,query:1,ts:1,client:1,user:1}).sort({ts:-1}).limit(1000)

注：Mongo的慢SQL是记录到业务库的system.profile表里。

采用远程连接方式获取慢SQL，所以无需要在数据库服务器端部署相关agent或计划任务。
