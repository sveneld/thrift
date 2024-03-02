<?php

namespace Test\Thrift\Unit\Lib\Transport;


use PHPUnit\Framework\TestCase;
use Thrift\Exception\TException;
use Thrift\Exception\TTransportException;
use Thrift\Transport\TSSLSocket;

class TSSLSocketTest extends TestCase
{
    /**
     * @dataProvider openExceptionDataProvider
     */
    public function testOpenException(
        $host,
        $port,
        $context,
        $debugHandler,
        $expectedException,
        $expectedMessage,
        $expectedCode
    ) {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);
        $this->expectExceptionCode($expectedCode);

        $socket = new TSSLSocket(
            $host,
            $port,
            $context,
            $debugHandler
        );
        $socket->open();
    }

    public function openExceptionDataProvider()
    {
        yield 'host is empty' => [
            '',
            9090,
            null,
            null,
            TTransportException::class,
            'Cannot open null host',
            TTransportException::NOT_OPEN,
        ];
        yield 'port is not positive' => [
            'localhost',
            0,
            null,
            null,
            TTransportException::class,
            'Cannot open without port',
            TTransportException::NOT_OPEN,
        ];
        yield 'connection failure' => [
            'nonexistent-host',
            9090,
            null,
            null,
            TException::class,
            'TSocket: Could not connect to',
            TTransportException::UNKNOWN,
        ];
    }

    public function testDoubleConnect(): void
    {
        $host = 'localhost';
        $port = 9090;
        $context = null;
        $debugHandler = null;
        $transport = new TSSLSocket(
            $host,
            $port,
            $context,
            $debugHandler
        );

        $transport->open();
        $this->expectException(TTransportException::class);
        $this->expectExceptionMessage('Socket already connected');
        $this->expectExceptionCode(TTransportException::ALREADY_OPEN);
        $transport->open();
    }

    public function testDebugHandler()
    {
        $host = 'nonexistent-host';
        $port = 9090;
        $context = null;

        $debugHandler = function ($error) {
            $this->assertEquals(
                'TSocket: Could not connect to ssl://nonexistent-host:9090 (Connection refused [999])',
                $error
            );
        };
        $transport = new TSSLSocket(
            $host,
            $port,
            $context,
            $debugHandler
        );
        $transport->setDebug(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('TSocket: Could not connect to');
        $this->expectExceptionCode(0);
        $transport->open();
    }

    public function testOpenWithContext()
    {
        $host = 'self-signed-localhost';
        $port = 9090;
        $context = stream_context_create(
            [
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => true,
                ],
            ]
        );
        $debugHandler = null;
        $transport = new TSSLSocket(
            $host,
            $port,
            $context,
            $debugHandler
        );

        $transport->open();
        $this->assertTrue($transport->isOpen());
    }

    /**
     * @dataProvider hostDataProvider
     */
    public function testGetHost($host, $expected)
    {
        $port = 9090;
        $context = null;
        $debugHandler = null;
        $transport = new TSSLSocket(
            $host,
            $port,
            $context,
            $debugHandler
        );
        $this->assertEquals($expected, $transport->getHost());
    }

    public function hostDataProvider()
    {
        yield 'localhost' => ['localhost', 'ssl://localhost'];
        yield 'ssl_localhost' => ['ssl://localhost', 'ssl://localhost'];
        yield 'http_localhost' => ['http://localhost', 'http://localhost'];
    }
}

//redeclare core functions for testing

namespace Thrift\Transport;

{
    function stream_socket_client(
        string $address,
        &$error_code,
        &$error_message,
        ?float $timeout,
        int $flags = STREAM_CLIENT_CONNECT,
        $context = null
    ) {
        if ($address === 'ssl://nonexistent-host:9090') {
            $error_code = 999;
            $error_message = 'Connection refused';

            return false;
        }

        return fopen('php://memory', 'r+');
    }
}
