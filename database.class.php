<?php

class database
{
    protected $connection = null;
    protected $table = '';
    protected $statement = null;
    protected $host = '';
    protected $username = '';
    protected $password = '';
    protected $name = '';
    protected $limit = 15;
    protected $offset = 0;
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->name = $config['name'];
        $this->connect();
        $this->connection->set_charset("utf8");
    }
    protected function connect()
    {
        $this->connection = new mysqli(
            $this->host, 
            $this->username, 
            $this->password, 
            $this->name
        );
        if($this->connection->connect_errno)
        {
            
            exit($this->connection->connect_errno);
        }
    }
    public function table($tableName)
    {
        $this->table =$tableName;
        return $this;
    }
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }
    protected function do($sql, $dataTypes, $values)
    {
        $this->statement = $this->connection->prepare($sql);
        $this->statement->bind_param($dataTypes, ...$values);
        $this->statement->execute();
        $this->resetQuery();
    }
    public function get()
    {
        $sql = "SELECT * FROM $this->table LIMIT ? OFFSET ?";
        $dataTypes = "ii";
        $values = [$this->limit, $this->offset];
        $this->do($sql, $dataTypes, $values);
        $result = $this->statement->get_result();
        $returnData = [];
        while ($row = $result->fetch_object())
        {
            $returnData[] = $row;
        }
        return $returnData;
    }
    public function where($data = [])
    {
        $keyValues = [];
        foreach($data as $key => $value)
        {
            $keyValues[] = $key.'= ?';
        }
        $fields = implode(', ', $keyValues);
        $sql = "SELECT * FROM $this->table WHERE $fields";
        $dataTypes = str_repeat('s',count($data));
        $values = array_values($data);
        $this->do($sql, $dataTypes, $values);
        $result = $this->statement->get_result();
        $returnData = [];
        while($row = $result->fetch_object())
        {
            $returnData[] = $row;
        }
        return $returnData;
    }
    public function insert($data = [])
    {
        $fields = implode(', ', array_keys($data));
        $valuesMark = implode(', ', array_fill(0, count($data), '?'));
        $values = array_values($data);
        $sql = "INSERT INTO $this->table($fields) VALUES ($valuesMark)";
        $dataTypes = str_repeat('s', count($data));
        $this->do($sql, $dataTypes, $values);
        return $this->statement->affected_rows;
    }
    public function deleteId($id)
    {
        $sql = "DELETE FROM $this->table WHERE id = ?";
        $this->statement = $this->connection->prepare($sql);
        $this->statement->bind_param('i', $id);
        $this->statement->execute();
        $this->resetQuery();
        return $this->statement->affected_rows;
    }
    public function updateRow($id, $data)
    {
        $keyValues = [];
        foreach ($data as $key => $value)
        {
            $keyValues[] = $key.'=?';
        }
        $setFields = implode(', ', $keyValues);
        $values = array_values($data);
        $values[] = $id;
        $sql = "UPDATE $this->table SET $setFields WHERE id = ?";
        $dataTypes = str_repeat('s', count($data)).'i';
        $this->do($sql, $dataTypes, $values);
        return $this->connection->affected_rows; 
    }
    public function resetQuery()
    {
        $this->table = '';
        $this->limit = 15;
        $this->offset = 0;
    }
}