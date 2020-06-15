# mongo_slowquery
MongoDB Slowquery平台可视化慢日志工具

agent客户端：慢日志采集分析工具，参考了Percona pt-mongodb-query-digest展示思路，并用PHP重写定制化，将结果插入MySQL表里，用前端页面展现出来，方便开发定位问题。每次抓取最近的1000条超过2秒的慢SQL记录入库。

采用远程连接方式获取慢SQL，所以无需要在数据库服务器端部署相关agent或计划任务。
