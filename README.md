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

