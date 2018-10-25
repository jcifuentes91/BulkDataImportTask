# BulkDataImportTask
## INSTRUCTIONS:
CSV report:
- the first line contains headers always
- you should ensure that all required fields are present
- columns order is unknown
- file contains a list of transactions
- batch's transactions are always stored together

* Merchant1 (key - MERCHANT_ID)
  * Batch1 (key - BATCH_DATE & BATCH_REF_NUM)
     * Transaction1
     * Transaction2
   * Batch2
     * Transaction3
     * Transaction4
 * Merchant2 (key - MERCHANT_ID)
   * Batch3 (key - BATCH_DATE & BATCH_REF_NUM)
     * Transaction5
     * Transaction6
Your class:
- will receive a file name (with full path) and mappings (like $mapping)
- should be able to import a given file (if all required headers are present)  
- suggest a db structure and write SQL commands to create it 
- be able to process big files with low enough memory usage.

## HOW TO RUN THE TEST
- change the first 5 lines on task.php to your mysql connection parameters
```
$db_host = 'localhost'; //Mysql Host
$db_user = 'test_user'; //Mysql User
$db_pass = 'VIQTGgXTlN8'; //Mysql password
$database = 'test'; //Mysql database/schema
```
- run
``` 
php task.php
```

## QUERIES:
- display all transactions for a batch (merchant + date + ref num) date, type, card_type, card_number, amount 
```
SELECT merchant_id, 
	merchant_name, 
    batch_date, 
    ref_num as 'batch_ref_num',
    date as 'transaction_date', 
    transaction_type,
    card_type,
    card_number,
    amount FROM test.transaction_merge
WHERE ref_num = '307965163216534420635657' 
AND batch_date = '2018-05-05'
AND merchant_id = '344858307505959269';
```
- display stats for a batch per card type (VI - 2 transactions with $100 total, MC - 10 transaction with $200 total)
```
SELECT card_type,
	SUM(1) as 'transactions',
	SUM(amount) as 'total',
	CONCAT(card_type," - ",SUM(1)," transactions with $",FORMAT(SUM(amount),2)," total") as 'readable'
    FROM test.transaction_merge
WHERE ref_num = '865311392860455095554114' 
AND batch_date = '2018-05-05'
AND merchant_id = '79524081202206784'
GROUP BY card_type;
```
- display stats for a merchant and a given date range
```
SELECT merchant_id,merchant_name,
	SUM(1) as 'transactions',
	SUM(amount) as 'total',
    CONCAT(merchant_name," - ",SUM(1)," transactions with $",FORMAT(SUM(amount),2)," total") as 'readable'
    FROM test.transaction_merge
WHERE merchant_id = '79524081202206784'
AND date BETWEEN '2018-05-01' AND'2018-05-06'
```
- display top 10 merchants (by total amount) for a given date range merchant id, merchant name, total amount, number of transactions
```
SELECT merchant_id,
	merchant_name,
	FORMAT(SUM(amount),2) as 'total amount',
    SUM(1) as 'number of transactions'
    FROM test.transaction_merge
WHERE date BETWEEN '2018-05-01' AND'2018-05-06'
GROUP BY merchant_id
ORDER BY 'total amount' DESC
LIMIT 10;
```
