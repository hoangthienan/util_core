<?php


namespace go1\util\tests;


class MockPDO extends \PDO
{
    public $dsn;
    public $username;
    public $password;
    public $options;

    public function __construct($dsn, $username = null, $password = null, $options = null)
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }
}
