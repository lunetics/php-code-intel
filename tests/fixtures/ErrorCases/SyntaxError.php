<?php

// This file intentionally contains syntax errors for testing error handling

namespace TestFixtures\ErrorCases;

class SyntaxErrorExample
{
    public function missingBrace()
    {
        if (true) {
            echo "Missing closing brace";
        // Missing closing brace for if statement
    
    public function invalidSyntax()
    {
        $var = "unclosed string;
        echo $var;
    }

    public function unexpectedToken()
    {
        $array = [
            'key1' => 'value1',
            'key2' => 'value2'
            'key3' => 'value3'  // Missing comma
        ];
    }
}