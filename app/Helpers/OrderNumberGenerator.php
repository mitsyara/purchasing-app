<?php

namespace App\Helpers;

use Carbon\Carbon;

class OrderNumberGenerator
{
    /**
     * Generate purchase order number
     */
    public static function generatePurchaseOrderNumber(int $companyId, string $orderDate): string
    {
        $id = str_pad($companyId, 2, '0', STR_PAD_LEFT);
        $date = Carbon::parse($orderDate)->format('ymd');
        
        return "PO-{$id}{$date}";
    }

    /**
     * Generate shipment number
     */
    public static function generateShipmentNumber(int $companyId, string $shipmentDate): string
    {
        $id = str_pad($companyId, 2, '0', STR_PAD_LEFT);
        $date = Carbon::parse($shipmentDate)->format('ymd');
        
        return "SH-{$id}{$date}";
    }

    /**
     * Generate invoice number
     */
    public static function generateInvoiceNumber(int $companyId, string $invoiceDate): string
    {
        $id = str_pad($companyId, 2, '0', STR_PAD_LEFT);
        $date = Carbon::parse($invoiceDate)->format('ymd');
        
        return "INV-{$id}{$date}";
    }

    /**
     * Make number unique by checking database
     */
    public static function makeUnique(string $baseNumber, string $modelClass, string $column = 'order_number', ?int $excludeId = null): string
    {
        $originalNumber = $baseNumber;
        $counter = 0;
        
        do {
            $testNumber = $counter === 0 ? $originalNumber : $originalNumber . sprintf('.%02d', $counter);
            $query = app($modelClass)->where($column, $testNumber);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            $exists = $query->exists();
            $counter++;
        } while ($exists);

        return $testNumber;
    }

    /**
     * Parse order number parts
     */
    public static function parseOrderNumber(string $orderNumber): array
    {
        if (preg_match('/^([A-Z]+)-(\d{2})(\d{6})(?:\.(\d{2}))?$/', $orderNumber, $matches)) {
            return [
                'prefix' => $matches[1] ?? null,
                'company_id' => (int) ($matches[2] ?? 0),
                'date' => $matches[3] ?? null,
                'sequence' => isset($matches[4]) ? (int) $matches[4] : 0,
            ];
        }

        return [];
    }

    /**
     * Format number with padding
     */
    public static function formatSequence(int $number, int $padding = 2): string
    {
        return str_pad($number, $padding, '0', STR_PAD_LEFT);
    }
}