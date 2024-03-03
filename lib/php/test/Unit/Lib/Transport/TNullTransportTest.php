<?php

namespace Test\Thrift\Unit\Lib\Transport;

use PHPUnit\Framework\TestCase;
use Thrift\Exception\TTransportException;
use Thrift\Transport\TNullTransport;

class TNullTransportTest extends TestCase
{
    public function testIsOpen()
    {
        $transport = new TNullTransport();
        $this->assertTrue($transport->isOpen());
    }

    public function testOpen()
    {
        $transport = new TNullTransport();
        $this->assertNull($transport->open());
    }

    public function testClose()
    {
        $transport = new TNullTransport();
        $this->assertNull($transport->close());
    }

    public function testRead()
    {
        $transport = new TNullTransport();
        $this->expectException(TTransportException::class);
        $this->expectExceptionMessage("Can't read from TNullTransport.");
        $this->expectExceptionCode(0);
        $transport->read(1);
    }

    public function testWrite()
    {
        $transport = new TNullTransport();
        $this->assertNull($transport->write('test'));
    }
}
