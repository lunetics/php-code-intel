<?php

// This file simulates a large file that might cause memory issues

namespace TestFixtures\ErrorCases;

class LargeFileExample
{
    /**
     * This class contains many methods to simulate a large file
     * that could potentially cause memory or timeout issues during parsing
     */

    // Generate many similar methods to increase file size
<?php for ($i = 1; $i <= 100; $i++): ?>
    public function method<?= $i ?>(): string
    {
        $data = [
            'method' => 'method<?= $i ?>',
            'description' => 'This is method number <?= $i ?> in the large file',
            'data' => array_fill(0, 100, 'large_data_<?= $i ?>'),
            'timestamp' => time(),
            'random' => md5('method_<?= $i ?>_' . rand()),
        ];
        
        return json_encode($data);
    }

    public function processMethod<?= $i ?>(array $input): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            $result[$key . '_<?= $i ?>'] = $value . '_processed_' . <?= $i ?>;
        }
        return $result;
    }

<?php endfor; ?>

    public function aggregateAllMethods(): array
    {
        $results = [];
<?php for ($i = 1; $i <= 100; $i++): ?>
        $results[] = $this->method<?= $i ?>();
<?php endfor; ?>
        return $results;
    }
}