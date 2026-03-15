<?php
namespace Aurora\Core\Interfaces;

use Aurora\Core\DTOs\PricingResultDTO;

/**
 * Interface cho các chiến lược tính giá khác nhau
 */
interface PricingStrategyInterface {
    public function calculate(array $roomType, int $numNights, int $numAdults): array;
}
