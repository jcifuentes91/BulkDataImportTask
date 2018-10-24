<?php
class Database extends PDO
{
    public function __construct($host, $user = NULL, $password = NULL, $database = NULL, $options = [])
    {
        $default_options = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $options = array_replace($default_options, $options);
        $dsn = 'mysql:host='.$host.';dbname='.$database.';charset=utf8';
        parent::__construct($dsn, $user, $password, $options);
    }
    public function run($sql, $args = NULL)
    {
        if (!$args)
        {
             return $this->query($sql);
        }
        $stmt = $this->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }
}


Class BulkImport{
    private $mapping;
    private $sorted = array();
    private $database;

    public function __construct($database,$mapping){
        $this->database = $database;
        $this->mapping = $mapping;
    }

    public static function migrate($host, $user, $password, $database){
        $sql = file_get_contents('migration.sql');
        $mysqli = new mysqli($host,$user,$password,$database);
        if (mysqli_connect_errno()) { /* check connection */
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit();
        }

        /* execute multi query */
        if ($mysqli->multi_query($sql)) {
            echo "Migration success";
        } else {
            echo "Migration error";
        }
    }

    private function generateTmpTable(){
        $sql1 = 'DROP TABLE IF EXISTS `tmp_data_import`;';
        $sql2 = 'CREATE TABLE `tmp_data_import` (
          `mid` int(11) NOT NULL,
          `dba` varchar(100) NOT NULL,
          `batch_date` date NOT NULL,
          `batch_ref_num` int(11) NOT NULL,
          `trans_date` date NOT NULL,
          `trans_type` varchar(20) NOT NULL,
          `trans_card_type` varchar(2) NOT NULL,
          `trans_card_num` varchar(20) NOT NULL,
          `trans_amount` decimal(8,2) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        $this->database->run($sql1);
        $this->database->run($sql2);
    }

    private function sortHeaders($headers){
        $this->sorted = array();
        foreach($this->mapping as $key=>$map){
            $newkey = array_search($map,$headers);
            $this->sorted[$key] = $newkey;
        }
    }

    public function processFile($file_location){
        $this->generateTmpTable();
        $file = fopen($file_location,'r');
        $headers = fgetcsv($file);
        $this->sortHeaders($headers);
        
        //print_r($headers);
        $sql = "INSERT INTO tmp_data_import (`mid`,`dba`,`batch_date`,`batch_ref_num`,`trans_date`, `trans_type`,`trans_card_type`,`trans_card_num`,`trans_amount`) VALUES (?,?,?,?,?,?,?,?,?)";
        $line_no = 1;
        while (($line = fgetcsv($file)) !== FALSE) {
            $line_no++;
            if(count($line) == 9){
                $mid = $line[$this->sorted['mid']];
                $dba = $line[$this->sorted['dba']];
                $batch_date = $line[$this->sorted['batch_date']];
                $batch_ref_num = $line[$this->sorted['batch_ref_num']];
                $trans_date = $line[$this->sorted['trans_date']];
                $trans_type = $line[$this->sorted['trans_type']];
                $trans_card_type = $line[$this->sorted['trans_card_type']];
                $trans_card_num = $line[$this->sorted['trans_card_num']];
                $trans_amount = $line[$this->sorted['trans_amount']];
                $this->database->run($sql,[$mid,$dba,$batch_date,$batch_ref_num,$trans_date,$trans_type,$trans_card_type,$trans_card_num,$trans_amount]);
            }else{
                print_r('Columns missing in line: '.$line_no);
            }
        }
        fclose($file);
        $merchant_sql = 'INSERT INTO `merchant` (`merchant_name`,`merchant_id`) SELECT DISTINCT(`dba`),`mid` FROM `tmp_data_import`;';
        $batch_sql = 'INSERT INTO `batch` (`ref_num`,`date`) SELECT DISTINCT(batch_ref_num),batch_date  FROM `tmp_data_import`;';
        $transaction_type_sql = 'INSERT INTO `transaction_type` (`name`) SELECT DISTINCT(`trans_type`) FROM `tmp_data_import`;';
        $card_type_sql = 'INSERT INTO `card_type` (`name`) SELECT DISTINCT(`trans_card_type`) FROM `tmp_data_import`;';
        $transaction_sql = 'INSERT INTO `transaction` (`batch_id`,`merchant_id`,`date`,`type`,`card_type`,`card_number`,`amount`) 
        SELECT b.`id`,m.`id`, tmp.`trans_date`, tt.`id`, ct.`id`, tmp.`trans_card_num`,tmp.`trans_amount` FROM `tmp_data_import` as tmp 
        LEFT JOIN `merchant` as m ON tmp.`dba` = m.`merchant_name` AND tmp.`mid` = m.`merchant_id`
        LEFT JOIN `batch` as b ON tmp.`batch_ref_num` = b.`ref_num` AND tmp.`batch_date` = b.`date`
        LEFT JOIN `transaction_type` tt ON tmp.`trans_type` = tt.`name`
        LEFT JOIN `card_type` ct ON tmp.`trans_card_type` = ct.`name`';
        $this->database->run($merchant_sql);
        $this->database->run($batch_sql);
        $this->database->run($transaction_type_sql);
        $this->database->run($card_type_sql);
        $this->database->run($transaction_sql);
        $sql1 = 'DROP TABLE IF EXISTS `tmp_data_import`;';
        $this->database->run($sql1);
    }
    

}