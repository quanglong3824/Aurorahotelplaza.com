<?php
namespace Aurora\Core\DTOs;

/**
 * Guest DTO - Đại diện cho một khách hàng (Người lớn hoặc Trẻ em)
 */
class GuestDTO {
    public float $height;
    public bool $includeBreakfast;
    public string $category; // 'adult', 'child_1m_1m3', 'child_under_1m'

    public function __construct(float $height = 1.7, bool $includeBreakfast = true) {
        $this->height = $height;
        $this->includeBreakfast = $includeBreakfast;
        $this->category = $this->determineCategory();
    }

    private function determineCategory(): string {
        if ($this->height < 1.0) return 'child_under_1m';
        if ($this->height <= 1.3) return 'child_1m_1m3';
        return 'adult';
    }
}
