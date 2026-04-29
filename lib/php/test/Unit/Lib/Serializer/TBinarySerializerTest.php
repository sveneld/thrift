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
 */

namespace Test\Thrift\Unit\Lib\Serializer;

use PHPUnit\Framework\TestCase;
use Test\Thrift\Unit\Lib\Serializer\Fixture\BinarySerializerTestStruct;
use Thrift\Exception\TTransportException;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Serializer\TBinarySerializer;
use Thrift\Transport\TMemoryBuffer;

class TBinarySerializerTest extends TestCase
{
    public function testSerializeWritesTheFallbackBinaryPayload(): void
    {
        $this->skipWhenAcceleratedExtensionIsLoaded();

        $object = new BinarySerializerTestStruct(array('message' => 'hello', 'number' => 42));

        $this->assertSame($this->writeManually($object), TBinarySerializer::serialize($object));
    }

    public function testDeserializeReadsTheFallbackBinaryPayload(): void
    {
        $this->skipWhenAcceleratedExtensionIsLoaded();

        $object = new BinarySerializerTestStruct(array('message' => 'hello', 'number' => 42));

        $deserialized = TBinarySerializer::deserialize(
            $this->writeManually($object),
            BinarySerializerTestStruct::class
        );

        $this->assertInstanceOf(BinarySerializerTestStruct::class, $deserialized);
        $this->assertNotSame($object, $deserialized);
        $this->assertSame($object->message, $deserialized->message);
        $this->assertSame($object->number, $deserialized->number);
    }

    public function testDeserializeRejectsTruncatedPayload(): void
    {
        $this->skipWhenAcceleratedExtensionIsLoaded();

        $this->expectException(TTransportException::class);

        TBinarySerializer::deserialize('', BinarySerializerTestStruct::class);
    }

    private function writeManually(BinarySerializerTestStruct $object): string
    {
        $transport = new TMemoryBuffer();
        $protocol = new TBinaryProtocolAccelerated($transport);

        $object->write($protocol);
        $protocol->getTransport()->flush();

        return $transport->getBuffer();
    }

    private function skipWhenAcceleratedExtensionIsLoaded(): void
    {
        if (function_exists('thrift_protocol_write_binary') || function_exists('thrift_protocol_read_binary')) {
            $this->markTestSkipped('Fallback serializer unit tests require the thrift_protocol extension to be disabled.');
        }
    }
}
