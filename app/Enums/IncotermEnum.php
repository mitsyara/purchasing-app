<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum IncotermEnum: string implements HasLabel
{
    case EXW = 'EXW';
    case FCA = 'FCA';
    case FOB = 'FOB';
    case FAS = 'FAS';
    case CFR = 'CFR';
    case CIF = 'CIF';
    case CPT = 'CPT';
    case CIP = 'CIP';
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
            self::CFR => 'CFR',
            self::CIF => 'CIF',
            self::CPT => 'CPT',
            self::CIP => 'CIP',
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
