<?php

header("Content-Type: text/html; charset=utf-8");

$filename = dirname(__FILE__)."/payPublicKey.pem";
	
	@chmod($filename, 0777);
	@unlink($filename);

$devPubKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAi+2KzPEUs6JoitkkFnrTf/NjqgiIQo09mm7tKRTdStS0k3zaSQaoWNLaEukpa3MB/278YYy+VpDRSAzH0QKRlOtWEzObGUL3EVB5qqniIvpDjTSWNGxxYhY4K+9ahwdsdH6CVws/onW7i5G5OU0Wp5Ahqldv7oQheFtYxB42IrAGxCcyY2A1bImJJTAUVqmyoWiWpmEyfI/AKBdAwSwsLasifIss1H7f9Wd7wvlPptsfjX6jURb10Se/y3MfxfJrKikbOifPXaYeNkSo5ML60aGYpq8cXlSNDXJEYatggYXtVCeI8WuM81k9NXovPsfzlShocRXwkCStSJf8Ld5TMQIDAQAB";
$begin_public_key = "-----BEGIN PUBLIC KEY-----\r\n";
$end_public_key = "-----END PUBLIC KEY-----\r\n";


$fp = fopen($filename,'ab');
fwrite($fp,$begin_public_key,strlen($begin_public_key));

$raw = strlen($devPubKey)/64;
$index = 0;
while($index <= $raw )
{
	$line = substr($devPubKey,$index*64,64)."\r\n";
	if(strlen(trim($line)) > 0)
	fwrite($fp,$line,strlen($line)); 
	$index++;
}
fwrite($fp,$end_public_key,strlen($end_public_key));
fclose($fp);
?>

