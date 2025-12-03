<?php
header('Content-Type: application/json');

define('TELEGRAM_API_KEY', '[REPLACE]');
define('ADMINS', array('[REPLACE]'));
define('SECURITY', array('[REPLACE]'));

global $db;
define('SQL', array('host' => '[REPLACE]', 'username' => '[REPLACE]', 'password' => '[REPLACE]', 'database' => '[REPLACE]'));
$db = mysqli_connect(SQL['host'], SQL['username'], SQL['password'], SQL['database']);
mysqli_query($db, "SET NAMES 'utf8mb4'");
mysqli_query($db, "SET CHARACTER SET utf8mb4");
mysqli_query($db, "SET SESSION collation_connection = 'utf8mb4_unicode_ci'");

if(isset($_GET['password'])) {
	$password = $_GET['password'];
	if($password == SECURITY) {
        $time = time();
		$fileName = "backup_$time.sql";
		$backup = exportDatabase();
		file_put_contents($fileName, $backup);
		$zip = "backup_$time.zip";
		zipFile($zip, array($fileName));
		$url = "https://".$_SERVER['HTTP_HOST']."/$zip";// [REPLACE] with your current directory
		$caption = "Backup generated at <b>".date("Y-m-d H:i:s")."</b>";
		$sent = 0;
		foreach(ADMINS as $user) {
		    $info = sendDocument($user, $url, $caption);
		    if($info->ok) $sent ++;
		}
		unlink($fileName);
		unlink($zip);
		echo json_encode(array('status' => true, 'result' => array('admins' => sizeof(ADMINS), 'sent' => $sent)));
	}
    else {
        echo json_encode(array('status' => false, 'result' => array('error' => 'invalid password')));
    }
}
else {
    echo json_encode(array('status' => false, 'result' => array('error' => 'invalid parameters')));
}

function exportDatabase($table = "") {
    global $db;
    $tables = array();
    if(empty($table)) {
        $tables_in_database = mysqli_query($db, "SHOW TABLES");
        if(mysqli_num_rows($tables_in_database) > 0) {
            while($row = mysqli_fetch_row($tables_in_database)) {
                array_push($tables, $row[0]);
            }
        } 
    }
    else {
        $existed_tables = array();
        foreach($table as $t) {
            if(mysqli_num_rows(mysqli_query($db, "SHOW TABLES LIKE '$table'")) == 1) {
                array_push($existed_tables, $t);
            }
        }
        $tables = $existed_tables;
    }
    $contents = "--\n-- Database: `".SQL['database']."`\n--\n-- --------------------------------------------------------\n\n\n\n";
    foreach($tables as $table) {
        $result = mysqli_query($db, "SELECT * FROM `$table`");
        $columns = mysqli_num_fields($result);
        $rows = mysqli_num_rows($result);
        $tresult = mysqli_fetch_row(mysqli_query($db, "SHOW CREATE TABLE `$table`"));
        $contents .= "--\n-- Table structure for table `$table`\n--\n\n";
        $contents .= $tresult[1].";\n\n\n\n";
        $insert_limit = 100;
        $insert_count = 0;
        $total_count  = 0;
        while($result_row = mysqli_fetch_row($result)) {
            if($insert_count == 0) {
                $contents .= "--\n-- Dumping data for table `$table`\n--\n\n";
                $contents .= "INSERT INTO `$table` VALUES ";
            }
            $insert_query = "";
            $contents .= "\n(";
            for($j = 0; $j < $columns; $j++) {
                $insert_query .= "'".str_replace("\n","\\n", addslashes($result_row[$j]))."',";
            }
            $insert_query = substr($insert_query, 0, -1)."),";
            if($insert_count == ($insert_limit - 1) || $insert_count == ($rows - 1) || $total_count == ($rows - 1)) {
                $contents .= substr($insert_query, 0, -1);
                $contents .= ";\n\n\n\n";
                $insert_count = 0;
            }
            else {
                $contents .= $insert_query;
                $insert_count++;
            }
            $total_count++;        
        }  
    }
    return $contents;
}
function zipFile($name, $files = array()) {
    $zip = new ZipArchive();
    $zip->open($name, ZipArchive::CREATE);
    foreach($files as $file) {
        $zip->addFile($file);
    }
    $zip->close();
    return true;
}
function bot($method, $datas = []) {
    $url = "https://api.telegram.org/bot".TELEGRAM_API_KEY."/".$method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $response = curl_exec($ch);
    if(curl_error($ch)) {
        var_dump(curl_error($ch));
    }
    else {
        return json_decode($response);
    }
}
function sendChatAction($chat_id, $action) {
    return bot('sendChatAction', [
        'chat_id' => $chat_id,
        'action' => $action
    ]);
}
function sendDocument($chat_id, $document, $caption, $reply = -1, $key = null) {
    sendChatAction($chat_id, "upload_document");
	return bot('sendDocument', [
        'chat_id' => $chat_id,
        'document' => $document,
        'reply_markup' => $key,
        'caption' => $caption,
        'parse_mode' => "HTML",
        'reply_to_message_id' => $reply
    ]);
}
function downloadFile($url) {
    $path = parse_url($url, PHP_URL_PATH);
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $filename = pathinfo($path, PATHINFO_FILENAME);
    $newfname = $filename.'.'.$extension;
    if(file_exists($newfname)) {
        return $newfname;
    }
    $file = fopen ($url, 'rb');
    if($file) {
        $newf = fopen($newfname, 'wb');
        if($newf) {
            while(!feof($file)) {
                fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
            }
        }
    }
    if($file) {
        fclose($file);
    }
    if($newf) {
        fclose($newf);
    }
    return $newfname;
}
