# PHP-Sql-Dumper
SQL dumper in pure php (no shell command!)

## Use:
````php
<?php
require('SQLDumper.php');

$dumper = new SQLDumper(
    "localhost", //hostname
    "root", //username
    "pa$$w0rd", //password
    "SqlDump.sql" //output file
);
$success = $dumper->dump(); //dump all databases
$success = $dumper->dump(['db1', 'db2', 'otherDB']); //dump specific databases
````

## Example 
* [Daily backup to telegram (github gist)](https://gist.github.com/YehudaEi/ead01d7993f72575535309486efe822d)

dump all databases:
````php
$dumper = new SQLDumper("localhost", "root", "pa$$w0rd", "SqlDump.sql");
$success = $dumper->dump();
if($success) echo "The dump has been created";
````

dump specific databases:
````php
$dumper = new SQLDumper("localhost", "root", "pa$$w0rd", "SqlDump.sql");
$success = $dumper->dump(['db1', 'db2', 'otherDB']);
if($success) echo "The dump has been created";
````

## Contact
[sqldumper@yehudae.net](mailto:sqldumper@yehudae.net)

## License
MIT
