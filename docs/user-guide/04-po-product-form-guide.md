# Hướng dẫn nhập liệu Form Sản phẩm trong đơn hàng (PO Product Form)

## Tổng quan
Form được sử dụng để nhập thông tin chi tiết về các sản phẩm trong đơn đặt hàng (Purchase).

## Cấu trúc Form

### Thông tin sản phẩm

#### A. Chọn sản phẩm

- **Product** (Sản phẩm) - **Bắt buộc**
  - Chọn sản phẩm từ danh sách có sẵn
  - Có tính năng tìm kiếm
  - Chỉ hiển thị sản phẩm đang hoạt động khi tạo mới đơn hàng
  - Không thể chọn sản phẩm đã có (trùng)

**Lưu ý**: Tính năng Assortment (nhóm sản phẩm) đang phát triển thêm, hiện tại chỉ sử dụng Product.

#### B. Thông tin số lượng và giá

- **Qty** (Số lượng) - **Bắt buộc**
  - Nhập số lượng sản phẩm cần đặt
  - Sử dụng validation là số, tối thiểu 0,001

- **Unit Price** (Giá mua thực tế) - **Bắt buộc**
  - Nhập giá mua thực tế của sản phẩm
  - Đơn vị tiền tệ lấy từ đơn hàng
  - Sử dụng validation là số, tối thiểu 0,001

- **Contract Price** (Giá hợp đồng)
  - Nhập giá hợp đồng (nếu có)
  - Đơn vị tiền tệ lấy từ đơn hàng
  - Không bắt buộc. Tự lấy giá thực tế nếu không nhập.
  - Sử dụng validation là số, tối thiểu 0,001

## Quy tắc nhập liệu

### Trường bắt buộc
- Product (Sản phẩm)
- Qty (Số lượng)
- Unit Price (Giá đơn vị)

### Trường tùy chọn
- Contract Price (Giá hợp đồng)

### Quy tắc ràng buộc
1. Mỗi sản phẩm chỉ có thể được chọn một lần trong cùng một đơn hàng
2. Chỉ có thể chọn sản phẩm đang hoạt động khi tạo đơn hàng mới
3. Khi chọn Product, hệ thống sẽ tự động bỏ chọn Assortment (và ngược lại)
4. Số lượng và giá phải là số hợp lệ, tối thiểu 0,001

## Mẹo sử dụng

1. **Chọn sản phẩm**: Sử dụng tính năng tìm kiếm để nhanh chóng tìm được sản phẩm cần thiết

2. **Nhập số lượng**: Đảm bảo nhập đúng đơn vị tính theo thông tin sản phẩm

3. **Giá cả**: 
   - Unit Price là giá thực tế giao dịch
   - Contract Price là giá trong hợp đồng (nếu khác với giá giao dịch)

4. **Validation**: Hệ thống sẽ kiểm tra và ngăn không cho chọn trùng sản phẩm