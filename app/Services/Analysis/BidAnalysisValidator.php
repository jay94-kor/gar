<?php

namespace App\Services\Analysis;

use RuntimeException;

class BidAnalysisValidator
{
    /**
     * @param  array<string, mixed>  $analysis
     */
    public function validate(array $analysis): void
    {
        $requiredKeys = [
            'meta',
            'summary',
            'vehicles',
            'procurement',
            'contract',
            'insurance',
            'qualification',
            'evaluation',
            'performance_requirement',
            'required_documents',
            'special_conditions',
        ];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $analysis)) {
                throw new RuntimeException(sprintf('Analysis payload is missing required key [%s].', $key));
            }
        }

        if (! is_array($analysis['vehicles'])) {
            throw new RuntimeException('Analysis [vehicles] must be an array.');
        }

        foreach (['meta', 'summary', 'procurement', 'contract', 'insurance', 'qualification', 'evaluation', 'performance_requirement', 'required_documents', 'special_conditions'] as $objectKey) {
            if (! is_array($analysis[$objectKey])) {
                throw new RuntimeException(sprintf('Analysis [%s] must be an object.', $objectKey));
            }
        }
    }
}
