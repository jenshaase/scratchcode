<?php

/*
 * This class is inspired by Janet Fiber implementation
 * https://janet-lang.org/docs/fibers/index.html
 * 
 * Not sure what to do with it.
 */

class FiberError extends \Exception {}

class Fiber {

    const STATUS_DEAD = 'dead'; // The fiber has finished
    const STATUS_NEW = 'new'; // The fiber has just been created and not yet run
    const STATUS_PENDING = 'pending'; // The fiber has been yielded
    const STATUS_ERROR = 'error'; // The fiber has errored out

    /**
     * @var callable
     */
    private $generatorFunction;

    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var string;
     */
    private $status;

    public function __construct(callable $generatorFunction)
    {
        $this->status = self::STATUS_NEW;
        $this->generatorFunction = $generatorFunction;
    }

    public function getStatus() {
        return $this->status;
    }

    public function resume($value = null) {
        try {
            if ($value) {
                return $this->sendValue($value);
            } else {
                return $this->nextValue();
            }
        } catch (FiberError $e) {
            $this->status = self::STATUS_ERROR;
            return $e->getMessage();
        }
    }

    protected function sendValue($v) {
        switch ($this->status) {
            case self::STATUS_PENDING:
                $this->generator->send($v);
        }
    }

    protected function nextValue() {
        switch ($this->status) {
            case self::STATUS_NEW:
                $f = $this->generatorFunction;
                $this->generator = $f();
                $this->status = self::STATUS_PENDING;
                return $this->generator->current();

            case self::STATUS_PENDING:
                $this->generator->next();
                
                if (!$this->generator->valid()) {
                    $v = $this->generator->getReturn() ?? null;
                    $this->status = self::STATUS_DEAD;
                } else {
                    $v = $this->generator->current();
                }

                return $v;
                
            case self::STATUS_DEAD:
                throw new \Exception("Fiber is dead");

        }
    }

    public function current() {
        return $this->generator->current();
    }
}


// 1. Basic usage
$f = new Fiber(function() {
    yield 1;
    yield 2;
    yield 3;
    yield 4;
    return 5;
});

var_dump($f->getStatus()); // status: new
var_dump($f->resume()); // dumps: 1
var_dump($f->resume()); // dumps: 2
var_dump($f->resume()); // dumps: 3
var_dump($f->resume()); // dumps: 4
var_dump($f->getStatus()); // status: pending
var_dump($f->resume()); // dumps: 1
var_dump($f->getStatus()); // dumps: 5
try {
    var_dump($f->resume()); // throws an Exception
} catch (\Exception $e) {
    var_dump("an exception was thrown: " . $e->getMessage());
}


// 2. Catching errors
$f = new Fiber(function() {
    var_dump("start function");
    throw new FiberError("something is wrong!");
    var_dump("never gets here");
});

$result = $f->resume();
if ($f->getStatus() == Fiber::STATUS_ERROR) {
    var_dump("oops, the is an error: " . $result);
} else {
    var_dump("everything ok: " . $result);
}