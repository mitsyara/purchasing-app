# CustomsData Category Processing Commands

Hệ thống Artisan commands để xử lý phân loại category cho CustomsData với khả năng đa luồng.

**📁 Namespace:** `App\Console\Commands\CustomsData`

## Các Commands

### 1. `php artisan cus-data:category-auto` ⭐ **KHUYẾN NGHỊ**
**Mục đích:** Auto-detect môi trường và chạy version phù hợp

**Options:**
- `--processes=3` : Số lượng process song song (chỉ áp dụng nếu multi-process available)
- `--chunk-size=500` : Kích thước chunk (mặc định: 500)
- `--force` : Buộc xử lý lại tất cả records
- `--stats` : Hiển thị performance stats chi tiết

**Ví dụ:**
```bash
# Auto-detect và chạy version tối ưu
php artisan cus-data:category-auto --stats

# Với cấu hình tùy chỉnh
php artisan cus-data:category-auto --chunk-size=300 --processes=2 --stats
```

### 2. `php artisan cus-data:category`
**Mục đích:** Xử lý phân loại category cho CustomsData với đa luồng

**Options:**
- `--processes=4` : Số lượng process chạy song song (mặc định: 4)
- `--chunk-size=1000` : Kích thước chunk cho mỗi process (mặc định: 1000)  
- `--force` : Buộc xử lý lại tất cả records (bỏ qua kiểm tra hash)
- `--timeout=3600` : Timeout cho mỗi process tính bằng giây (mặc định: 1 giờ)

**Ví dụ:**
```bash
# Chạy với cấu hình mặc định
php artisan cus-data:category

# Chạy với 8 process song song, chunk size 500
php artisan cus-data:category --processes=8 --chunk-size=500

# Buộc xử lý lại tất cả records
php artisan cus-data:category --force

# Chạy với timeout 30 phút
php artisan cus-data:category --timeout=1800
```

### 3. `php artisan cus-data:category-single`
**Mục đích:** Version single-threaded cho shared hosting

**Options:**
- `--chunk-size=500` : Kích thước chunk (mặc định: 500)
- `--force` : Buộc xử lý lại tất cả records  
- `--stats` : Hiển thị performance stats chi tiết

**Ví dụ:**
```bash
# Cho shared hosting
php artisan cus-data:category-single --chunk-size=300 --stats

# Process tất cả records
php artisan cus-data:category-single --force --stats
```

### 4. `php artisan cus-data:category-worker`
**Mục đích:** Worker process để xử lý một chunk (được gọi tự động bởi command chính)

**Arguments:**
- `ids` : Danh sách ID cách nhau bởi dấu phẩy
- `keywords-hash` : Hash của keywords hiện tại
- `chunk-index` : Index của chunk

**Lưu ý:** Command này thường không được gọi trực tiếp.

### 5. `php artisan cus-data:category-monitor`
**Mục đích:** Theo dõi tiến trình xử lý CustomsData Category

**Options:**
- `--refresh=5` : Thời gian refresh tính bằng giây (mặc định: 5)
- `--once` : Chỉ hiển thị một lần, không refresh

**Ví dụ:**
```bash
# Theo dõi với refresh mỗi 5 giây
php artisan cus-data:category-monitor

# Theo dõi với refresh mỗi 10 giây  
php artisan cus-data:category-monitor --refresh=10

# Chỉ hiển thị một lần
php artisan cus-data:category-monitor --once
```

### 6. `php artisan cus-data:category-cleanup`
**Mục đích:** Dọn dẹp dữ liệu CustomsData Category

**Options:**
- `--dry-run` : Chỉ hiển thị kết quả, không thực thi thay đổi
- `--reset-hash` : Reset category_keywords_hash về null cho tất cả records
- `--reset-category` : Reset customs_data_category_id về null cho tất cả records  
- `--fix-orphans` : Sửa các records có category_id không tồn tại

**Ví dụ:**
```bash
# Xem thống kê cleanup
php artisan cus-data:category-cleanup

# Kiểm tra dry-run trước khi reset hash
php artisan cus-data:category-cleanup --reset-hash --dry-run

# Reset tất cả hash
php artisan cus-data:category-cleanup --reset-hash

# Reset tất cả category
php artisan cus-data:category-cleanup --reset-category

# Sửa các records có category không hợp lệ
php artisan cus-data:category-cleanup --fix-orphans
```

## Workflow Sử Dụng

### 1. Xử lý lần đầu hoặc xử lý lại từ đầu:
```bash
# Xem thống kê hiện tại
php artisan cus-data:category-monitor --once

# Reset về trạng thái ban đầu (tùy chọn)
php artisan cus-data:category-cleanup --reset-category

# Chạy xử lý (KHUYẾN NGHỊ - auto-detect môi trường)
php artisan cus-data:category-auto --stats

# Hoặc manual choice
php artisan cus-data:category --processes=2 --chunk-size=300    # VPS/Dedicated
php artisan cus-data:category-single --chunk-size=300           # Shared hosting
```

### 2. Theo dõi tiến trình:
```bash
# Mở terminal khác để theo dõi
php artisan cus-data:category-monitor
```

### 3. Xử lý incremental (chỉ records mới/thay đổi):
```bash
# Chạy bình thường (sẽ bỏ qua records đã xử lý với hash hiện tại)
php artisan cus-data:category-auto --stats
```

### 4. Troubleshooting:
```bash
# Kiểm tra tình trạng dữ liệu
php artisan cus-data:category-cleanup

# Sửa các records có vấn đề
php artisan cus-data:category-cleanup --fix-orphans

# Reset và chạy lại nếu cần
php artisan cus-data:category-cleanup --reset-hash
php artisan cus-data:category-auto --force --stats
```

### 5. Shared Hosting Issues:
```bash
# Nếu command chỉ hiển thị hash rồi dừng
php artisan cus-data:category-single --stats

# Hoặc dùng auto-detect
php artisan cus-data:category-auto --stats
```

## Cách Hoạt Động

1. **Command chính** (`cus-data:category`):
   - Lấy danh sách ID cần xử lý (bỏ qua records đã có hash hiện tại)
   - Chia thành các chunk
   - Tạo các worker process song song
   - Theo dõi và collect kết quả

2. **Worker process** (`cus-data:category-worker`):
   - Nhận một chunk IDs
   - Load records từ database  
   - Gọi `guessCategoryByName()` cho mỗi record
   - Report kết quả về parent process

3. **Keywords Hash System**:
   - Mỗi lần chạy sẽ tính hash của tất cả categories và keywords
   - Chỉ xử lý records chưa có hash hoặc có hash khác với hash hiện tại
   - Đảm bảo không xử lý lại records không cần thiết

## Performance Tuning

### Tối ưu số processes:
- **CPU cores ít (2-4):** `--processes=2`
- **CPU cores trung bình (4-8):** `--processes=3-4` (mặc định)
- **CPU cores nhiều (8+):** `--processes=4-6` (giới hạn tối đa 6)

**⚠️ Lưu ý:** Giới hạn tối đa 6 processes để tránh overload database connection pool.

### Tối ưu chunk size:
- **Database nhỏ (<100K records):** `--chunk-size=500`
- **Database trung bình (100K-1M):** `--chunk-size=1000` (mặc định)  
- **Database lớn (>1M):** `--chunk-size=2000`

### Memory considerations:
- Mỗi worker process sẽ tiêu thụ memory cho chunk của nó
- Monitor memory usage: `php artisan cus-data:category-monitor`
- Giảm chunk-size nếu gặp memory issues

## Logging

Tất cả commands đều log vào Laravel log system:
- Info logs: Tiến trình xử lý, thống kê
- Warning logs: Records xử lý thất bại
- Error logs: Lỗi process, system errors

Log location: `storage/logs/laravel.log`

## Tính Năng An Toàn

1. **Incremental Processing:** Chỉ xử lý records cần thiết
2. **Hash Checking:** Tránh xử lý lại records không cần
3. **Error Isolation:** Lỗi ở một process không ảnh hưởng processes khác
4. **Dry Run:** Test trước khi thực hiện cleanup
5. **Monitoring:** Theo dõi real-time tiến trình