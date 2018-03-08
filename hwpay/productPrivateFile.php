<?php

header("Content-Type: text/html; charset=utf-8");

$filename = dirname(__FILE__)."/payPrivateKey.pem";
	
	@chmod($filename, 0777);
	@unlink($filename);

$devPrvKey = "MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCL7YrM8RSzomiK2SQWetN/82OqCIhCjT2abu0pFN1K1LSTfNpJBqhY0toS6SlrcwH/bvxhjL5WkNFIDMfRApGU61YTM5sZQvcRUHmqqeIi+kONNJY0bHFiFjgr71qHB2x0foJXCz+idbuLkbk5TRankCGqV2/uhCF4W1jEHjYisAbEJzJjYDVsiYklMBRWqbKhaJamYTJ8j8AoF0DBLCwtqyJ8iyzUft/1Z3vC+U+m2x+NfqNRFvXRJ7/Lcx/F8msqKRs6J89dph42RKjkwvrRoZimrxxeVI0NckRhq2CBhe1UJ4jxa4zzWT01ei8+x/OVKGhxFfCQJK1Il/wt3lMxAgMBAAECggEANiA0FRNaDSRQ6QiWb4WiYzd8AU0cnBWzUxX+ceoqsWP216gvbZkALJ+fEDqVtYT+rFY7lOZSx/xrn8Gl10D1nFOt726CW7KuDEoDThM5sIr671d8lJmwAB+VKdWDwICMIMHA3DezLT36RkIfkj0x+q4Df7cjynDc24gGHZKMIT06QpETzIUJAS75Hh1eh/8kZC34O/K4yg02WP94E2OAdKvceJ++UIIOEsXZEQgATeorefCG3TtTE56GGM9EbG6Fd0alJ2EyUHhY2fpW5a2vC+ENL02N4sV2n35Dr3LdW16NaK2k69YWGsFHdLhPiO65jJiufDmnKCC391r/boDJcwKBgQD8ufun9L0wVHhx0FgDWJSFK1bBoT6NfkUDBmQTAVeMHOjXz2su6sqNoZLoK2DZ2oOpZRzkOxDXiLOtJgMaKoLF5cy6bL8ssO5QuZcOoi6kh8BQs6dTaEn7BlenuMU4st7ZL9u5PmZasCzgAxAIzHcyTCyiBUj05qvFvSk+OaXYewKBgQCNvYeel7qcDEwab5CHFv+zCjRs1Je5IwzYun70OFbbJkpm3byRh3Fp52oe4De4DLwHG/7+9013L14v9/vC8MDQCnsK7iJZNkg2PqghaDSRAVZGkTwiIHnztxr72bGw7SAkChcXbbb6yIThLuxGnh6peEMQZeRJTlCqlwAtMruRQwKBgQC16BhfeCWE2Agpw9DV97hPcjZsAOfJaI2786mslFazn0tpqv53MsDA2P+o5TA9Hq+OCh2VmX4x5U+O3cF14Ato7lNCDGw+esvOhwdXnRz9iEjAzc8ule5KJPKK4s/yU61bnrsExwldVVm3k/zCmIljRrkJlcxBmpchKVq6UzwwaQKBgE0h5ruXNiXVS1TXgX8CNQPpeb3/stE9EbCfTJb6UcnQfIyd/g/z9PP8yvMicS/nPitgQNPfIHjLfiX8fTUIbuTHz02TaX3C3Gkoj7YeTvzaDWk5f8/SfIr0Cesj/F6bq/Hwy8AkJH80v5sLPejwAx+WImi6ChEaXSt4INB32zqhAoGBAJ5vnBrkDBqFVRgYIMWh7BkLfZnDjZSdAf/MKj+F+KuSmYNUzDYxhij94juS+uiiPQriFOLvQxlcRsVHmBOLP1bqaOZ2N0iiI/5+3sM8t2xr1c8S65UPd1OxzlqJbZGwstvAZKEKp1rXxzwa21yPHbqs4FGVq5ap5sR7JF4nzAbI";
$begin_private_key = "-----BEGIN PRIVATE KEY-----\r\n";
$end_private_key = "-----END PRIVATE KEY-----\r\n";


$fp = fopen($filename,'ab');
fwrite($fp,$begin_private_key,strlen($begin_private_key));

$raw = strlen($devPrvKey)/64;
$index = 0;
while($index <= $raw )
{
	$line = substr($devPrvKey,$index*64,64)."\r\n";
	if(strlen(trim($line)) > 0)
	fwrite($fp,$line,strlen($line)); 
	$index++;
}
fwrite($fp,$end_private_key,strlen($end_private_key));
fclose($fp);
?>

