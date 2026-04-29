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

namespace Test\Thrift\Unit\Lib\Serializer\Fixture;

use Thrift\Type\TType;

class BinarySerializerTestStruct
{
    public $message;
    public $number;

    public function __construct(array $values = array())
    {
        $this->message = $values['message'] ?? null;
        $this->number = $values['number'] ?? null;
    }

    public function getName()
    {
        return 'BinarySerializerTestStruct';
    }

    public function write($output)
    {
        $output->writeStructBegin($this->getName());
        if ($this->message !== null) {
            $output->writeFieldBegin('message', TType::STRING, 1);
            $output->writeString($this->message);
            $output->writeFieldEnd();
        }
        if ($this->number !== null) {
            $output->writeFieldBegin('number', TType::I32, 2);
            $output->writeI32($this->number);
            $output->writeFieldEnd();
        }
        $output->writeFieldStop();
        $output->writeStructEnd();
    }

    public function read($input)
    {
        $input->readStructBegin($name);
        while (true) {
            $input->readFieldBegin($fieldName, $fieldType, $fieldId);
            if ($fieldType === TType::STOP) {
                break;
            }
            switch ($fieldId) {
                case 1:
                    if ($fieldType === TType::STRING) {
                        $input->readString($this->message);
                    } else {
                        $input->skip($fieldType);
                    }
                    break;
                case 2:
                    if ($fieldType === TType::I32) {
                        $input->readI32($this->number);
                    } else {
                        $input->skip($fieldType);
                    }
                    break;
                default:
                    $input->skip($fieldType);
                    break;
            }
            $input->readFieldEnd();
        }
        $input->readStructEnd();
    }
}
