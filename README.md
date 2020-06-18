# MongoDB Slowquery慢查询日志分析平台（开发中）

背景：
    MongoDB的慢SQL日志是记录到业务库的system.profile表里，当线上DB运行缓慢时，开发通常联系DBA去排查问题，那么可以将这种机械化的工作，做成一个平台化、可视化的工具出来，让开发在网页里点点鼠标即可查看数据库运行状况，这将大大提高工作效率，降低对DBA的依赖。
    
   参考了Percona pt-mongodb-query-digest工具抓取分析的展示思路，并用PHP重构，将分析结果插入MySQL表里，用前端页面展现出来，方便开发定位问题。

每次抓取最近的1000条超过2秒的慢SQL记录入库。

执行

    php check_mongo_slowsql.php 

相当于执行：

    db.getSiblingDB("samples").system.profile.find({millis:{$gte:2000}},    
    {millis:1,ns:1,query:1,ts:1,client:1,user:1}).sort({ts:-1}).limit(1000)


采用远程连接方式获取慢SQL，所以无需要在数据库服务器端部署相关agent或计划任务。
