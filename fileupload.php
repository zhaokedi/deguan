<?php  
$base_path = "./Uploads/"; //接收文件目录  
$target_path = $base_path . basename ( $_FILES ['uploadfile'] ['name'] );  
if (move_uploaded_file ( $_FILES ['uploadfile'] ['tmp_name'], $target_path )) {  
    $array = array ("code" => "1", "message" => $_FILES ['uploadfile'] ['name'] );  
    echo json_encode ( $array );  
} else {  
    $array = array ("code" => "0", "message" => "There was an error uploading the file, please try again!" . $_FILES ['uploadfile'] ['error'] );  
    echo json_encode ( $array );  
}  
?>  