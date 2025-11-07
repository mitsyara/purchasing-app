<?php

namespace App\Services\Contact;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Service xử lý business logic cho Contact
 */
class ContactService
{
    /**
     * Tạo mới contact
     */
    public function create(array $data): Contact
    {
        // Validate dữ liệu trước khi tạo
        $this->validateContactData($data);
        
        // Tự động tạo contact_code nếu không được cung cấp
        if (empty($data['contact_code'])) {
            $data['contact_code'] = $this->generateContactCode();
        }

        return Contact::create($data);
    }

    /**
     * Cập nhật contact
     */
    public function update(int $id, array $data): bool
    {
        $contact = Contact::findOrFail($id);
        
        // Validate dữ liệu trước khi cập nhật
        $this->validateContactData($data, $id);
        
        return $contact->update($data);
    }

    /**
     * Xóa contact
     */
    public function delete(int $id): bool
    {
        $contact = Contact::findOrFail($id);
        return $contact->delete();
    }

    /**
     * Lấy contact theo ID
     */
    public function findById(int $id): ?Contact
    {
        return Contact::find($id);
    }

    /**
     * Lấy contact theo code
     */
    public function findByCode(string $code): ?Contact
    {
        return Contact::where('contact_code', $code)->first();
    }

    /**
     * Lấy tất cả suppliers
     */
    public function getSuppliers(): Collection
    {
        return Contact::where('is_supplier', true)
            ->orderBy('contact_name')
            ->get();
    }

    /**
     * Lấy tất cả customers
     */
    public function getCustomers(): Collection
    {
        return Contact::where('is_customer', true)
            ->orderBy('contact_name')
            ->get();
    }

    /**
     * Lấy tất cả traders
     */
    public function getTraders(): Collection
    {
        return Contact::where('is_trader', true)
            ->orderBy('contact_name')
            ->get();
    }

    /**
     * Tìm kiếm contacts theo nhiều tiêu chí
     */
    public function search(array $criteria): Collection
    {
        $query = Contact::query();

        if (!empty($criteria['name'])) {
            $query->where('contact_name', 'LIKE', '%' . $criteria['name'] . '%');
        }

        if (!empty($criteria['code'])) {
            $query->where('contact_code', 'LIKE', '%' . $criteria['code'] . '%');
        }

        if (!empty($criteria['email'])) {
            $query->where('contact_email', 'LIKE', '%' . $criteria['email'] . '%');
        }

        if (isset($criteria['is_supplier'])) {
            $query->where('is_supplier', $criteria['is_supplier']);
        }

        if (isset($criteria['is_customer'])) {
            $query->where('is_customer', $criteria['is_customer']);
        }

        if (isset($criteria['is_trader'])) {
            $query->where('is_trader', $criteria['is_trader']);
        }

        return $query->orderBy('contact_name')->get();
    }

    /**
     * Kiểm tra contact code đã tồn tại
     */
    public function contactCodeExists(string $code, ?int $excludeId = null): bool
    {
        $query = Contact::where('contact_code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Validate dữ liệu contact
     */
    private function validateContactData(array $data, ?int $excludeId = null): void
    {
        $errors = [];

        // Kiểm tra các trường bắt buộc
        if (empty($data['contact_name'])) {
            $errors['contact_name'] = 'Tên contact là bắt buộc.';
        }

        // Kiểm tra contact code nếu có
        if (!empty($data['contact_code'])) {
            if ($this->contactCodeExists($data['contact_code'], $excludeId)) {
                $errors['contact_code'] = 'Mã contact đã tồn tại.';
            }
        }

        // Kiểm tra format email nếu có
        if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Email không đúng định dạng.';
        }

        // Kiểm tra số điện thoại nếu có
        if (!empty($data['contact_phone']) && !preg_match('/^[\d\s\-\+\(\)]+$/', $data['contact_phone'])) {
            $errors['contact_phone'] = 'Số điện thoại không đúng định dạng.';
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Tự động tạo contact code
     */
    private function generateContactCode(): string
    {
        $prefix = 'CT';
        $lastContact = Contact::where('contact_code', 'LIKE', $prefix . '%')
            ->orderBy('contact_code', 'desc')
            ->first();

        if (!$lastContact) {
            return $prefix . '0001';
        }

        $lastNumber = (int) substr($lastContact->contact_code, 2);
        $newNumber = $lastNumber + 1;

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Lấy suppliers cho select options
     */
    public function getSupplierOptions(): array
    {
        return $this->getSuppliers()
            ->pluck('contact_name', 'id')
            ->toArray();
    }

    /**
     * Lấy customers cho select options
     */
    public function getCustomerOptions(): array
    {
        return $this->getCustomers()
            ->pluck('contact_name', 'id')
            ->toArray();
    }

    /**
     * Lấy traders cho select options
     */
    public function getTraderOptions(): array
    {
        return $this->getTraders()
            ->pluck('contact_name', 'id')
            ->toArray();
    }
}