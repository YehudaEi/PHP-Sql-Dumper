<?php

/**
 * PHP SQL Dumper
 *
 * @copyright  Copyright (c) 2020 Yehuda Eisenberg (https://YehudaE.net)
 * @author     Yehuda Eisenberg
 * @license    MIT
 * @version    1.0
 * @link       https://github.com/YehudaEi/PHP-Sql-Dumper
 */

class SQLDumper{
    
    /**
     * MySqli db connection
     * 
     * @var mysqli MySqli db connection
     */
    private $DBConn;
    
    /**
     * Dump output file
     * 
     * @var string Dump output file
     */
    private $outFile;
    
    /**
     * Dump only this Databases
     * 
     * @var array Dump only this Databases
     */
    private $onlyDump;
    
    /**
     * Non-dump databases
     * 
     * @var array Non-dump databases
     */
    private $nonDump = ['information_schema', 'performance_schema', 'mysql', 'sys'];
    
    /**
     * Constructor function.
     * 
     * @param string $host mysql hostname
     * @param string $user username
     * @param string $pass password
     * @param string $outFile output file
     * 
     * @return void
     */
    public function __construct($host, $user, $pass, $outFile){
        $this->DBConn = new mysqli($host, $user, $pass);
        if($this->DBConn == false || empty($this->DBConn) || $this->DBConn->connect_error){
            http_response_code(500);
            die('Error connecting to the DB');
        }
        $this->DBConn->set_charset("utf8mb4");

        $this->outFile = $outFile;
        $this->write("", NULL);
    }
    
    /**
     * Dumping function
     * 
     * @param array $onlyDump (optional) dump only this Databases
     * 
     * @return bool or void (true if success or void if faile)
     */
    public function dump($onlyDump = array()){
        $this->onlyDump = $onlyDump;
        $DBs = $this->DBConn->query("SHOW DATABASES;");
        
        $databases = array();
    
        while ($row = $DBs->fetch_assoc()){
            if(!in_array($row['Database'], $this->nonDump) && empty($this->onlyDump)){
                $tables = $this->DBConn->query("SHOW TABLES FROM `{$row['Database']}`;")->fetch_all();
                $databases[$row['Database']] = $tables;
            }
            else if(!empty($this->onlyDump) && in_array($row['Database'], $this->onlyDump)){
                $tables = $this->DBConn->query("SHOW TABLES FROM `{$row['Database']}`;")->fetch_all();
                $databases[$row['Database']] = $tables;
            }
        }
        
        $this->write("-- -------------------------------------------------- --");
        $this->write("--                                                    --");
        $this->write("-- Created by PHP-Sql-Dumper by Yehuda Eisenberg.     --");
        $this->write("-- Repo: https://github.com/YehudaEi/PHP-Sql-Dumper   --");
        $this->write("--                                                    --");
        $this->write("-- -------------------------------------------------- --");
        $this->write("");
        $this->write("-- Generation time: " . date('r'));
        $this->write("-- Host: " . $this->DBConn->host_info);
        $this->write("/*!40030 SET NAMES UTF8 */;");
        $this->write("/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;");
        $this->write("/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;");
        $this->write("/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;");
        $this->write("/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;");
        $this->write("/*!40103 SET TIME_ZONE='" . date('P') . "' */;");
        $this->write("/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;");
        $this->write("/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;");
        $this->write("/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;");
        $this->write("/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;");
        $this->write("");
        
        
        foreach ($databases as $name => $tables){
            $this->DBConn->select_db($name);
            $this->write("-- ");
            $this->write("-- Database: " . $name);
            $this->write("-- ");
            $this->write("");
            $this->write($this->DBConn->query("SHOW CREATE DATABASE `{$name}`")->fetch_assoc()['Create Database'] . ";");
            $this->write("");
            $this->write("USE `{$name}`;");
            $this->write("");
        
            foreach ($tables as $table){
                $table = $table[0];
                $this->write("-- ");
                $this->write("-- Database: " . $name . ", Structure Table: " . $table);
                $this->write("-- ");
                $this->write("");
                
                $this->write("DROP TABLE IF EXISTS `{$table}`;");
                $res = $this->DBConn->query("SHOW CREATE TABLE `{$table}`")->fetch_assoc();
                $this->write($res['Create Table'] . ";");
                $this->write("");
                $this->write("");
                
                $this->write("-- ");
                $this->write("-- Database: " . $name . ", Data Table: " . $table);
                $this->write("-- ");
                $this->write("");
                
                $this->write("LOCK TABLES `{$table}` WRITE;");
                $data = $this->DBConn->query("SELECT * FROM `{$table}`;");
                
                $i = 0;
                $tmpRows = array();
                while ($row = $data->fetch_assoc()){
                    $rowData = array();
                    foreach ($row as $dat){
                        if($dat == null) $dat = "NULL";
                        $rowData[] = "'" . $this->DBConn->real_escape_string($dat) . "'";
                    }
                    $tmpRows[] = '(' . implode(",", $rowData) . ')';
                    $i++;
                    
                    if($i == 20){
                        $this->write("INSERT INTO `{$table}` VALUES \r\n" . implode(",\n", $tmpRows) . ';');
                        $this->write("");
                        $i = 0;
                        $tmpRows = array();
                    }
                }
                if(count($tmpRows) > 0){
                    $this->write("INSERT INTO `{$table}` VALUES \r\n" . implode(",\n", $tmpRows) . ';');
                }
                $this->write("UNLOCK TABLES;");
                
                $this->write("");
                $this->write("");
            }
        }
        
        
        $this->write("/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;");
        $this->write("/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;");
        $this->write("/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;");
        $this->write("/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;");
        $this->write("/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;");
        $this->write("/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;");
        $this->write("/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;");
        
        return true;
    }
    
    /**
     * Write to file
     * 
     * @param string $str string to write
     * @param string $flag file_put_contents flag
     * 
     * @return void
     */
    private function write($str, $flag = FILE_APPEND){
        file_put_contents($this->outFile, $str . "\r\n", $flag);
    }
}

?>
