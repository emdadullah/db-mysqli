<?php

class DbCache {

    static $servername = DB_HOST;
    static $username = DB_USER;
    static $password = DB_PASSWORD;
    static $dbname = DB_NAME;

    function __construct() {

    }

    function setCache($cacheName) {
        $conn = new mysqli(self::$servername, self::$username, self::$password, self::$dbname);

        $sql = 'CREATE TABLE IF NOT EXISTS `cached_data` (
                `cache_key` varchar(200) DEFAULT NULL,
                `value` longblob,
                `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                `expiration` int(11) DEFAULT NULL,
                KEY `cache_key` (`cache_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8';

        $conn->query($sql);
        $conn->close();
    }

    function retrieve($cacheKey) {
        $conn = new mysqli(self::$servername, self::$username, self::$password, self::$dbname);
        $sql = "SELECT value FROM cached_data WHERE cache_key = ? order by timestamp desc limit 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $cacheKey);
        $stmt->execute();
        $result = $stmt->get_result();

        $return = NULL;

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $return = json_decode($row['value'], TRUE);
                $isValid = json_last_error() === JSON_ERROR_NONE;  /// check data un valid json format
                if($isValid == FALSE){
                    $return =  FALSE;
                }
//                if($cacheKey == 'GetAllLotData-By-8-33-101-102-34-35-106') {
//                    var_dump($row['value']);
//                }
                break;
            }
        }

        $stmt->close();
        $conn->close();

        return $return;
    }

    function retrieveAll() {
        $conn = new mysqli(self::$servername, self::$username, self::$password, self::$dbname);
        $sql = "SELECT value FROM cached_data";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $return = array();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $return[] = $row['value'];
            }
        }

        $stmt->close();
        $conn->close();

        return $return;
    }

    function dbConnection(){
        $conn = new mysqli(self::$servername, self::$username, self::$password, self::$dbname);
        return $conn;
    }


    function storeWithTransection($cacheKey, $data, $conn) {

//        if(!isset($conn)) {
//            $conn = new mysqli(self::$servername, self::$username, self::$password, self::$dbname);
//        }
        $query = FALSE;
        $refreshTime = 432000;

        if(count($data) > 0 && !empty($data)){
            $sql_del = "DELETE FROM cached_data WHERE cache_key = ?";
            $stmt_del = $conn->prepare($sql_del);
            $stmt_del->bind_param("s", $cacheKey);
            $stmt_del->execute();

            $sql = "INSERT INTO cached_data (cache_key, value, expiration) VALUES (?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $cacheKey, json_encode($data), $refreshTime);
            $stmt->execute();

            $stmt_del->close();
            $stmt->close();

            if($conn->affected_rows){
                $query = TRUE;
            }
        }
        return $query;
        //$conn->close();
    }

    function store($cacheKey, $data, $refreshTime) {
        $conn = new mysqli(self::$servername, self::$username, self::$password, self::$dbname);

        $sql_del = "DELETE FROM cached_data WHERE cache_key = ?";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->bind_param("s", $cacheKey);
        $stmt_del->execute();

        $sql = "INSERT INTO cached_data (cache_key, value, expiration) VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $cacheKey, json_encode($data), $refreshTime);
        $stmt->execute();

        $stmt_del->close();
        $stmt->close();
        $conn->close();
    }

    function eraseAll() {
        $conn = new mysqli(self::$servername, self::$username, self::$password, self::$dbname);

        $sql_del = "DELETE FROM cached_data";
        $stmt_del = $conn->prepare($sql_del);
        $stmt_del->execute();

        $stmt_del->close();
        $conn->close();
    }

    function isCached($cacheKey) {
        $conn = new mysqli(self::$servername, self::$username, self::$password, self::$dbname);
        $sql = "SELECT 'x' FROM cached_data WHERE cache_key = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $cacheKey);
        $stmt->execute();
        $result = $stmt->get_result();

        $return = FALSE;

        if ($result->num_rows > 0 ) {
            $return = TRUE;
        }

        $stmt->close();
        $conn->close();

        return $return;
    }

    function getCacheDate(){
        $conn = new mysqli(self::$servername, self::$username, self::$password, self::$dbname);
        $sql = "SELECT timestamp FROM cached_data order by timestamp desc limit 1";

        $stmt = $conn->prepare($sql);
        //$stmt->bind_param('s');
        $stmt->execute();
        $result = $stmt->get_result();

        //var_dump($result);
        $return = NULL;

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $return = json_decode($row['value'], TRUE);
                break;
            }
        }

        $stmt->close();
        $conn->close();

        return $return;
    }
}
