<?php
/*
Simple PDO Wrapper
*/
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

    /*
    Runs the migration sql given the connection parameters
    $host - MYSQL Database host
    $user - MYSQL Database user
    $password - MYSQL Database password
    $database - MYSQL Database name/schema
    */
    public static function migrate($host, $user, $password, $database){
        //reads the contents of the migration sql file
        $sql = file_get_contents('migration.sql');
        $mysqli = new mysqli($host,$user,$password,$database);
        //Checks for the MYSQL connection/credentials
        if (mysqli_connect_errno()) { 
            printf("Connection failed: %s\n", mysqli_connect_error());
            exit();
        }
        //Executes all the commands on the migration sql file
        if ($mysqli->multi_query($sql)) {
            echo "Migration success \n";
        } else {
            echo "Migration error \n";
        }
    }

    /*
    Generates the temporary table used for importing the data from the file
    */
    private function generateTmpTable(){
        //Sql comand to drop the table if exists
        $sql1 = 'DROP TABLE IF EXISTS `tmp_data_import`;';
        //DDL to create the tmp_data_import table
        $sql2 = 'CREATE TABLE `tmp_data_import` (
          `mid` VARCHAR(18) NOT NULL,
          `dba` varchar(100) NOT NULL,
          `batch_date` date NOT NULL,
          `batch_ref_num` VARCHAR(24) NOT NULL,
          `trans_date` date NOT NULL,
          `trans_type` varchar(20) NOT NULL,
          `trans_card_type` varchar(2) NOT NULL,
          `trans_card_num` varchar(20) NOT NULL,
          `trans_amount` decimal(8,2) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
        //Executes the drop
        $this->database->run($sql1);
        //Executes the creation of the table
        $this->database->run($sql2);
    }

    /*
    Given an array of headers it sorts out the position of the mappings in the header
    */
    private function sortHeaders($headers){
        //clears the array
        $this->sorted = array();
        //For each mapping recieved search it in the headers array and store its position in the array
        foreach($this->mapping as $key=>$map){
            $newkey = array_search($map,$headers);
            $this->sorted[$key] = $newkey;
        }
    }

    /*
    Processes the given file, imports the data into a temporary table and then using sql moves the data into the
    different entities in the database 
    */
    public function processFile($file_location){
        print_r("Processing file\n");
        //Generates temporary table
        $this->generateTmpTable();
        //Reads the csv file
        $file = fopen($file_location,'r');
        //The first line are the headers
        $headers = fgetcsv($file);
        //Sorts the headers and mappings
        $this->sortHeaders($headers);
        //Sql command to insert data into the temporary table
        $sql = "INSERT INTO tmp_data_import (`mid`,`dba`,`batch_date`,`batch_ref_num`,`trans_date`, `trans_type`,`trans_card_type`,`trans_card_num`,`trans_amount`) VALUES (:mid,:dba,:bd,:brn,:td,:tt,:tct,:tcn,:ta)";
        $line_no = 1;
        //While the file can be read and separated using fgetcsv, stores it in an array
        while (($line = fgetcsv($file)) !== FALSE) {
            $line_no++;
            //We expect each line to have 9 columns
            if(count($line) == 9){
                //store each column in a variable
                $mid = $line[$this->sorted['mid']];
                $dba = $line[$this->sorted['dba']];
                $batch_date = $line[$this->sorted['batch_date']];
                $batch_ref_num = $line[$this->sorted['batch_ref_num']];
                $trans_date = $line[$this->sorted['trans_date']];
                $trans_type = $line[$this->sorted['trans_type']];
                $trans_card_type = $line[$this->sorted['trans_card_type']];
                $trans_card_num = $line[$this->sorted['trans_card_num']];
                $trans_amount = $line[$this->sorted['trans_amount']];
                $params = ['mid'=>$mid,'dba'=>$dba,'bd'=>$batch_date,'brn' => $batch_ref_num, 'td' => $trans_date,'tt'=>$trans_type,'tct'=>$trans_card_type,'tcn'=>$trans_card_num,'ta'=>$trans_amount];
                //execute the insert sql with the parameters
                $this->database->run($sql,$params);
            }else{
                print_r('Columns missing in line: '.$line_no."\n");
            }
        }
        //Close the file
        fclose($file);
        //SQL command to retrieve all the mechants in the tmp table and store them in the mechant table
        $merchant_sql = 'INSERT INTO `merchant` (`merchant_name`,`merchant_id`) SELECT DISTINCT(`dba`),`mid` FROM `tmp_data_import`;';
        //SQL command to retrieve all the batches in the tmp table and store them in the batch table
        $batch_sql = 'INSERT INTO `batch` (`ref_num`,`date`) SELECT DISTINCT(batch_ref_num),`batch_date`  FROM `tmp_data_import`;';
        //SQL command to retrieve all the transaction types in the tmp table and store them in the transaction type table
        $transaction_type_sql = 'INSERT INTO `transaction_type` (`name`) SELECT DISTINCT(`trans_type`) FROM `tmp_data_import`;';
        //SQL command to retrieve all the card types in the tmp table and store them in the card type table
        $card_type_sql = 'INSERT INTO `card_type` (`name`) SELECT DISTINCT(`trans_card_type`) FROM `tmp_data_import`;';
        //Finally SQL command to retrieve all the transactions inserted in the tmp table with relationships and store them in the transaction table
        $transaction_sql = 'INSERT INTO `transaction` (`batch_id`,`merchant_id`,`date`,`type`,`card_type`,`card_number`,`amount`) 
        SELECT b.`id`,m.`id`, tmp.`trans_date`, tt.`id`, ct.`id`, tmp.`trans_card_num`,tmp.`trans_amount` FROM `tmp_data_import` as tmp 
        LEFT JOIN `merchant` as m ON tmp.`dba` = m.`merchant_name` AND tmp.`mid` = m.`merchant_id`
        LEFT JOIN `batch` as b ON tmp.`batch_ref_num` = b.`ref_num` AND tmp.`batch_date` = b.`date`
        LEFT JOIN `transaction_type` tt ON tmp.`trans_type` = tt.`name`
        LEFT JOIN `card_type` ct ON tmp.`trans_card_type` = ct.`name`';
        //Run the SQL commands
        $this->database->run($merchant_sql);
        $this->database->run($batch_sql);
        $this->database->run($transaction_type_sql);
        $this->database->run($card_type_sql);
        $this->database->run($transaction_sql);
        //Drop the tmp data table
        $sql1 = 'DROP TABLE IF EXISTS `tmp_data_import`;';
        //$this->database->run($sql1);
        print_r("Process finished \n");
    }
    

}