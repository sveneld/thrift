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
 */

namespace Test\Thrift\Unit\Lib\Server;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Test\Thrift\Unit\Lib\ReflectionHelper;
use Test\Thrift\Unit\Lib\Server\Fixture\ForkingServerTerminatedException;
use Test\Thrift\Unit\Lib\Server\Fixture\ForkingServerTestDouble;
use Test\Thrift\Unit\Lib\Server\Fixture\TestProcessor;
use Thrift\Exception\TException;
use Thrift\Exception\TTransportException;
use Thrift\Factory\TProtocolFactory;
use Thrift\Factory\TTransportFactoryInterface;
use Thrift\Protocol\TProtocol;
use Thrift\Server\TServerTransport;
use Thrift\Transport\TTransport;

class TForkingServerTest extends TestCase
{
    use ReflectionHelper;

    /**
     * @var MockObject|TestProcessor
     */
    private $processor;
    /**
     * @var MockObject|TServerTransport
     */
    private $serverTransport;
    /**
     * @var MockObject|TTransportFactoryInterface
     */
    private $inputTransportFactory;
    /**
     * @var MockObject|TTransportFactoryInterface
     */
    private $outputTransportFactory;
    /**
     * @var MockObject|TProtocolFactory
     */
    private $inputProtocolFactory;
    /**
     * @var MockObject|TProtocolFactory
     */
    private $outputProtocolFactory;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(TestProcessor::class);
        $this->serverTransport = $this->createMock(TServerTransport::class);
        $this->inputTransportFactory = $this->createMock(TTransportFactoryInterface::class);
        $this->outputTransportFactory = $this->createMock(TTransportFactoryInterface::class);
        $this->inputProtocolFactory = $this->createMock(TProtocolFactory::class);
        $this->outputProtocolFactory = $this->createMock(TProtocolFactory::class);
    }

    public function testServeTracksAndCollectsForkedChildren(): void
    {
        $acceptedTransport = $this->createMock(TTransport::class);
        $server = $this->createServer();
        $server->setForkResults(array(1234));
        $server->setWaitPidResults(array(0, 1234));

        $this->serverTransport->expects($this->once())
            ->method('listen');
        $this->serverTransport->expects($this->once())
            ->method('close');

        $acceptCalls = 0;
        $this->serverTransport->expects($this->exactly(2))
            ->method('accept')
            ->willReturnCallback(function () use (&$acceptCalls, $acceptedTransport, $server) {
                ++$acceptCalls;
                if ($acceptCalls === 1) {
                    return $acceptedTransport;
                }

                $server->stop();
                throw new TTransportException('stopped');
            });

        $acceptedTransport->expects($this->once())
            ->method('close');

        $this->inputTransportFactory->expects($this->never())
            ->method('getTransport');
        $this->outputTransportFactory->expects($this->never())
            ->method('getTransport');
        $this->processor->expects($this->never())
            ->method('process');

        $server->serve();

        $this->assertSame(
            array(
                array(1234, ForkingServerTestDouble::WAIT_NO_HANG),
                array(1234, ForkingServerTestDouble::WAIT_NO_HANG),
            ),
            $server->getWaitPidCalls()
        );
        $this->assertSame(array(), $this->getPropertyValue($server, 'children_'));
    }

    public function testServeRunsAcceptedTransportInChildProcess(): void
    {
        $acceptedTransport = $this->createMock(TTransport::class);
        $inputTransport = $this->createMock(TTransport::class);
        $outputTransport = $this->createMock(TTransport::class);
        $inputProtocol = $this->createMock(TProtocol::class);
        $outputProtocol = $this->createMock(TProtocol::class);

        $server = $this->createServer();
        $server->setForkResults(array(0));

        $this->serverTransport->expects($this->once())
            ->method('listen');
        $this->serverTransport->expects($this->once())
            ->method('accept')
            ->willReturn($acceptedTransport);

        $this->inputTransportFactory->expects($this->once())
            ->method('getTransport')
            ->with($acceptedTransport)
            ->willReturn($inputTransport);
        $this->outputTransportFactory->expects($this->once())
            ->method('getTransport')
            ->with($acceptedTransport)
            ->willReturn($outputTransport);
        $this->inputProtocolFactory->expects($this->once())
            ->method('getProtocol')
            ->with($inputTransport)
            ->willReturn($inputProtocol);
        $this->outputProtocolFactory->expects($this->once())
            ->method('getProtocol')
            ->with($outputTransport)
            ->willReturn($outputProtocol);

        $this->processor->expects($this->exactly(2))
            ->method('process')
            ->with($inputProtocol, $outputProtocol)
            ->willReturnOnConsecutiveCalls(true, false);
        $acceptedTransport->expects($this->once())
            ->method('close');

        $this->expectException(ForkingServerTerminatedException::class);
        $this->expectExceptionMessage('terminated with 0');

        $server->serve();
    }

    public function testServeThrowsWhenForkFails(): void
    {
        $server = $this->createServer();
        $server->setForkResults(array(-1));

        $this->serverTransport->expects($this->once())
            ->method('listen');
        $this->serverTransport->expects($this->once())
            ->method('accept')
            ->willReturn($this->createMock(TTransport::class));

        $this->expectException(TException::class);
        $this->expectExceptionMessage('Failed to fork');

        $server->serve();
    }

    private function createServer(): ForkingServerTestDouble
    {
        return new ForkingServerTestDouble(
            $this->processor,
            $this->serverTransport,
            $this->inputTransportFactory,
            $this->outputTransportFactory,
            $this->inputProtocolFactory,
            $this->outputProtocolFactory
        );
    }
}
