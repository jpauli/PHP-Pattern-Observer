<?php
use Observer\Listeners\Mail;
use Observer\Listeners\Mail\Adapter\Mock as MailMock;
use PHPUnit\Framework\TestCase;

class MailListenerTest extends TestCase
{
    private Mail $maillistener;
    private MailMock $mock;

    public function setUp() : void
    {
        $this->maillistener = new Mail($this->mock = new MailMock('foo@foo.bar'));
    }

    public function testUpdate()
    {
        $this->maillistener->update(new MockErrorHandler);
        $this->assertContains('errortest', $this->mock->sent);
    }

    public function testInvalidEmailThrowsException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid Email address');
        new MailMock('foo');
    }
}
