<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum IncotermEnum: string implements HasLabel
{
    case CIF = 'CIF';
    case CIP = 'CIP';
    case CPT = 'CPT';
    case CFR = 'CFR';
    case DAP = 'DAP';
    case DAT = 'DAT';
    case DDP = 'DDP';
    case EXW = 'EXW';
    case FAS = 'FAS';
    case FCA = 'FCA';
    case FOB = 'FOB';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CIF => 'CIF',
            self::CIP => 'CIP',
            self::CPT => 'CPT',
            self::CFR => 'CFR',
            self::DAP => 'DAP',
            self::DAT => 'DAT',
            self::DDP => 'DDP',
            self::EXW => 'EXW',
            self::FAS => 'FAS',
            self::FCA => 'FCA',
            self::FOB => 'FOB',
        };
    }

    public static function allCases(): array
    {
        $arr = [];
        foreach (static::cases() as $case) {
            $arr[] = $case->value;
        }
        return $arr;
    }
}
