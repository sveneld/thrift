<?php

/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package thrift.transport
 */

namespace Test\Thrift\Unit\Lib\Transport;

use PHPUnit\Framework\TestCase;
use Test\Thrift\Unit\Lib\Transport\Fixtures\TestStream;
use Thrift\Exception\TException;
use Thrift\Exception\TTransportException;
use Thrift\Transport\TSocket;

class TSocketTest extends TestCase
{
    /**
     * @dataProvider openExceptionDataProvider
     */
    public function testOpenException(
        $host,
        $port,
        $persist,
        $debugHandler,
        $expectedException,
        $expectedMessage,
        $expectedCode
    ) {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);
        $this->expectExceptionCode($expectedCode);

        $socket = new TSocket(
            $host,
            $port,
            $persist,
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
            false,
            TTransportException::class,
            'Cannot open null host',
            TTransportException::NOT_OPEN,
        ];
        yield 'port is not positive' => [
            'localhost',
            0,
            false,
            null,
            TTransportException::class,
            'Cannot open without port',
            TTransportException::NOT_OPEN,
        ];
        yield 'connection failure' => [
            'nonexistent-host',
            9090,
            false,
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
        $persist = false;
        $debugHandler = null;
        $transport = new TSocket(
            $host,
            $port,
            $persist,
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
        $false = false;

        $debugHandler = function ($error) {
            $this->assertEquals(
                'TSocket: Could not connect to nonexistent-host:9090 (Connection refused [999])',
                $error
            );
        };
        $transport = new TSocket(
            $host,
            $port,
            $false,
            $debugHandler
        );
        $transport->setDebug(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('TSocket: Could not connect to');
        $this->expectExceptionCode(0);
        $transport->open();
    }

    public function testOpenPersist()
    {
        $host = 'persist-localhost';
        $port = 9090;
        $persist = true;
        $debugHandler = null;
        $transport = new TSocket(
            $host,
            $port,
            $persist,
            $debugHandler
        );

        $transport->open();
        $this->assertTrue($transport->isOpen());
    }

    public function testSetHandle()
    {
        $host = 'localhost';
        $port = 9090;
        $persist = false;
        $debugHandler = null;
        $transport = new TSocket(
            $host,
            $port,
            $persist,
            $debugHandler
        );

        $this->assertFalse($transport->isOpen());
        $transport->setHandle(fopen('php://memory', 'r+'));
        $this->assertTrue($transport->isOpen());
    }

    public function testSetSendTimeout()
    {
        $host = 'localhost';
        $port = 9090;
        $persist = false;
        $debugHandler = null;
        $transport = new TSocket(
            $host,
            $port,
            $persist,
            $debugHandler
        );

        $transport->setSendTimeout(9999);
        $reflector = new \ReflectionClass($transport);
        $property = $reflector->getProperty('sendTimeoutSec_');
        $property->setAccessible(true);
        $this->assertEquals(9.0, $property->getValue($transport));
        $property = $reflector->getProperty('sendTimeoutUsec_');
        $property->setAccessible(true);
        $this->assertEquals(999000, $property->getValue($transport));
    }

    public function testSetRecvTimeout()
    {
        $host = 'localhost';
        $port = 9090;
        $persist = false;
        $debugHandler = null;
        $transport = new TSocket(
            $host,
            $port,
            $persist,
            $debugHandler
        );

        $transport->setRecvTimeout(9999);
        $reflector = new \ReflectionClass($transport);
        $property = $reflector->getProperty('recvTimeoutSec_');
        $property->setAccessible(true);
        $this->assertEquals(9.0, $property->getValue($transport));
        $property = $reflector->getProperty('recvTimeoutUsec_');
        $property->setAccessible(true);
        $this->assertEquals(999000, $property->getValue($transport));
    }

    /**
     * @dataProvider hostDataProvider
     */
    public function testGetHost($host, $expected)
    {
        $port = 9090;
        $persist = false;
        $debugHandler = null;
        $transport = new TSocket(
            $host,
            $port,
            $persist,
            $debugHandler
        );
        $this->assertEquals($expected, $transport->getHost());
    }

    public function hostDataProvider()
    {
        yield 'localhost' => ['localhost', 'localhost'];
        yield 'ssl_localhost' => ['ssl://localhost', 'ssl://localhost'];
        yield 'http_localhost' => ['http://localhost', 'http://localhost'];
    }

    public function testGetPort()
    {
        $host = 'localhost';
        $port = 9090;
        $persist = false;
        $debugHandler = null;
        $transport = new TSocket(
            $host,
            $port,
            $persist,
            $debugHandler
        );
        $this->assertEquals($port, $transport->getPort());
    }

    public function testClose()
    {
        $host = 'localhost';
        $port = 9090;
        $persist = false;
        $debugHandler = null;
        $transport = new TSocket(
            $host,
            $port,
            $persist,
            $debugHandler
        );
        $transport->setHandle(fopen('php://memory', 'r+'));
        $reflector = new \ReflectionClass($transport);
        $property = $reflector->getProperty('handle_');
        $property->setAccessible(true);
        $this->assertNotNull($property->getValue($transport));

        $transport->close();
        $reflector = new \ReflectionClass($transport);
        $property = $reflector->getProperty('handle_');
        $property->setAccessible(true);
        $this->assertNull($property->getValue($transport));
    }

    public function testWrite()
    {
        $host = 'localhost';
        $port = 9090;
        $persist = false;
        $debugHandler = null;
        $transport = new TSocket(
            $host,
            $port,
            $persist,
            $debugHandler
        );
        $fileName = sys_get_temp_dir() . '/' . md5(mt_rand(0, time()) . time());
        touch($fileName);
        $handle = fopen($fileName, 'r+');
        $transport->setHandle($handle);
        $transport->write('test1234456789132456798');
        $this->assertEquals('test1234456789132456798', file_get_contents($fileName));

        register_shutdown_function(function () use ($fileName) {
            is_file($fileName) && unlink($fileName);
        });
    }

    /**
     * @dataProvider writeFailDataProvider
     */
    public function testWriteFail(
        $streamName,
        $expectedException,
        $expectedMessage,
        $expectedCode
    ) {
        $host = 'localhost';
        $port = 9090;
        $persist = false;
        $debugHandler = null;
        $transport = new TSocket(
            $host,
            $port,
            $persist,
            $debugHandler
        );
        if (in_array("test", stream_get_wrappers())) {
            stream_wrapper_unregister("test");
        }
        stream_wrapper_register("test", TestStream::class);
        $handle = fopen('test://' . $streamName, 'r+');
        $transport->setHandle($handle);

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);
        $this->expectExceptionCode($expectedCode);

        $transport->write('test1234456789132456798');
    }

    public function writeFailDataProvider()
    {
        yield 'stream_select timeout' => [
            'timeout',
            TTransportException::class,
            'TSocket: timed out writing 23 bytes from localhost:9090',
            0,
        ];
        yield 'stream_select fail write' => [
            'failWrite',
            TTransportException::class,
            'TSocket: Could not write 23 bytes localhost:9090',
            0,
        ];
        yield 'stream_select fail' => [
            'fail',
            TTransportException::class,
            'TSocket: Could not write 23 bytes localhost:9090',
            0,
        ];
    }

    public function testRead()
    {
        $host = 'localhost';
        $port = 9090;
        $persist = false;
        $debugHandler = null;
        $transport = new TSocket(
            $host,
            $port,
            $persist,
            $debugHandler
        );
        $fileName = sys_get_temp_dir() . '/' . md5(mt_rand(0, time()) . time());
        file_put_contents($fileName, '12345678901234567890');
        $handle = fopen($fileName, 'r+');
        $transport->setHandle($handle);
        $this->assertEquals('12345', $transport->read(5));

        register_shutdown_function(function () use ($fileName) {
            is_file($fileName) && unlink($fileName);
        });
    }

    /**
     * @dataProvider readFailDataProvider
     */
    public function testReadFail(
        $streamName,
        $expectedException,
        $expectedMessage,
        $expectedCode
    ) {
        $host = 'localhost';
        $port = 9090;
        $persist = false;
        $debugHandler = null;
        $transport = new TSocket(
            $host,
            $port,
            $persist,
            $debugHandler
        );
        if (in_array("test", stream_get_wrappers())) {
            stream_wrapper_unregister("test");
        }
        stream_wrapper_register("test", TestStream::class);
        $handle = fopen('test://' . $streamName, 'r+');
        $transport->setHandle($handle);

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);
        $this->expectExceptionCode($expectedCode);

        $transport->read(5);
    }

    public function readFailDataProvider()
    {
        yield 'stream_select timeout' => [
            'timeout',
            TTransportException::class,
            'TSocket: timed out reading 5 bytes from localhost:9090',
            0,
        ];
        yield 'stream_select fail read' => [
            'failRead',
            TTransportException::class,
            'TSocket read 0 bytes',
            0,
        ];
        yield 'stream_select fail' => [
            'fail',
            TTransportException::class,
            'TSocket: Could not read 5 bytes from localhost:9090',
            0,
        ];
    }
}

//redeclare core functions for testing
namespace Thrift\Transport;

{
    function fsockopen(
        string $hostname,
        int $port,
        &$error_code,
        &$error_message,
        ?float $timeout
    ) {
        if ($hostname === 'nonexistent-host' && $port === 9090) {
            $error_code = 999;
            $error_message = 'Connection refused';

            return false;
        }

        return fopen('php://memory', 'r+');
    }

    function pfsockopen(
        string $hostname,
        int $port,
        &$error_code,
        &$error_message,
        ?float $timeout
    ) {
        return fopen('php://memory', 'r+');
    }

    function stream_select(
        &$read,
        &$write,
        &$except,
        $seconds,
        $microseconds
    ) {
        if (!is_null($write)) {
            $uri = stream_get_meta_data($write[0])['uri'];
            if ($uri === 'test://timeout') {
                return 0;
            } elseif ($uri === 'test://failWrite') {
                return 1;
            } elseif ($uri === 'test://fail') {
                return false;
            }
        }
        if (!is_null($read)) {
            $uri = stream_get_meta_data($read[0])['uri'];
            if ($uri === 'test://timeout') {
                return 0;
            } elseif ($uri === 'test://failRead') {
                return 1;
            } elseif ($uri === 'test://fail') {
                return false;
            }
        }

        return \stream_select($read, $write, $except, $seconds, $microseconds);
    }
}
