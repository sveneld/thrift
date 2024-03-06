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

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Thrift\Exception\TException;
use Thrift\Transport\TPhpStream;

class TPhpStreamTest extends TestCase
{
    use PHPMock;

    /**
     * @dataProvider fopenDataProvider
     */
    public function testOpen(
        $mode,
        $sapiName,
        $fopenParams,
        $fopenResult,
        $expectedException,
        $expectedExceptionMessage,
        $expectedExceptionCode
    ) {
        $this->markTestSkipped(
            'Due to the bug Zend OPcache can\'t be temporary enabled (it may be only disabled till the end of request)'
        );

        $this->getFunctionMock('Thrift\Transport', 'php_sapi_name')
             ->expects(!empty($sapiName) ? $this->once() : $this->never())
             ->willReturn($sapiName);

        $this->getFunctionMock('Thrift\Transport', 'fopen')
             ->expects($this->exactly(count($fopenResult)))
             ->withConsecutive(...$fopenParams)
             ->willReturnOnConsecutiveCalls(...$fopenResult);

        if ($expectedException) {
            $this->expectException($expectedException);
            $this->expectExceptionMessage($expectedExceptionMessage);
            $this->expectExceptionCode($expectedExceptionCode);
        }

        $transport = new TPhpStream($mode);
        $transport->open();
    }

    public function fopenDataProvider()
    {
        yield 'readCli' => [
            'mode' => TPhpStream::MODE_R,
            'sapiName' => 'cli',
            'fopenParams' => [['php://stdin', 'r']],
            'fopenResult' => [fopen('php://temp', 'r')],
            'expectedException' => null,
            'expectedExceptionMessage' => '',
            'expectedExceptionCode' => 0,
        ];
        yield 'readNotCli' => [
            'mode' => TPhpStream::MODE_R,
            'sapiName' => 'apache',
            'fopenParams' => [['php://input', 'r']],
            'fopenResult' => [fopen('php://temp', 'r')],
            'expectedException' => null,
            'expectedExceptionMessage' => '',
            'expectedExceptionCode' => 0,
        ];
        yield 'write' => [
            'mode' => TPhpStream::MODE_W,
            'sapiName' => '',
            'fopenParams' => [['php://output', 'w']],
            'fopenResult' => [fopen('php://temp', 'w')],
            'expectedException' => null,
            'expectedExceptionMessage' => '',
            'expectedExceptionCode' => 0,
        ];
        yield 'read and write' => [
            'mode' => TPhpStream::MODE_R | TPhpStream::MODE_W,
            'sapiName' => 'cli',
            'fopenParams' => [['php://stdin', 'r'], ['php://output', 'w']],
            'fopenResult' => [fopen('php://temp', 'r'), fopen('php://temp', 'w')],
            'expectedException' => null,
            'expectedExceptionMessage' => '',
            'expectedExceptionCode' => 0,
        ];
        yield 'read exception' => [
            'mode' => TPhpStream::MODE_R,
            'sapiName' => 'cli',
            'fopenParams' => [['php://stdin', 'r']],
            'fopenResult' => [false],
            'expectedException' => TException::class,
            'expectedExceptionMessage' => 'TPhpStream: Could not open php://input',
            #should depend on php_sapi_name result
            'expectedExceptionCode' => 0,
        ];
        yield 'write exception' => [
            'mode' => TPhpStream::MODE_W,
            'sapiName' => '',
            'fopenParams' => [['php://output', 'w']],
            'fopenResult' => [false],
            'expectedException' => TException::class,
            'expectedExceptionMessage' => 'TPhpStream: Could not open php://output',
            'expectedExceptionCode' => 0,
        ];
    }

    /**
     * @dataProvider closeDataProvider
     */
    public function testClose(
        $mode,
        $fopenParams,
        $fopenResult,
        $fcloseParams
    ) {
        $this->markTestSkipped(
            'Due to the bug Zend OPcache can\'t be temporary enabled (it may be only disabled till the end of request)'
        );

        $transport = new TPhpStream($mode);

        $this->getFunctionMock('Thrift\Transport', 'fopen')
             ->expects($this->exactly(count($fopenParams)))
             ->withConsecutive(...$fopenParams)
             ->willReturnOnConsecutiveCalls(...$fopenResult);

        $transport->open();
        $this->assertTrue($transport->isOpen());

        $this->getFunctionMock('Thrift\Transport', 'fclose')
             ->expects($this->exactly(count($fcloseParams)))
             ->withConsecutive(...$fcloseParams);

        $transport->close();
        $this->assertFalse($transport->isOpen());
    }

    public function closeDataProvider()
    {
        $read = fopen('php://temp', 'r');
        $write = fopen('php://temp', 'w');
        yield 'read' => [
            'mode' => TPhpStream::MODE_R,
            'fopenParams' => [['php://stdin', 'r']],
            'fopenResult' => [$read],
            'fcloseParams' => [[$read]],
        ];
        yield 'write' => [
            'mode' => TPhpStream::MODE_W,
            'fopenParams' => [['php://output', 'w']],
            'fopenResult' => [$write],
            'fcloseParams' => [[$write]],
        ];
        yield 'read and write' => [
            'mode' => TPhpStream::MODE_R | TPhpStream::MODE_W,
            'fopenParams' => [['php://stdin', 'r'], ['php://output', 'w']],
            'fopenResult' => [$read, $write],
            'fcloseParams' => [[$read], [$write]],
        ];
    }

    /**
     * @dataProvider readDataProvider
     */
    public function testRead(
        $freadResult,
        $expectedResult,
        $expectedException,
        $expectedExceptionMessage,
        $expectedExceptionCode
    ) {
        $transport = new TPhpStream(TPhpStream::MODE_R);

        $this->getFunctionMock('Thrift\Transport', 'fread')
             ->expects($this->once())
             ->with($this->anything(), 5)
             ->willReturn($freadResult);

        if ($expectedException) {
            $this->expectException($expectedException);
            $this->expectExceptionMessage($expectedExceptionMessage);
            $this->expectExceptionCode($expectedExceptionCode);
        }

        $this->assertEquals($expectedResult, $transport->read(5));
    }

    public function readDataProvider()
    {
        yield 'success' => [
            'freadResult' => '12345',
            'expectedResult' => '12345',
            'expectedException' => null,
            'expectedExceptionMessage' => '',
            'expectedExceptionCode' => 0,
        ];
        yield 'empty' => [
            'freadResult' => '',
            'expectedResult' => '',
            'expectedException' => TException::class,
            'expectedExceptionMessage' => 'TPhpStream: Could not read 5 bytes',
            'expectedExceptionCode' => 0,
        ];
        yield 'false' => [
            'freadResult' => false,
            'expectedResult' => false,
            'expectedException' => TException::class,
            'expectedExceptionMessage' => 'TPhpStream: Could not read 5 bytes',
            'expectedExceptionCode' => 0,
        ];
    }

    /**
     * @dataProvider writeDataProvider
     */
    public function testWrite(
        $buf,
        $fwriteParams,
        $fwriteResult,
        $expectedException,
        $expectedExceptionMessage,
        $expectedExceptionCode
    ) {
        $transport = new TPhpStream(TPhpStream::MODE_W);

        $this->getFunctionMock('Thrift\Transport', 'fwrite')
             ->expects($this->exactly(count($fwriteParams)))
             ->withConsecutive(...$fwriteParams)
             ->willReturnOnConsecutiveCalls(...$fwriteResult);

        if ($expectedException) {
            $this->expectException($expectedException);
            $this->expectExceptionMessage($expectedExceptionMessage);
            $this->expectExceptionCode($expectedExceptionCode);
        }

        $transport->write($buf);
    }

    public function writeDataProvider()
    {
        yield 'success' => [
            'buf' => '12345',
            'fwriteParams' => [[$this->anything(), '12345']],
            'fwriteResult' => [5],
            'expectedException' => null,
            'expectedExceptionMessage' => '',
            'expectedExceptionCode' => 0,
        ];
        yield 'several iteration' => [
            'buf' => '1234567890',
            'fwriteParams' => [[$this->anything(), '1234567890'], [$this->anything(), '67890']],
            'fwriteResult' => [5, 5],
            'expectedException' => null,
            'expectedExceptionMessage' => '',
            'expectedExceptionCode' => 0,
        ];
        yield 'fail' => [
            'buf' => '1234567890',
            'fwriteParams' => [[$this->anything(), '1234567890']],
            'fwriteResult' => [false],
            'expectedException' => TException::class,
            'expectedExceptionMessage' => 'TPhpStream: Could not write 10 bytes',
            'expectedExceptionCode' => 0,
        ];
    }

    public function testFlush()
    {
        $transport = new TPhpStream(TPhpStream::MODE_R);
        $this->getFunctionMock('Thrift\Transport', 'fflush')
             ->expects($this->once());
        $transport->flush();
    }
}
