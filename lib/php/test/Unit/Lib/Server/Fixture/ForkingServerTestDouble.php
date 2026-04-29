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

namespace Test\Thrift\Unit\Lib\Server\Fixture;

use Thrift\Server\TForkingServer;

class ForkingServerTestDouble extends TForkingServer
{
    const WAIT_NO_HANG = 1;

    private $forkResults = array();
    private $waitPidResults = array();
    private $waitPidCalls = array();

    public function setForkResults(array $forkResults): void
    {
        $this->forkResults = $forkResults;
    }

    public function setWaitPidResults(array $waitPidResults): void
    {
        $this->waitPidResults = $waitPidResults;
    }

    public function getWaitPidCalls(): array
    {
        return $this->waitPidCalls;
    }

    protected function fork()
    {
        if (!$this->forkResults) {
            throw new \RuntimeException('No fork result configured');
        }

        return array_shift($this->forkResults);
    }

    protected function waitPid($pid, &$status, $options)
    {
        $this->waitPidCalls[] = array($pid, $options);
        $status = 0;

        if (!$this->waitPidResults) {
            throw new \RuntimeException('No waitpid result configured');
        }

        return array_shift($this->waitPidResults);
    }

    protected function waitNoHangOption()
    {
        return self::WAIT_NO_HANG;
    }

    protected function terminate($status)
    {
        throw new ForkingServerTerminatedException('terminated with ' . $status);
    }
}
