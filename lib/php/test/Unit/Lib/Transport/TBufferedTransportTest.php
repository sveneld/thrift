<?php

namespace Test\Thrift\Unit\Lib\Transport;

use PHPUnit\Framework\TestCase;
use Thrift\Transport\TBufferedTransport;
use Thrift\Transport\TTransport;

class TBufferedTransportTest extends TestCase
{
    public function testIsOpen()
    {
        $transport = $this->createMock(TTransport::class);
        $bufferedTransport = new TBufferedTransport($transport);

        $transport
            ->expects($this->once())
            ->method('isOpen')
            ->willReturn(true);

        $this->assertTrue($bufferedTransport->isOpen());
    }

    public function testOpen()
    {
        $transport = $this->createMock(TTransport::class);
        $bufferedTransport = new TBufferedTransport($transport);

        $transport
            ->expects($this->once())
            ->method('open')
            ->willReturn(null);

        $this->assertNull($bufferedTransport->open());
    }

    public function testClose()
    {
        $transport = $this->createMock(TTransport::class);
        $bufferedTransport = new TBufferedTransport($transport);

        $transport
            ->expects($this->once())
            ->method('close')
            ->willReturn(null);

        $this->assertNull($bufferedTransport->close());
    }

    public function testPutBack()
    {
        $transport = $this->createMock(TTransport::class);
        $bufferedTransport = new TBufferedTransport($transport);
        $bufferedTransport->putBack('test');

        $ref = new \ReflectionClass($bufferedTransport);
        $property = $ref->getProperty('rBuf_');
        $property->setAccessible(true);
        $this->assertEquals('test', $property->getValue($bufferedTransport));

        $bufferedTransport->putBack('abcde');
        $this->assertEquals('abcdetest', $property->getValue($bufferedTransport));
    }

    /**
     * @dataProvider readAllDataProvider
     */
    public function testReadAll(
        $startBuffer,
        $readLength,
        $bufferReadLength,
        $bufferReadResult,
        $expectedBufferValue,
        $expectedRead
    ) {
        $transport = $this->createMock(TTransport::class);
        $bufferedTransport = new TBufferedTransport($transport);
        $bufferedTransport->putBack($startBuffer);

        $transport
            ->expects($bufferReadLength > 0 ? $this->once() : $this->never())
            ->method('readAll')
            ->with($bufferReadLength)
            ->willReturn($bufferReadResult);

        $this->assertEquals($expectedRead, $bufferedTransport->readAll($readLength));

        $ref = new \ReflectionClass($bufferedTransport);
        $property = $ref->getProperty('rBuf_');
        $property->setAccessible(true);
        $this->assertEquals($expectedBufferValue, $property->getValue($bufferedTransport));
    }

    public function readAllDataProvider()
    {
        yield 'buffer empty' => [
            'startBuffer' => '',
            'readLength' => 5,
            'bufferReadLength' => 5,
            'bufferReadResult' => '12345',
            'expectedBufferValue' => '',
            'expectedRead' => '12345',
        ];
        yield 'buffer have partly loaded data' => [
            'startBuffer' => '12345',
            'readLength' => 10,
            'bufferReadLength' => 5,
            'bufferReadResult' => '67890',
            'expectedBufferValue' => '',
            'expectedRead' => '1234567890',
        ];
        yield 'buffer fully readed' => [
            'startBuffer' => '12345',
            'readLength' => 5,
            'bufferReadLength' => 0,
            'bufferReadResult' => '',
            'expectedBufferValue' => '',
            'expectedRead' => '12345',
        ];
        yield 'request less data that we have in buffer' => [
            'startBuffer' => '12345',
            'readLength' => 3,
            'bufferReadLength' => 0,
            'bufferReadResult' => '',
            'expectedBufferValue' => '45',
            'expectedRead' => '123',
        ];
    }

    /**
     * @dataProvider readDataProvider
     */
    public function testRead(
        $readBufferSize,
        $startBuffer,
        $readLength,
        $bufferReadResult,
        $expectedBufferValue,
        $expectedRead
    ) {
        $transport = $this->createMock(TTransport::class);
        $bufferedTransport = new TBufferedTransport($transport, $readBufferSize);
        $bufferedTransport->putBack($startBuffer);

        $transport
            ->expects(empty($startBuffer) > 0 ? $this->once() : $this->never())
            ->method('read')
            ->with($readBufferSize)
            ->willReturn($bufferReadResult);

        $this->assertEquals($expectedRead, $bufferedTransport->read($readLength));

        $ref = new \ReflectionClass($bufferedTransport);
        $property = $ref->getProperty('rBuf_');
        $property->setAccessible(true);
        $this->assertEquals($expectedBufferValue, $property->getValue($bufferedTransport));
    }

    public function readDataProvider()
    {
        yield 'buffer empty' => [
            'readBufferSize' => 10,
            'startBuffer' => '',
            'readLength' => 5,
            'bufferReadResult' => '12345',
            'expectedBufferValue' => '',
            'expectedRead' => '12345',
        ];
        yield 'buffer read partly' => [
            'readBufferSize' => 10,
            'startBuffer' => '',
            'readLength' => 5,
            'bufferReadResult' => '1234567890',
            'expectedBufferValue' => '67890',
            'expectedRead' => '12345',
        ];
        yield 'buffer fully readed' => [
            'readBufferSize' => 10,
            'startBuffer' => '12345',
            'readLength' => 5,
            'bufferReadResult' => '',
            'expectedBufferValue' => '',
            'expectedRead' => '12345',
        ];
    }

    /**
     * @dataProvider writeDataProvider
     */
    public function testWrite(
        $writeBufferSize,
        $writeData,
        $bufferedTransportCall,
        $expectedWriteBufferValue
    ) {
        $transport = $this->createMock(TTransport::class);
        $bufferedTransport = new TBufferedTransport($transport, 512, $writeBufferSize);

        $transport
            ->expects($this->exactly($bufferedTransportCall))
            ->method('write')
            ->with($writeData)
            ->willReturn(null);

        $this->assertNull($bufferedTransport->write($writeData));

        $ref = new \ReflectionClass($bufferedTransport);
        $property = $ref->getProperty('wBuf_');
        $property->setAccessible(true);
        $this->assertEquals($expectedWriteBufferValue, $property->getValue($bufferedTransport));
    }

    public function writeDataProvider()
    {
        yield 'store data in buffer' => [
            'writeBufferSize' => 10,
            'writeData' => '12345',
            'bufferedTransportCall' => 0,
            'expectedWriteBufferValue' => '12345',
        ];
        yield 'send data to buffered transport' => [
            'writeBufferSize' => 10,
            'writeData' => '12345678901',
            'bufferedTransportCall' => 1,
            'expectedWriteBufferValue' => '',
        ];
    }

    /**
     * @dataProvider flushDataProvider
     */
    public function testFlush(
        $writeBuffer
    ) {
        $transport = $this->createMock(TTransport::class);
        $bufferedTransport = new TBufferedTransport($transport, 512, 512);
        $ref = new \ReflectionClass($bufferedTransport);
        $property = $ref->getProperty('wBuf_');
        $property->setAccessible(true);
        $property->setValue($bufferedTransport, $writeBuffer);

        $transport
            ->expects(!empty($writeBuffer) ? $this->once() : $this->never())
            ->method('write')
            ->with($writeBuffer)
            ->willReturn(null);

        $transport
            ->expects($this->once())
            ->method('flush')
            ->willReturn(null);

        $this->assertNull($bufferedTransport->flush());

        $this->assertEquals('', $property->getValue($bufferedTransport));
    }

    public function flushDataProvider()
    {
        yield 'empty buffer' => [
            'writeBuffer' => '',
        ];
        yield 'not empty buffer' => [
            'writeBuffer' => '12345',
        ];
    }
}
