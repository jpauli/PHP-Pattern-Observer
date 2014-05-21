<?php
use Observer\Listeners\Db;
use PHPUnit\Framework\TestCase;

class DbListenerTest extends TestCase
{
    private Db $dblistener;

    public function setUp() : void
    {
        $this->dblistener = new Db($db = new PDO('sqlite::memory:'), 'error', 'nom');
        $db->exec("CREATE TABLE error (nom TEXT)");
    }

    public function testUpdate()
    {
        $this->dblistener->update(new MockErrorHandler);
        $this->assertEquals('errortest', $this->dblistener->getPDO()->query("SELECT nom FROM error")->fetch(PDO::FETCH_COLUMN, 1));
    }

    public function testTostring()
    {
        $this->assertMatchesRegularExpression("|error|", (string)$this->dblistener);
    }
}
