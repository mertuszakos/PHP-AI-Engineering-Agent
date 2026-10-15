<?php

namespace App\Search;

class VectorMath
{
    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new \InvalidArgumentException(
                'Vectors must have the same dimensions.'
            );
        }

        $dotProduct = $magnitudeA = $magnitudeB = 0;
        for ($i=0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];

            $magnitudeA += $a[$i] * $a[$i];
            $magnitudeB += $b[$i] * $b[$i];
        }

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            throw new \InvalidArgumentException(
                'Cosine similarity is undefined for zero vectors.'
            );
        }

        return $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB));
    }
}