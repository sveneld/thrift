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


namespace Test\Thrift\Unit\Lib\Transport\Fixtures;

class TestStream
{
    public $context;

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        return true;
    }

    public function stream_read($count)
    {
        return '';
    }

    public function stream_write($data)
    {
        return 0;
    }

    public function stream_tell()
    {
        return 0;
    }

    public function stream_eof()
    {
        return true;
    }

    public function stream_seek($offset, $whence)
    {
        return 0;
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
        return true;
    }

    public function stream_cast()
    {
        return [];
    }
}
