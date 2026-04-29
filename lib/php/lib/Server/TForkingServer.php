<?php

namespace Thrift\Server;

use Thrift\Transport\TTransport;
use Thrift\Exception\TException;
use Thrift\Exception\TTransportException;

/**
 * A forking implementation of a Thrift server.
 *
 * @package thrift.server
 */
class TForkingServer extends TServer
{
    /**
     * Flag for the main serving loop
     *
     * @var bool
     */
    private $stop_ = false;

    /**
     * List of children.
     *
     * @var array
     */
    protected $children_ = array();

    /**
     * Listens for new client using the supplied
     * transport. We fork when a new connection
     * arrives.
     *
     * @return void
     */
    public function serve()
    {
        $this->transport_->listen();

        while (!$this->stop_) {
            try {
                $transport = $this->transport_->accept();

                if ($transport != null) {
                    $pid = $this->fork();

                    if ($pid > 0) {
                        $this->handleParent($transport, $pid);
                    } elseif ($pid === 0) {
                        $this->handleChild($transport);
                    } else {
                        throw new TException('Failed to fork');
                    }
                }
            } catch (TTransportException $e) {
            }

            $this->collectChildren();
        }
    }

    /**
     * Code run by the parent
     *
     * @param TTransport $transport
     * @param int $pid
     * @return void
     */
    private function handleParent(TTransport $transport, $pid)
    {
        $this->children_[$pid] = $transport;
    }

    /**
     * Code run by the child.
     *
     * @param TTransport $transport
     * @return void
     */
    private function handleChild(TTransport $transport)
    {
        try {
            $inputTransport = $this->inputTransportFactory_->getTransport($transport);
            $outputTransport = $this->outputTransportFactory_->getTransport($transport);
            $inputProtocol = $this->inputProtocolFactory_->getProtocol($inputTransport);
            $outputProtocol = $this->outputProtocolFactory_->getProtocol($outputTransport);
            while ($this->processor_->process($inputProtocol, $outputProtocol)) {
            }
            @$transport->close();
        } catch (TTransportException $e) {
        }

        $this->terminate(0);
    }

    /**
     * Collects any children we may have
     *
     * @return void
     */
    private function collectChildren()
    {
        foreach ($this->children_ as $pid => $transport) {
            if ($this->waitPid($pid, $status, $this->waitNoHangOption()) > 0) {
                unset($this->children_[$pid]);
                if ($transport) {
                    @$transport->close();
                }
            }
        }
    }

    /**
     * Forks the current process.
     *
     * @return int
     */
    protected function fork()
    {
        return pcntl_fork();
    }

    /**
     * Waits for a child process.
     *
     * @param int $pid
     * @param int|null $status
     * @param int $options
     * @return int
     */
    protected function waitPid($pid, &$status, $options)
    {
        return pcntl_waitpid($pid, $status, $options);
    }

    /**
     * Returns the non-blocking wait option.
     *
     * @return int
     */
    protected function waitNoHangOption()
    {
        return \WNOHANG;
    }

    /**
     * Terminates the child process.
     *
     * @param int $status
     * @return void
     */
    protected function terminate($status)
    {
        exit($status);
    }

    /**
     * Stops the server running. Kills the transport
     * and then stops the main serving loop
     *
     * @return void
     */
    public function stop()
    {
        $this->transport_->close();
        $this->stop_ = true;
    }
}
