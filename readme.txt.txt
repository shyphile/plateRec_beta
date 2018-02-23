1.软件使用的框架：thinkphp5+workerman+vue.js+jquery+jqueryui+bootstrap
2.后端语言：php 

3.使用软件前先安装xmapp集成环镜
4.GatewayWorker的start.bat脚本要在后来一直执行，它是软件跟相机异步通讯的伺服工具
  可以用nssm工具将start.bat做成系统服务，随系统开机启用

5.数据库使用的是it_parkv10的mysql数据库文件

6.实时监控地址：http://192.168.0.18/plateRec_beta/plateRec/public/index/monitor/show
7.管理地址：http://localhost/plateRec_beta/plateRec/public/

8.软件未做功能：权限，月转临，白名单。。。

9.因为使用了vlc插件，只有ie浏览器和360浏览器可以看到相机的实时图像，其它浏览器除了不能看监控视频，其它不影响