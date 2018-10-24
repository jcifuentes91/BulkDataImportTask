# BulkDataImportTask
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
