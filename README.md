Ingress-Sentinels
=================

inserts data with damage email report in a sqlite database.


How to use?
----
* Run Composer from your application
* The database are used is SQLite. After run composer, run the following command to create the sqlite file:
```shell
migrate -e development
```
* Put the data access for the email account that concentrates the reports Ingress in test.php file and run this file with the following command:
```shell
php test.php
```
When the test.php script has finished running, you will have a database to parse all emails that are configured in the account.

TODO:
----
* Analysis of data from the database to identify anomaly
