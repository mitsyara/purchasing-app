<?php

namespace App\Services\Core;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Helpers\DateHelper;

class ExchangeRateService
{
    private string $xmlEndpoint = 'https://portal.vietcombank.com.vn/Usercontrols/TVPortal.TyGia/pXML.aspx';
    private string $apiEndpoint = 'https://www.vietcombank.com.vn/api/exchangerates?date=';

    /**
     * Fetch exchange rates for a specific date
     */
    public function fetchRates(?string $date = null): array
    {
        $date = $date ?: today()->format('Y-m-d');

        if (!DateHelper::isValidDate($date)) {
            throw new \InvalidArgumentException("Invalid date format. Expected 'Y-m-d'.");
        }

        $cacheKey = "vcb_exchange_rates_{$date}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($date) {
            return $this->getExchangeRates($date);
        });
    }

    /**
     * Get current exchange rates
     */
    public function getCurrentRates(): array
    {
        return $this->fetchRates(today()->format('Y-m-d'));
    }

    /**
     * Get exchange rate for specific currency pair
     */
    public function getRate(string $fromCurrency, string $toCurrency, ?string $date = null): ?float
    {
        $rates = $this->fetchRates($date);
        
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        // Assuming VND as base currency
        if ($toCurrency === 'VND') {
            return $rates[$fromCurrency]['sell'] ?? null;
        }

        if ($fromCurrency === 'VND') {
            return $rates[$toCurrency]['buy'] ?? null;
        }

        // Cross currency calculation
        $fromToVnd = $rates[$fromCurrency]['sell'] ?? null;
        $toToVnd = $rates[$toCurrency]['sell'] ?? null;

        if ($fromToVnd && $toToVnd) {
            return $fromToVnd / $toToVnd;
        }

        return null;
    }

    /**
     * Convert amount between currencies
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency, ?string $date = null): ?float
    {
        $rate = $this->getRate($fromCurrency, $toCurrency, $date);
        
        return $rate ? $amount * $rate : null;
    }

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        $rates = $this->getCurrentRates();
        return array_keys($rates);
    }

    /**
     * Check if currency is supported
     */
    public function isCurrencySupported(string $currency): bool
    {
        return in_array($currency, $this->getSupportedCurrencies());
    }

    /**
     * Get historical rates for a date range
     */
    public function getHistoricalRates(string $startDate, string $endDate, string $currency): array
    {
        if (!DateHelper::isDateInRange($startDate, $startDate, $endDate)) {
            throw new \InvalidArgumentException("Invalid date range");
        }

        $dates = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current->lte($end)) {
            try {
                $rates = $this->fetchRates($current->format('Y-m-d'));
                if (isset($rates[$currency])) {
                    $dates[$current->format('Y-m-d')] = $rates[$currency];
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch rates for {$current->format('Y-m-d')}", [
                    'error' => $e->getMessage()
                ]);
            }
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Get exchange rates from API
     */
    private function getExchangeRates(string $date): array
    {
        $isHistorical = Carbon::parse($date)->lt(today());
        
        return $isHistorical 
            ? $this->fetchHistoricalRates($date)
            : $this->fetchCurrentRates();
    }

    /**
     * Fetch historical rates
     */
    private function fetchHistoricalRates(string $date): array
    {
        try {
            $response = Http::timeout(10)->get($this->apiEndpoint . $date);

            if ($response->failed()) {
                throw new \RuntimeException("VCB API request failed");
            }

            $data = $response->json();
            return $this->transformHistoricalData($data);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch historical exchange rates', [
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Fetch current rates
     */
    private function fetchCurrentRates(): array
    {
        try {
            $response = Http::timeout(10)->get($this->xmlEndpoint);

            if ($response->failed()) {
                throw new \RuntimeException("VCB XML request failed");
            }

            return $this->parseXmlRates($response->body());
        } catch (\Throwable $e) {
            Log::error('Failed to fetch current exchange rates', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Transform historical data
     */
    private function transformHistoricalData(array $data): array
    {
        $transformed = [];
        
        if (isset($data['ExchangeRate'])) {
            foreach ($data['ExchangeRate'] as $rate) {
                $currency = $rate['CurrencyCode'] ?? null;
                if ($currency) {
                    $transformed[$currency] = [
                        'buy' => (float) ($rate['Buy'] ?? 0),
                        'sell' => (float) ($rate['Sell'] ?? 0),
                    ];
                }
            }
        }

        return $transformed;
    }

    /**
     * Parse XML rates
     */
    private function parseXmlRates(string $xmlContent): array
    {
        // Simplified XML parsing - implement based on VCB XML structure
        $rates = [];
        
        try {
            $xml = simplexml_load_string($xmlContent);
            
            if ($xml && isset($xml->Exrate)) {
                foreach ($xml->Exrate as $rate) {
                    $currency = (string) $rate['CurrencyCode'];
                    $rates[$currency] = [
                        'buy' => (float) $rate['Buy'],
                        'sell' => (float) $rate['Sell'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to parse XML rates', ['error' => $e->getMessage()]);
        }

        return $rates;
    }

    /**
     * Clear cache for specific date
     */
    public function clearCache(?string $date = null): void
    {
        $date = $date ?: today()->format('Y-m-d');
        $cacheKey = "vcb_exchange_rates_{$date}";
        Cache::forget($cacheKey);
    }

    /**
     * Clear all exchange rate cache
     */
    public function clearAllCache(): void
    {
        Cache::flush(); // Or use more specific pattern if needed
    }
}