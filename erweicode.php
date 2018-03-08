$urlToEncode="http://www.helloweba.com";   
generateQRfromGoogle($urlToEncode);   
/**  
 * google api 二维码生成【QRcode可以存储最多4296个字母数字类型的任意文本，具体可以查看二维码数据格式】  
 * @param string $chl 二维码包含的信息，可以是数字、字符、二进制信息、汉字。  
 不能混合数据类型，数据必须经过UTF-8 URL-encoded  
 * @param int $widhtHeight 生成二维码的尺寸设置  
 * @param string $EC_level 可选纠错级别，QR码支持四个等级纠错，用来恢复丢失的、读错的、模糊的、数据。  
 *                            L-默认：可以识别已损失的7%的数据  
 *                            M-可以识别已损失15%的数据  
 *                            Q-可以识别已损失25%的数据  
 *                            H-可以识别已损失30%的数据  
 * @param int $margin 生成的二维码离图片边框的距离  
 */   
function generateQRfromGoogle($chl,$widhtHeight ='150',$EC_level='L',$margin='0')   
{   
    $chl = urlencode($chl);   
    echo '<img src="http://chart.apis.google.com/chart?chs='.$widhtHeight.'x'.$widhtHeight.'   
    &cht=qr&chld='.$EC_level.'|'.$margin.'&chl='.$chl.'" alt="QR code" widhtHeight="'.$widhtHeight.'   
    " widhtHeight="'.$widhtHeight.'"/>';   
}