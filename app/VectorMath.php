<?php

class VectorMath
{
    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new RuntimeException('Different dimensions');
        }

        $dotProduct = $magnitudeA = $magnitudeB = 0;
        for ($i=0; $i < count($a); $i++) { 
            $dotProduct += $a[$i] * $b[$i];

            $magnitudeA += $a[$i] * $a[$i];
            $magnitudeB += $b[$i] * $b[$i];
        }

        return $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB));
    }
}