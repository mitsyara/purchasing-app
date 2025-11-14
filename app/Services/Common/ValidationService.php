<?php

namespace App\Services\Common;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * Service xử lý validation chung
 */
class ValidationService
{
    /**
     * Validate dữ liệu với rules
     */
    public function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $validator->validated();
    }

    /**
     * Validate email format
     */
    public function validateEmail(?string $email): bool
    {
        if (empty($email)) {
            return true; // Allow empty email
        }
        
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone number format
     */
    public function validatePhone(?string $phone): bool
    {
        if (empty($phone)) {
            return true; // Allow empty phone
        }
        
        return preg_match('/^[\d\s\-\+\(\)]+$/', $phone) === 1;
    }

    /**
     * Validate date format
     */
    public function validateDate(?string $date): bool
    {
        if (empty($date)) {
            return true; // Allow empty date
        }
        
        return strtotime($date) !== false;
    }

    /**
     * Validate positive number
     */
    public function validatePositiveNumber($number): bool
    {
        return is_numeric($number) && $number > 0;
    }

    /**
     * Validate non-negative number
     */
    public function validateNonNegativeNumber($number): bool
    {
        return is_numeric($number) && $number >= 0;
    }

    /**
     * Validate currency code (3 characters)
     */
    public function validateCurrencyCode(?string $currency): bool
    {
        if (empty($currency)) {
            return true; // Allow empty currency
        }
        
        return preg_match('/^[A-Z]{3}$/', strtoupper($currency)) === 1;
    }

    /**
     * Validate SKU format
     */
    public function validateSku(?string $sku): bool
    {
        if (empty($sku)) {
            return true; // Allow empty SKU
        }
        
        return preg_match('/^[A-Za-z0-9\-_]+$/', $sku) === 1;
    }

    /**
     * Validate array not empty
     */
    public function validateArrayNotEmpty($data): bool
    {
        return is_array($data) && !empty($data);
    }

    /**
     * Sanitize string input
     */
    public function sanitizeString(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }
        
        return trim(strip_tags($input));
    }

    /**
     * Sanitize number input
     */
    public function sanitizeNumber($input): ?float
    {
        if ($input === null || $input === '') {
            return null;
        }
        
        return (float) $input;
    }

    /**
     * Validate và sanitize contact data
     */
    public function validateContactData(array $data, ?int $excludeId = null): array
    {
        $rules = [
            'contact_name' => 'required|string|max:255',
            'contact_code' => 'nullable|string|max:50|unique:contacts,contact_code' . ($excludeId ? ",$excludeId" : ''),
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_address' => 'nullable|string|max:500',
            'is_supplier' => 'boolean',
            'is_customer' => 'boolean',
            'is_trader' => 'boolean',
        ];

        $messages = [
            'contact_name.required' => 'Tên contact là bắt buộc.',
            'contact_name.max' => 'Tên contact không được vượt quá 255 ký tự.',
            'contact_code.unique' => 'Mã contact đã tồn tại.',
            'contact_email.email' => 'Email không đúng định dạng.',
            'contact_phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
        ];

        return $this->validate($data, $rules, $messages);
    }

    /**
     * Validate và sanitize product data
     */
    public function validateProductData(array $data, ?int $excludeId = null): array
    {
        $rules = [
            'product_name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku' . ($excludeId ? ",$excludeId" : ''),
            'category_id' => 'nullable|exists:categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'unit_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
        ];

        $messages = [
            'product_name.required' => 'Tên sản phẩm là bắt buộc.',
            'sku.unique' => 'SKU đã tồn tại.',
            'category_id.exists' => 'Danh mục không tồn tại.',
            'unit_id.exists' => 'Đơn vị không tồn tại.',
            'unit_price.numeric' => 'Giá phải là số.',
            'unit_price.min' => 'Giá phải lớn hơn hoặc bằng 0.',
        ];

        return $this->validate($data, $rules, $messages);
    }

    /**
     * Validate purchase order line data
     */
    public function validateOrderLineData(array $data): array
    {
        $rules = [
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
        ];

        $messages = [
            'product_id.required' => 'Sản phẩm là bắt buộc.',
            'product_id.exists' => 'Sản phẩm không tồn tại.',
            'qty.required' => 'Số lượng là bắt buộc.',
            'qty.min' => 'Số lượng phải lớn hơn 0.',
            'unit_price.required' => 'Đơn giá là bắt buộc.',
            'unit_price.min' => 'Đơn giá phải lớn hơn hoặc bằng 0.',
            'currency.size' => 'Mã tiền tệ phải có 3 ký tự.',
        ];

        return $this->validate($data, $rules, $messages);
    }

    /**
     * Validate inventory transaction data
     */
    public function validateInventoryData(array $data): array
    {
        $rules = [
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'transaction_direction' => 'required|in:import,export',
            'qty' => 'required|numeric|min:0.01',
            'io_price' => 'nullable|numeric|min:0',
            'io_currency' => 'nullable|string|size:3',
        ];

        $messages = [
            'warehouse_id.required' => 'Kho hàng là bắt buộc.',
            'warehouse_id.exists' => 'Kho hàng không tồn tại.',
            'product_id.required' => 'Sản phẩm là bắt buộc.',
            'product_id.exists' => 'Sản phẩm không tồn tại.',
            'transaction_direction.required' => 'Loại giao dịch là bắt buộc.',
            'transaction_direction.in' => 'Loại giao dịch không hợp lệ.',
            'qty.required' => 'Số lượng là bắt buộc.',
            'qty.min' => 'Số lượng phải lớn hơn 0.',
        ];

        return $this->validate($data, $rules, $messages);
    }
}