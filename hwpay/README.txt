PHP支付demo包含两个文件：
1、productPublicFile.php：用于生成必要的pem文件，是安全验证所必须得,若更改公钥需重新运行此程序，会自动更换生成的pem文件
2、demo.php: 用于支付安全验证，利用上步生成的pem文件，来完成支付的验证（有两个demo文件，分别支持sha1withrsa和sha256withrsa签名算法）。

环境：PHP5.0或以上

步骤
1、将productPublicFile.php中的公钥替换为自己在开发者社区上取得的。
2、运行productPublicFile.php，将生成为文件payPublicKey.pem。
3、运行demo.php，若不报错，则说明环境搭建成功。
4、做一笔测试支付，若控制台打印:"result"：0,说明支付成功。
5、有两个demo文件，分别支持sha1withrsa和sha256withrsa签名算法。cp可根据自己选择的签名算法配套使用。在同时有两种的情况下，可以根据接口中的参数“signType”确定使用哪一种。