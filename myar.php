<?php
/**
 * Myar - A Lightweight MySQL Data Library
 *
 * Copyright (c) 2011, Geoff Doty
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 *  * Redistributions of source code must retain the above copyright notice, this list of
 *    conditions and the following disclaimer.
 *
 *  * Redistributions in binary form must reproduce the above copyright notice, this list
 *    of conditions and the following disclaimer in the documentation and/or other materials
 *    provided with the distribution.
 *
 *  * Neither the name of the SimplePie Team nor the names of its contributors may be used
 *    to endorse or promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS
 * OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS
 * AND CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
 * OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Geoff Doty <n2geoff@gmail.com>
 * @copyright 2011 Geoff Doty
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @todo write documentation
 */

class Myar {

    private $dbh = NULL;
    public $last_query = NULL;

    public function __construct($host = NULL, $username = NULL, $passwd = NULL, $dbname = '', $port = 3306, $socket = NULL)
    {
        $host       = isset($host)     ? $host     : ini_get('mysqli.default_host');
        $username   = isset($username) ? $username : ini_get('mysqli_default_user');
        $passwd     = isset($passwd)   ? $passwd   : ini_get('mysqli_default_pw');
        $dbame      = isset($dbname)   ? $dbname   : '';
    
        $this->dbh = new mysqli($host, $username, $passwd, $dbname);
        return $this->dbh;
    }
    
    public function insert($table, $data) 
    {
        $table = $this->_tablize($table);
    
        $keys = implode(', ', array_keys($data));
        $vals = implode(', ', array_values($data));
        
        return $this->dbh->query("INSERT INTO {$table} ({$keys}) VALUES ({$vals})");
    }
    
    public function update($table, $data, $where) 
    {
        $table = $this->_tablize($table);
    
        $set = array();
        foreach($data as $key => $value)
        {
            $set[] = '`' . $key . '`=' . $this->_escape($value);
        }

        if(is_string($where))
        {
            $where = $this->_escape($where);
        } 
        elseif(is_array($where))
        {
            
        }    
        
        $set = implode(', ', $set);
        return $this->dbh->query("UPDATE {$table} SET {$set} WHERE {$where}");
    }
    
    public function delete($table, $where) 
    {
        return $this->dbh->query("DELETE FROM {$table} WHERE {$where}");
    }
        
    public function select($table, $data = NULL, $where = NULL) 
    {
        if($data === NULL) { $data = '*';}

        $vals = implode(', ', array_values($data));

        return $this->dbh->query("SELECT {$data} FROM {$table} WHERE {$where}");
    }
    
    public function query($sql) 
    {
        $this->last_query = $sql; //store query for debugging

        $result = mysqli_query($this->dbh, $sql);
        
        if($result)
        {
            return new Myar_Result($result);
        }
        else
        {
            return FALSE;
        }
    }
    
    public function affected_rows()
    {
        return mysqli_affected_rows($this->dbh);
    }
    
    private function _where($where)
    {
        
    }
    
    /******************************************************************************
    * Security
    *******************************************************************************/
    
    private function _tablize($table) 
    {
        $table = str_replace('`', '', $table);
        
        if(strpos($table, '.')) 
        {
          list($table_db, $table_table) = explode('.', $table, 2);
          $table = "`{$table_db}`.`{$table_table}`";
        } 
        else 
        {
          $table = "`{$table}`";
        }
    
        return $table;
   }

    private function _escape($value) 
    {
        if(is_string($value))
        {
            $value = "'" . $this->dbh->real_escape_string($value) . "'";
        }
        elseif(is_bool($value))
        {
            $value = ($value === FALSE) ? 0 : 1;
        }
        elseif(is_null($value))
        {
            $value = 'NULL';
        }

        return $value;
    }
    
    public function error()
    {
        return $this->dbh->error;
    }
}

class Myar_Result {

    private $result_object = NULL;
    public $num_rows  = 0;
    private $insert_id = 0;
    
    public function __construct(&$result = NULL)
    {
        if(is_object($result))
        {
            $this->result_object = $result;
            $this->num_rows = $result->num_rows;
        }
        else
        {
            $this->num_rows = 0;
            $this->result = array();
        }
    }
    
    public function result()
	{
        $data = array();
        while($row = mysqli_fetch_object($this->result_object))
        {
            $data[] = $row;
        }
    
        return $data;
	}
    
    public function result_array()
    {
        $data = array();
        while($row = mysqli_fetch_array($this->result_object, MYSQLI_ASSOC))
        {
            $data[] = $row;
        }
    
        return $data;
    }
    
    public function num_rows()
    {
        return $this->num_rows;
    }    
    
    public function insert_id()
    {
        return $this->insert_id;
    }
    
    public function free()
    {
        mysqli_free_result($this->result_object);
        $this->result       = NULL;
        $this->num_rows     = 0;
        $this->insert_id    = 0;
    }
}