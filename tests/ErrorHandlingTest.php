<?php

namespace Async\Tests;

use Async\Processor\Processor;
use Async\Processor\ProcessorError;
use PHPUnit\Framework\TestCase;

class ErrorHandlingTest extends TestCase
{
    public function testIt_can_handle_exceptions_via_catch_callback()
    {
        $process = spawn(function () {
                throw new \Exception('test');
            })->catch(function (\Exception $e) {
                $this->assertRegExp('/test/', $e->getMessage());
            });

        spawn_run($process);
        $this->assertFalse($process->isSuccessful());
        $this->assertTrue($process->isTerminated());
    }

    public function testIt_can_handle_exceptions_via_catch_callback_yield()
    {
        $process = spawn(function () {
                throw new \Exception('test');
            })->catch(function (\Exception $e) {
                $this->assertRegExp('/test/', $e->getMessage());
            });

        $pause = $process->yielding();
        $this->assertNull($pause->current());
        $this->assertTrue($process->isTerminated());
    }

    public function testIt_handles_stderr_as_processor_error()
    {
        $process = spawn(function () {
            fwrite(STDERR, 'test');
        })->catch(function (ProcessorError $error) {
           $this->assertStringContainsString('test', $error->getMessage());
        });

        spawn_run($process);
        $this->assertTrue($process->isSuccessful());
        $this->assertEquals('test', $process->getErrorOutput());
        $this->assertEquals('', $process->getOutput());
    }

    public function testIt_throws_the_exception_if_no_catch_callback()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/test/');

        $process = Processor::create(function () {
            throw new \Exception('test');
        });

        $process->run();
    }

    public function testIt_throws_the_exception_if_no_catch_callback_yield()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/test/');

        $process = Processor::create(function () {
            throw new \Exception('test');
        });

        $pause = $process->yielding();
        $this->assertNull($pause->current());
    }

    public function testIt_throws_fatal_errors()
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessageRegExp('/test/');

        $process = Processor::create(function () {
            throw new \Error('test');
        });

        $process->run();
    }

    public function testIt_keeps_the_original_trace()
    {
        $process = Processor::create(function () {
            $error = new ProcessorError();
            throw $error->fromException('test');
        })->catch(function (ProcessorError $exception) {
            $this->assertStringContainsString('Async\Processor\ProcessorError::fromException(\'test\')', $exception->getMessage());
        });

        $process->run();
    }
}
