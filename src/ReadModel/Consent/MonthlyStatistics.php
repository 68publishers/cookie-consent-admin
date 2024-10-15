<?php

declare(strict_types=1);

namespace App\ReadModel\Consent;

/**
 * @phpstan-type MonthlyStatisticsArray = array{ 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, 10: int, 11: int, 12: int }
 */
final readonly class MonthlyStatistics
{
    public function __construct(
        public int $m1,
        public int $m2,
        public int $m3,
        public int $m4,
        public int $m5,
        public int $m6,
        public int $m7,
        public int $m8,
        public int $m9,
        public int $m10,
        public int $m11,
        public int $m12,
    ) {}

    /**
     * @param array<string|int, int> $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            m1: $array['m1'] ?? $array[1] ?? 0,
            m2: $array['m2'] ?? $array[2] ?? 0,
            m3: $array['m3'] ?? $array[3] ?? 0,
            m4: $array['m4'] ?? $array[4] ?? 0,
            m5: $array['m5'] ?? $array[5] ?? 0,
            m6: $array['m6'] ?? $array[6] ?? 0,
            m7: $array['m7'] ?? $array[7] ?? 0,
            m8: $array['m8'] ?? $array[8] ?? 0,
            m9: $array['m9'] ?? $array[9] ?? 0,
            m10: $array['m10'] ?? $array[10] ?? 0,
            m11: $array['m11'] ?? $array[11] ?? 0,
            m12: $array['m12'] ?? $array[12] ?? 0,
        );
    }

    /**
     * @return MonthlyStatistics
     */
    public function toArray(): array
    {
        return [
            1 => $this->m1,
            2 => $this->m2,
            3 => $this->m3,
            4 => $this->m4,
            5 => $this->m5,
            6 => $this->m6,
            7 => $this->m7,
            8 => $this->m8,
            9 => $this->m9,
            10 => $this->m10,
            11 => $this->m11,
            12 => $this->m12,
        ];
    }
}
