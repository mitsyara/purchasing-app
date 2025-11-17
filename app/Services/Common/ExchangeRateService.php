<?php

namespace App\Services\Common;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service xử lý tỷ giá hối đoái từ Vietcombank
 * Refactored để support cả singleton usage và backward compatibility
 */
class ExchangeRateService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const DEFAULT_BASE_CURRENCY = 'VND';
    private const VCB_XML_URL = 'https://portal.vietcombank.com.vn/Usercontrols/TVPortal.TyGia/pXML.aspx';
    private const VCB_API_URL = 'https://www.vietcombank.com.vn/api/exchangerates?date=';

    private ?string $date = null;
    public ?Carbon $timestamp = null;
    public array $response = [];

    public function __construct(?string $date = null)
    {
        if ($date && !self::isValidDate($date)) {
            throw new \InvalidArgumentException("Invalid date format. Expected 'Y-m-d'.");
        }

        $this->date = $date;
    }

    public static function fetch(?string $date = null): array
    {
        $date = $date ?: today()->format('Y-m-d');

        if (!self::isValidDate($date)) {
            throw new \InvalidArgumentException("Invalid date format. Expected 'Y-m-d'.");
        }

        $cacheKey = "vcb_exchange_rates_{$date}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($date) {
            $service = new self($date);
            return $service->getExchangeRates()->response;
        });
    }

    public function getExchangeRates(): static
    {
        return $this->isHistoricalDate()
            ? $this->fetchHistoricalRates()
            : $this->fetchCurrentRates();
    }

    private function fetchHistoricalRates(): static
    {
        try {
            $response = Http::timeout(10)->get(self::VCB_API_URL . $this->date);

            if ($response->failed()) {
                throw new \RuntimeException("VCB API request failed");
            }

            $data = $response->json();
            $this->timestamp = Carbon::parse($data['UpdatedDate']);
            $this->response = $this->transformHistoricalData($data, $this->timestamp);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch historical exchange rates', [
                'date' => $this->date,
                'error' => $e->getMessage()
            ]);
        }

        return $this;
    }

    private function fetchCurrentRates(string $delimiter = ','): static
    {
        try {
            $xmlContent = $this->sendRequest(self::VCB_XML_URL);

            if (empty($xmlContent)) {
                throw new \RuntimeException("Empty XML response");
            }

            $parsed = $this->parseXml($xmlContent);
            $transformed = $this->transformXmlData($parsed, $delimiter);

            $this->timestamp = $transformed['timestamp'];
            $this->response = $transformed['rates'] + [
                'timestamp' => $this->timestamp->format('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            Log::error('Failed to fetch current exchange rates', [
                'error' => $e->getMessage()
            ]);
        }

        return $this;
    }

    private function sendRequest(string $url): string
    {
        try {
            return Http::timeout(10)->get($url)->body();
        } catch (\Throwable $e) {
            Log::error('Request to VCB failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Request to VCB failed!");
        }
    }

    private function parseXml(string $xml): array
    {
        $parser = xml_parser_create();
        $result = [];
        xml_parse_into_struct($parser, $xml, $result);
        xml_parser_free($parser);

        return $result;
    }

    private function transformHistoricalData(array $data, Carbon $timestamp): array
    {
        $rates = collect($data['Data'])->mapWithKeys(fn($item) => [
            $item['currencyCode'] => [
                'cash' => (float) $item['cash'],
                'transfer' => (float) $item['transfer'],
                'sell' => (float) $item['sell'],
            ]
        ])->toArray();

        $rates['timestamp'] = $timestamp->format('Y-m-d H:i:s');
        return $rates;
    }

    private function transformXmlData(array $xmlData, string $delimiter = ','): array
    {
        $rates = [];
        $timestamp = now();

        foreach ($xmlData as $item) {
            if (($item['tag'] ?? '') === 'DATETIME' && isset($item['value'])) {
                $timestamp = Carbon::createFromFormat('n/j/Y g:i:s A', $item['value'], 'Asia/Ho_Chi_Minh');
            }

            if (isset($item['attributes']['CURRENCYCODE'])) {
                $attr = $item['attributes'];
                $rates[$attr['CURRENCYCODE']] = [
                    'cash' => (float) str_replace($delimiter, '', $attr['BUY']),
                    'transfer' => (float) str_replace($delimiter, '', $attr['TRANSFER']),
                    'sell' => (float) str_replace($delimiter, '', $attr['SELL']),
                ];
            }
        }

        return ['rates' => $rates, 'timestamp' => $timestamp];
    }

    private static function isValidDate(string $date, string $format = 'Y-m-d'): bool
    {
        try {
            $d = Carbon::createFromFormat($format, $date);
            return $d && $d->format($format) === $date;
        } catch (\Throwable) {
            return false;
        }
    }

    private function isHistoricalDate(): bool
    {
        return $this->date < today()->format('Y-m-d');
    }

    // ==================== BACKWARD COMPATIBILITY METHODS ====================

    /**
     * Lấy tỷ giá từ cache hoặc API (new service compatibility)
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): ?float
    {
        if (strtoupper($fromCurrency) === strtoupper($toCurrency)) {
            return 1.0;
        }

        $cacheKey = "vcb_exchange_rate_{$fromCurrency}_{$toCurrency}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($fromCurrency, $toCurrency) {
            return $this->calculateExchangeRate($fromCurrency, $toCurrency);
        });
    }

    /**
     * Convert amount between currencies
     */
    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        return $rate ? $amount * $rate : null;
    }

    /**
     * Convert to VND
     */
    public function convertToBaseCurrency(float $amount, string $fromCurrency): ?float
    {
        return $this->convertAmount($amount, $fromCurrency, self::DEFAULT_BASE_CURRENCY);
    }

    /**
     * Convert from VND
     */
    public function convertFromBaseCurrency(float $amount, string $toCurrency): ?float
    {
        return $this->convertAmount($amount, self::DEFAULT_BASE_CURRENCY, $toCurrency);
    }

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        return Cache::remember('vcb_supported_currencies', self::CACHE_TTL * 24, function () {
            $rates = $this->getAllRatesFromVCB();
            return array_keys($rates);
        });
    }

    /**
     * Clear cache
     */
    public function clearExchangeRateCache(): void
    {
        Cache::forget('vcb_supported_currencies');
        $currencies = ['USD', 'EUR', 'JPY', 'GBP', 'CNY', 'KRW', 'SGD', 'THB'];
        foreach ($currencies as $from) {
            foreach ($currencies as $to) {
                Cache::forget("vcb_exchange_rate_{$from}_{$to}");
                Cache::forget("vcb_exchange_rate_{$to}_{$from}");
            }
        }
    }

    /**
     * Alias for getExchangeRate với date support
     */
    public function getRate(string $fromCurrency, string $toCurrency, ?string $date = null): ?float
    {
        if ($date && $date !== $this->date) {
            $tempService = new self($date);
            return $tempService->getExchangeRate($fromCurrency, $toCurrency);
        }
        return $this->getExchangeRate($fromCurrency, $toCurrency);
    }

    /**
     * Format amount with currency symbol
     */
    public function formatAmount(float $amount, string $currency): string
    {
        $formatMap = [
            'VND' => ['decimals' => 0, 'symbol' => '₫', 'position' => 'after'],
            'USD' => ['decimals' => 2, 'symbol' => '$', 'position' => 'before'],
            'EUR' => ['decimals' => 2, 'symbol' => '€', 'position' => 'after'],
            'JPY' => ['decimals' => 0, 'symbol' => '¥', 'position' => 'before'],
            'GBP' => ['decimals' => 2, 'symbol' => '£', 'position' => 'before'],
        ];
        
        $format = $formatMap[strtoupper($currency)] ?? ['decimals' => 2, 'symbol' => '', 'position' => 'after'];
        $formattedAmount = __number_string_converter($amount);
        
        return $format['position'] === 'before' 
            ? $format['symbol'] . $formattedAmount
            : $formattedAmount . ' ' . $format['symbol'];
    }

    /**
     * Validate currency code
     */
    public function isValidCurrency(string $currency): bool
    {
        $supportedCurrencies = $this->getSupportedCurrencies();
        return in_array(strtoupper($currency), $supportedCurrencies) || strtoupper($currency) === 'VND';
    }

    /**
     * Get multiple rates at once
     */
    public function getMultipleRates(string $baseCurrency, array $targetCurrencies): array
    {
        $rates = [];
        foreach ($targetCurrencies as $currency) {
            $rates[$currency] = $this->getExchangeRate($baseCurrency, $currency);
        }
        return $rates;
    }

    /**
     * Get exchange rate details
     */
    public function getExchangeRateDetails(string $fromCurrency, string $toCurrency): array
    {
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        
        return [
            'from' => strtoupper($fromCurrency),
            'to' => strtoupper($toCurrency),
            'rate' => $rate,
            'updated_at' => now()->toISOString(),
            'source' => 'Vietcombank',
        ];
    }

    // ==================== INTERNAL HELPER METHODS ====================

    /**
     * Calculate exchange rate from VCB data
     */
    private function calculateExchangeRate(string $fromCurrency, string $toCurrency): ?float
    {
        $rates = $this->getAllRatesFromVCB();
        
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);
        
        // VND to foreign currency
        if ($fromCurrency === 'VND' && isset($rates[$toCurrency])) {
            return 1 / $rates[$toCurrency]['sell'];
        }
        
        // Foreign currency to VND
        if ($toCurrency === 'VND' && isset($rates[$fromCurrency])) {
            return $rates[$fromCurrency]['transfer'];
        }
        
        // Cross currency via VND
        if (isset($rates[$fromCurrency]) && isset($rates[$toCurrency])) {
            $fromToVnd = $rates[$fromCurrency]['transfer'];
            $vndToTarget = 1 / $rates[$toCurrency]['sell'];
            return $fromToVnd * $vndToTarget;
        }
        
        return null;
    }

    /**
     * Get all rates from VCB and cache them
     */
    private function getAllRatesFromVCB(): array
    {
        $cacheKey = 'vcb_all_rates' . ($this->date ? "_$this->date" : '');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $this->getExchangeRates(); // This populates $this->response
            
            // Transform to expected format
            $rates = [];
            foreach ($this->response as $currency => $data) {
                if ($currency !== 'timestamp' && is_array($data)) {
                    $rates[$currency] = $data;
                }
            }
            
            return $rates;
        });
    }

    /**
     * Fetch rates method for backward compatibility (for Livewire)
     */
    public function fetchRates(string $date): array
    {
        $tempService = new self($date);
        $tempService->getExchangeRates();
        return $tempService->response;
    }
}
