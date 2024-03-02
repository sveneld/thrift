<?php

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
