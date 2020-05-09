<?php

/**
 * This is a very basic debugger for PHP, written in PHP.
 * 
 * The basic idea, is to use the yield Statement as breakpoint. A function with
 * a yield will return a Generator class instead of the function result.
 * We can use this to stop the execution, print some debug information and 
 * the continue the execution later
 * 
 * To accomplish this behaviour we need to wrap every function with a 
 * DebugInvoker class.
 */

/**
 * A DebugInvoker is just a wrapper around a function call
 */
class DebugInvoker {

    private $f;

    /**
     * @param callable $f The actual function we want to call
     */
    public function __construct(callable $f) {
        $this->f = $f;
    }

    /**
     * This calls the real function and handles possible execution stops.
     */
    public function __invoke(...$args)
    {
        $f = $this->f;
        $result = $f(...$args); // Call the function

        // Here comes the magic:
        // If the function returns a Generator, we know that the function
        // has a breakpoint in it
        if ($result instanceof Generator) {
            // Iterate of every breakpoint in the function
            foreach ($result as $step) {
                // Print the Debug information
                var_dump($step);
                // Wait for the user to press any key on the commandline
                // to continue the execution
                readline("Press enter to continue ");
            }

            // Wen we reach the end of, return the function result
            return $result->getReturn();
        } else {
            return $result;
        }
    }
}

// 1. Basic example
$a = new DebugInvoker(function($myName) {
    echo "hello, ";
    yield get_defined_vars();
    echo "$myName\n";
});

$a("world"); 
// 1. prints "hello, "
// 2. prints defined variables
// 3. ask to continue
// 4. prints "world\n";


// 2. Basic loop example
$a = new DebugInvoker(function($myName) {
    for ($i = 0; $i < 10; $i++) {
        echo "hello, ";
        yield get_defined_vars();
        echo "$myName\n";
    }
});

$a("world");
// Same as first example, but repeats the process 10 times.