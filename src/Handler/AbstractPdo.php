<?php
namespace Panadas\SessionModule\Handler;

abstract class AbstractPdo implements \SessionHandlerInterface
{

    private $pdo;
    private $tableName;

    public function __construct(\PDO $pdo, $tableName = "session")
    {
        $this
            ->setPdo($pdo)
            ->setTableName($tableName);
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    protected function setPdo(\PDO $pdo)
    {
        $this->pdo = $pdo;

        return $this;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    protected function setTableName($tableName)
    {
        $this->tableName = $tableName;

        return $this;
    }

    public function open($savePath, $id)
    {
        return true;
    }

    public function close()
    {
        return true;
    }
}
