<?php

use PHPUnit\Framework\TestCase;

class FileListenerTest extends TestCase
{
    const TEST_FILE = "foo.txt";
    private \Observer\Listeners\File $filelistener;

    public function setUp() : void
    {
        @unlink(_FILES_PATH . '/' . self::TEST_FILE);
        $this->filelistener = new Observer\Listeners\File(_FILES_PATH . '/' . self::TEST_FILE);
    }

    public function assertPreConditions() : void
    {
        $this->assertMatchesRegularExpression('#'.self::TEST_FILE.'#', (string)$this->filelistener);
    }

    public function testUpdateWritesToAFile()
    {
        $this->filelistener->update(new MockErrorHandler);
        $this->assertStringEqualsFile(_FILES_PATH . '/' . self::TEST_FILE, MockErrorHandler::ERRORMSG.PHP_EOL);
    }
}
