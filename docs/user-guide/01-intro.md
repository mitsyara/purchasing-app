# Hướng Dẫn Nhập Liệu Hệ Thống Purchasing App

Phiên bản hệ thống: v1.0 beta-1  
Cập nhật: 22/10/2025  
Liên hệ hỗ trợ: [Nam Vương Hùng Thái - Đẹp trai vãi nồi](tel:0916737190)

---

## Tổng quan
Tài liệu này hướng dẫn chi tiết cách nhập liệu cho các Form trong hệ thống Purchasing App.

---

## Danh sách Form

| Form | Mục đích | Tính năng chính |
|------|-----------|-----------------|
| **Liên hệ (Contact Form)** | Quản lý đối tác, nhà cung cấp, khách hàng | Quản lý thông tin, phân loại, sản phẩm chuyên môn, kho bãi & ngân hàng |
| **Đơn đặt hàng (Purchase Order Form)** | Tạo & quản lý đơn hàng mua | Quản lý thông tin đơn hàng, điều kiện thanh toán, nhân viên, sản phẩm |
| **Lô hàng (Purchase Shipment Form)** | Theo dõi & quản lý lô hàng | Trạng thái vận chuyển, thông quan, tỷ giá, lot/batch |
| **Sản phẩm đơn hàng (PO Product Form)** | Nhập chi tiết sản phẩm trong PO | Chọn sản phẩm, số lượng, giá, chống trùng sản phẩm |


---

## Quy tắc chung cho tất cả Form

- **Trường bắt buộc**: Các trường có dấu (*) phải nhập đủ.  
- **Trường duy nhất**: Mã số & số đơn hàng không được trùng.  
- **Tìm kiếm**: Dropdown hỗ trợ search theo từ khóa.  
- **Validation động**: Một số trường phụ thuộc giá trị trường khác (VD: Incoterm ảnh hưởng End User).  
- **Tự động hoàn thành**: Một số trường tự tính (VD: Exp Date tính từ Mfg Date).

---

## Quy trình nhập liệu khuyến nghị

### 1. Thiết lập dữ liệu cơ bản
1. Tạo Contact: Nhập thông tin nhà cung cấp / khách hàng.  
2. Cấu hình Sản phẩm: Đảm bảo dữ liệu sản phẩm chính xác.

### 2. Quy trình đặt hàng
1. Tạo Purchase Order (PO).  
2. Thêm sản phẩm bằng Form “PO Product”.  
3. Xác nhận PO → chuyển trạng thái Draft → In Progress.

### 3. Quản lý lô hàng
1. Tạo Shipment từ PO đã xác nhận.  
2. Cập nhật thông tin vận chuyển (ETA/ETD, tracking...).  
3. Xử lý thông quan.  
4. Cập nhật lot/batch tồn kho.

---

## Mẹo tăng hiệu quả

- Đánh dấu Favorite cho đối tác & sản phẩm thường dùng.  
- Copy thông tin từ đơn cũ hoặc lưu template cho đơn hàng mẫu.  
- Kiểm tra dữ liệu sớm để tránh lỗi về sau.  
- Dùng Auto-generate để tránh trùng mã.

---

## Xử lý lỗi thường gặp

| Lỗi | Nguyên nhân | Giải pháp |
|------|--------------|------------|
| Validation | Thiếu trường bắt buộc / sai định dạng | Kiểm tra các trường có dấu * |
| Trùng lặp | Mã / Số đơn hàng tồn tại | Dùng auto-generate hoặc nhập mã mới |
| Relationship | Chọn sai loại đối tác | Kiểm tra lại cấu hình Contact |
| Currency / Number | Sai format tiền tệ / số | Dùng dấu chấm (.) cho thập phân, kiểm tra local format |

---

## Hỗ trợ

Khi gặp sự cố:
1. Xem lại hướng dẫn chi tiết của form tương ứng.  
2. Kiểm tra workflow khuyến nghị.  
3. Đảm bảo cấu hình dữ liệu cơ bản chính xác.  
4. Liên hệ Nam Vương để được hỗ trợ thêm.

---

*Tài liệu Lưu hành nội bộ – NGHIÊM CẤM PHÁT TÁN.*
