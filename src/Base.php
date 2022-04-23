<?php
/**
 * Base.php
 *
 * This file is part of JSONBase.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 JSONBase
 * @license    https://github.com/muhammetsafak/JSONBase/blob/main/LICENSE  MIT
 * @version    1.0
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace JSONBase;

class Base
{

    private $dir_path;
    private $json;
    private static array $tables = [];
    private static $base;

    private static int $num_rows = 0;

    private static $select = '*';
    private static $temp_select = '*';
    private static string $from = '';
    private static array $where = [];
    private static array $results = [];

    public function __construct(string $dir_path)
    {
        $this->dir_path = rtrim($dir_path, '/');

        $tables = glob($this->dir_path . "/*.json");
        foreach($tables as $row){
            $table = substr($row, strlen($this->dir_path), -5);
            $table = ltrim($table, '/');
            $this->read($table);
        }
    }

    /**
     * It is used to generate a Json query. Specifies the columns in the results from the row() and rows() methods. "*" means all columns. If more than one column is desired, they must be separated by ",".
     *
     * @param string $select
     * @return self
     */
    public function select(string $select = '*'): self
    {
        static::$select = static::$temp_select;
        if($select == '*'){
            static::$select = '*';
        }else{
            if(static::$select == '*'){
                static::$select = '';
            }
            if($select != ""){
                static::$select .= ','.$select;
            }
            $selects = [];
            foreach(array_filter(explode(',', static::$select)) as $row){
                if(!in_array($row, $selects)){
                    $selects[] = trim($row);
                }
            }
            static::$select = implode(',', $selects);
        }
        static::$temp_select = static::$select;
        return $this;
    }

    /**
     * It is used to generate a Json query. Indicates on which Json the query to be executed with get() method will be executed.
     *
     * @param string $from
     * @return self
     */
    public function from(string $from): self
    {
        static::$from = $from;
        return $this;
    }

    /**
     * It is used to generate a Json query. Creates a where array for the query to be executed with the get() method. Multiple matches can be made using this method chaining.
     *
     * @param string $key
     * @param string $value
     * @return self
     */
    public function where(string $key, string $value): self
    {
        static::$where[$key] = $value;
        return $this;
    }

    /**
     * Execute the query created with the select(), from() and where() methods and get the results if any. You can access the results obtained with this method with the row(), rows() methods.
     *
     * @return self
     */
    public function get(): self
    {
        static::$results = $this->query(static::$from, static::$where);
        static::$where = [];
        static::$temp_select = '*';
        return $this;
    }

    /**
     * Returns the first result obtained as an associative array.
     *
     * @return array|false
     */
    public function row()
    {
        if(!isset(static::$results[0])){
            return false;
        }
        $index = static::$results[0];
        if(!isset(static::$base[static::$from][$index])){
            return false;
        }
        $res = static::$base[static::$from][$index];
        if(static::$select == '*'){
            return $res;
        }else{
            $selects = explode(',', static::$select);
            $result = [];
            foreach($selects as $row){
                if(isset($res[$row])){
                    $result[$row] = $res[$row];
                }else{
                    $result[$row] = null;
                }
            }
            return $result;
        }
    }

    /**
     * Returns the resulting results as an associative array.
     *
     * @return array|false
     */
    public function rows()
    {
        if(count(static::$results) > 0){
            $selects = explode(',', static::$select);
            $res = [];
            foreach(static::$results as $index){
                if(isset(static::$base[static::$from][$index])){
                    if(static::$select == '*'){
                        $res[] = static::$base[static::$from][$index];
                    }else{
                        $result = [];
                        foreach($selects as $row){
                            if(isset(static::$base[static::$from][$index][$row])){
                                $result[$row] = static::$base[static::$from][$index][$row];
                            }else{
                                $result[$row] = null;
                            }
                        }
                        $res[] = $result;
                    }
                }
            }
            return $res;
        }else{
            return false;
        }
    }

    /**
     * Returns the number of rows affected.
     *
     * @return int
     */
    public function num_rows(): int
    {
        return static::$num_rows;
    }

    /**
     * Adds an element to the end of a Json.
     *
     * @param string $from The name of the json file without extension. Example : setting for settings.json
     * @param array $data An associative array holding the data to be inserted.
     * @return bool
     */
    public function insert(string $from, array $data = []): bool
    {
        static::$base[$from][] = $data;
        static::$num_rows = 1;
        return $this->write($from);
    }

    /**
     * It adds multiple elements to the end of the json.
     *
     * @param string $from The name of the json file without extension. Example : setting for settings.json
     * @param array $data An associative multi array holding the data to be inserted.
     * @return bool
     */
    public function multiInsert(string $from, array $data = [])
    {
        static::$num_rows = 0;
        foreach($data as $row){
            if(is_array($row)){
                static::$base[$from][] = $row;
                static::$num_rows++;
            }else{
                throw new \Exception($row . "is not an array.");
            }
        }
        return $this->write($from);
    }

    /**
     * Updates a JSON array.
     *
     * @param string $from Specifies which json the update process will be done in.
     * @param array $where An associative array declaring which data to update.
     * @param array $data An associative array declaring the data to be updated.
     * @return bool
     */
    public function update(string $from, array $where, array $data = []): bool
    {
        $ids = $this->query($from, $where);
        if(count($ids) > 0){
            foreach($ids as $id){
                foreach($data as $key => $value){
                    static::$base[$from][$id][$key] = $value;
                }
            }
            return $this->write($from);
        }else{
            return false;
        }
    }

    /**
     * It is used to delete a record.
     *
     * @param string $from
     * @param array $where
     * @return bool
     */
    public function delete(string $from, array $where): bool
    {
        $ids = $this->query($from, $where);
        if(count($ids) > 0){
            foreach($ids as $id){
                if(isset(static::$base[$from][$id])){
                    static::$base[$from][$id] = ["deleted"];
                }
            }
            array_filter(static::$base[$from]);
            return $this->write($from);
        }else{
            return false;
        }
    }

    /**
     * Performs a relational query within a JSON array. Returns the Index of matches as an array.
     *
     * @param string $from
     * @param array $where
     * @return array
     */
    public function query(string $from, array $where): array
    {
        $res = [];
        if(count($where) == 1){
            for($i = 0; $i < count(static::$base[$from]); $i++){
                if(array_intersect_assoc(static::$base[$from][$i], $where)){
                    $res[] = $i;
                }
            }
        }elseif(count($where) > 1){
            for($i = 0; $i < count(static::$base[$from]); $i++){
                $add = true;
                foreach($where as $key => $value){
                    if(!array_intersect_assoc(static::$base[$from][$i], [$key => $value])){
                        $add = false;
                        break;
                    }
                }
                if($add){
                    $res[] = $i;
                }
            }
        }else{
            $res = static::$base[$from];
        }
        static::$num_rows = count($res);
        return $res;
    }

    /**
     * Create a table/json file
     *
     * @param string $name
     * @return bool
     */
    public function create_table(string $name): bool
    {
        $path = $this->dir_path . '/' . $name . '.json';
        $file = fopen($path, 'w+');
        $process = fwrite($file, '[]');
        fclose($file);
        if($process !== false){
            $this->read($name);
            return true;
        }else{
            return false;
        }
    }


    /**
     * Used to delete a table.
     *
     * @param string $from
     * @return bool
     */
    public function drop(string $from): bool
    {
        if(isset(static::$base[$from])){
            unset(static::$base[$from]);
            $path = $this->dir_path . '/' . $from . '.json';
            if(unlink($path)){
                unset(static::$tables[$from]);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * Used to empty a table.
     *
     * @param string $from
     * @return bool
     */
    public function truncate(string $from): bool
    {
        if(isset(static::$base[$from])){
            static::$base[$from] = [];
            return $this->write($from);
        }else{
            return false;
        }
    }

    /**
     * Used to rename a JSonBase file (table).
     *
     * @param string $from Old Name
     * @param string $rename New Name
     * @return bool
     */
    public function rename(string $from, string $rename): bool
    {
        static::$base[$rename] = static::$base[$from];
        static::$tables[$rename] = static::$tables[$from];
        unset(static::$base[$from]);
        unset(static::$tables[$from]);
        $fromPath = $this->dir_path . '/' . $from . '.json';
        $renamePath = $this->dir_path . '/' . $rename . '.json';
        return rename($fromPath, $renamePath);
    }

    /**
     * Used to copy a JSonBase file (table) with its content.
     *
     * @param string $from The name of the table to be copied
     * @param string $copyName
     * @return bool
     */
    public function copy(string $from, string $copyName): bool
    {
        static::$base[$copyName] = static::$base[$from];
        static::$tables[$copyName] = static::$tables[$from];
        $fromPath = $this->dir_path . '/' . $from . '.json';
        $copyPath = $this->dir_path . '/' . $copyName . '.json';
        return copy($fromPath, $copyPath);
    }

    /**
     * It allows you to download the JSonBase file.
     *
     * This method will try to initiate a download via the browser by adding a timestamp to the filename along with its contents.
     *
     * @param string $from
     */
    public function download(string $from): void
    {
        $path = $this->dir_path . '/' . $from . '.json';
        if(file_exists($path)){
            header("Content-length: ".filesize($path));
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.$from.'_'.date("Y-m-d_H-i-s").'.json"');
            readfile($path);
            exit;
        }
    }

    /**
     * Returns the loaded table names as an array.
     *
     * @return array
     */
    public function tables(): array
    {
        $names = [];
        foreach(static::$tables as $key => $value){
            $tables[] = $key;
        }
        return $names;
    }

    /**
     * Returns the total size of the table in bytes.
     *
     * @param string $from
     * @return int
     */
    public function size(string $from): int
    {
        $size = 0;
        if(isset(static::$tables[$from])){
            $size = static::$tables[$from];
        }
        return $size;
    }

    /**
     * Returns the total number of rows in the table.
     *
     * @param string $from
     * @return int
     */
    public function rowSize(string $from): int
    {
        $row_size = 0;
        if(isset(static::$base[$from])){
            $row_size = count(static::$base[$from]);
        }
        return $row_size;
    }

    /**
     * Reads the JSON file.
     *
     * @param string $from
     * @return void
     */
    private function read(string $from): void
    {
        $path = $this->dir_path . '/' . $from . '.json';
        $content = "";
        if (file_exists($path)) {
            $filesize = filesize($path);
            $file = fopen($path, "r");
            $content = fread($file, $filesize);
            fclose($file);
            $data = json_decode($content, true);
            static::$base[$from] = $data;

            if(!isset(static::$tables[$from])){
                static::$tables[$from] = $filesize;
            }
        }
    }

    /**
     * Writes the JSON file.
     *
     * @param string $from
     * @return bool
     */
    private function write(string $from): bool
    {
        $json = json_encode(static::$base[$from]);
        $path = $this->dir_path . '/' . $from . '.json';
        $file = fopen($path, 'w+');
        $json = str_replace(['["deleted"],', ',["deleted"]', ',["deleted"],'], [NULL, NULL, ','], (string)$json);
        $process = fwrite($file, $json);
        fclose($file);
        if($process !== false){
            static::$tables[$from] = strlen($json);
            return true;
        }else{
            return false;
        }
    }

}
