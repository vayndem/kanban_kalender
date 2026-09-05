<?php

namespace App\Models\Concerns;

use App\Support\NomorHp;

trait MenormalisasiNoHp
{
    public function setNoHpAttribute(?string $value): void
    {
        $this->attributes['no_hp'] = $value === null ? null : (NomorHp::normalkan($value) ?? $value);
    }
}
