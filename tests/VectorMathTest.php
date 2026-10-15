<?php

use PHPUnit\Framework\TestCase;
use App\Search\VectorMath;

class VectorMathTest extends TestCase
{
    private VectorMath $vectorMath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vectorMath = new VectorMath();
    }

    public function testIdenticalVectors(): void
    {
        $result = $this->vectorMath->cosineSimilarity([1,0], [1,0]);

        $this->assertEqualsWithDelta(
            1.0,
            $result,
            0.00001
        );
    }

    public function testOrthogonalVectors(): void
    {
        $result = $this->vectorMath->cosineSimilarity([1,0], [0,1]);

        $this->assertEqualsWithDelta(
            0.0,
            $result,
            0.00001
        );
    }

    public function testOppositeVectors(): void
    {
        $result = $this->vectorMath->cosineSimilarity([1,0], [-1,0]);

        $this->assertEqualsWithDelta(
            -1.0,
            $result,
            0.00001
        );
    }

    public function testZeroVector(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->vectorMath->cosineSimilarity([0,0], [1,1]);

    }

    public function testVectorStartingWithZeroIsValid(): void
    {
        $result = $this->vectorMath->cosineSimilarity([0,1], [0,1]);

        $this->assertEqualsWithDelta(
            1.0,
            $result,
            0.00001
        );
    }

    public function testSecondVectorIsZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->vectorMath->cosineSimilarity([1,0], [0,0]);
    }

    public function testDifferentDimensionsVector(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->vectorMath->cosineSimilarity([1,2,3], [1,2]);

    }
}