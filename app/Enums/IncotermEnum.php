<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum IncotermEnum: string implements HasLabel
{
    case EXW = 'EXW';
    case FCA = 'FCA';
    case FOB = 'FOB';
    case FAS = 'FAS';
    case CIF = 'CIF';
    case CIP = 'CIP';
    case CFR = 'CFR';
    case CPT = 'CPT';
    case DAT = 'DAT';
    case DAP = 'DAP';
    case DDP = 'DDP';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EXW => 'EXW',
            self::FCA => 'FCA',
            self::FOB => 'FOB',
            self::FAS => 'FAS',
            self::CIF => 'CIF',
            self::CIP => 'CIP',
            self::CFR => 'CFR',
            self::CPT => 'CPT',
            self::DAT => 'DAT',
            self::DAP => 'DAP',
            self::DDP => 'DDP',
        };
    }

    public static function allCases() :array
    {
        $arr = [];
        foreach (static::cases() as $case){
            $arr[] = $case->value;
        }
        return $arr;
    }
}
