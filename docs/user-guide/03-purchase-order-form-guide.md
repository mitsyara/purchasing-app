# Hướng dẫn nhập liệu Form Đơn đặt hàng (Purchase Order Form)

## Tổng quan
Form này được sử dụng để tạo và quản lý các đơn đặt hàng mua hàng từ nhà cung cấp.

## Cấu trúc Form

### Phần 1: Thông tin đơn hàng và nhân viên

#### A. Thông tin đơn hàng cơ bản

##### Thông tin công ty và đối tác
- **Company** (Công ty) - **Bắt buộc**
  - Chọn công ty/pháp nhân nhập hàng

- **Supplier** (Nhà cung cấp) - **Bắt buộc**
  - Chọn nhà cung cấp thực tế
  - Chỉ hiển thị các đối tác được đánh dấu là "Trader"
  - Có tính năng tìm kiếm
  - Không thể chọn trùng với Contract Supplier hoặc Payment Receiver

- **Contract Supplier** (Nhà cung cấp hợp đồng)
  - Chọn nếu có nhà cung cấp khác đứng tên hợp đồng
  - Chỉ hiển thị các đối tác được đánh dấu là "Trader"
  - Không thể chọn trùng với Supplier hoặc Payment Receiver

- **Payment Receiver** (Người nhận thanh toán)
  - Chọn nếu người nhận thanh toán khác với nhà cung cấp/nhà cung cấp hợp đồng
  - Chỉ hiển thị các đối tác được đánh dấu là "Trader"
  - Không thể chọn trùng với Supplier hoặc Contract Supplier

- **End User** (Người dùng cuối)
  - Chọn nếu có khách hàng nội địa cụ thể
  - Chỉ hiển thị các đối tác được đánh dấu là "Customer"
  - Trường này có thể bị vô hiệu hóa tùy thuộc vào Incoterm

##### Điều khoản thanh toán
- **Payment Term Delay At** (Tính từ)
  - Chọn mốc thời gian tính thanh toán
  - Các tùy chọn theo ngày hợp đồng (Order Date), ngày thực đi (ATD), ngày thực đến (ATA)

- **Payment Term Days** (Số ngày thanh toán)
  - Nhập số ngày trước/sau mốc thời gian cho phép
  - Có thể chọn Before/After để xác định trước hay sau mốc thời gian
  - Giá trị mặc định: 0, 30, 60 ngày
  - Chỉ nhập số dương, hệ thống tự động gán theo mốc thời gian.
  - Ví dụ: hạn thanh toán là sau 30 ngày kể từ ngày hợp đồng: After Order Date 30.

#### B. Thông tin nhân viên phụ trách

- **Purchaser** (Nhân viên mua hàng) - **Bắt buộc**
  - Chọn nhân viên phụ trách mua hàng
  - Có tính năng tìm kiếm

- **Salesperson** (Nhân viên bán hàng)
  - Chọn nhân viên phụ trách bán hàng nếu có
  - Có tính năng tìm kiếm

- **Clearance Docs staff** (Nhân viên chứng từ)
  - Chọn nhân viên phụ trách làm chứng từ thông quan
  - Có tính năng tìm kiếm

- **Declarant staff** (Nhân viên khai báo)
  - Chọn nhân viên phụ trách khai báo hải quan
  - Có tính năng tìm kiếm

#### C. Thông tin vận chuyển (ETA/ETD)
- Các trường thông tin về thời gian dự kiến đến/đi

### Phần 2: Thông tin chung

#### A. Trạng thái và số đơn hàng

- **Order Status** (Trạng thái đơn hàng) - **Bắt buộc**
  - Chọn trạng thái đơn hàng
  - Các tùy chọn: Draft, Inprogress, Completed, Canceled
  - Mặc định: Draft
  - Khi tạo mới không thể chọn "Canceled"

- **Order Number** (Số đơn hàng)
  - Nhập số đơn hàng
  - Phải là số duy nhất trong hệ thống
  - Bắt buộc khi trạng thái là "Inprogress" hoặc "Completed"
  - Có nút "Generate Order Number" để tự động tạo số

#### B. Thông tin ngày tháng và địa điểm

- **Order Date** (Ngày đặt hàng)
  - Chọn ngày đặt hàng
  - Không quá 6 tháng trước ngày hiện tại
  - Không được sau ngày hiện tại

- **Import Warehouse** (Kho nhập)
  - Chọn kho để nhập hàng

- **Import Port** (Cảng nhập)
  - Chọn cảng để nhập hàng

#### C. Điều kiện thương mại và tiền tệ

- **Incoterm** (Điều kiện giao hàng)
  - Chọn điều kiện giao hàng quốc tế
  - Mặc định: CIF
  - Khi chọn khác CIF sẽ vô hiệu hóa trường End User (khách nội địa)

- **Currency** (Tiền tệ) - **Bắt buộc**
  - Chọn loại tiền tệ
  - Hiển thị các tiền tệ của quốc gia yêu thích
  - Mặc định: USD

#### D. Tùy chọn khác

- **Skip Invoice** (Bỏ qua hóa đơn)
  - Đánh dấu nếu không nhập khẩu chính ngạch (tiểu ngạch)
  - Mặc định: false

### Phần 3: Sản phẩm (chỉ hiển thị khi tạo mới)

- **Products** (Sản phẩm)
  - Sử dụng để thêm nhiều sản phẩm
  - Tối thiểu 1 sản phẩm
  - Mặc định có 1 dòng sản phẩm
  - Nhấn nút "Add Product" để thêm sản phẩm

## Quy tắc nhập liệu

### Trường bắt buộc
- Company (Công ty)
- Supplier (Nhà cung cấp)
- Purchaser (Nhân viên mua hàng)
- Order Status (Trạng thái đơn hàng)
- Currency (Tiền tệ)
- Order Number khi trạng thái là Inprogress hoặc Completed

### Trường duy nhất (Không trùng)
- Order Number (Số đơn hàng)

### Quy tắc ràng buộc
1. Supplier, Contract Supplier và Payment Receiver không được trùng nhau
2. Order Number bắt buộc khi trạng thái là "Inprogress" hoặc "Completed"
3. Order Date không được quá 6 tháng trước, hoặc sau ngày hiện tại
4. End User bị vô hiệu hóa khi Incoterm khác CIF
5. Tối thiểu phải có 1 sản phẩm trong đơn hàng

### Tính năng đặc biệt
1. **Tự động tạo Order Number**: Nút "Generate Order Number" sẽ tạo số đơn hàng dựa trên Company và Order Date
2. **Ràng buộc động**: Các trường Supplier, Contract Supplier và Payment Receiver sẽ tự động vô hiệu hóa lựa chọn trùng lặp
3. **Validation thông minh**: Form sẽ kiểm tra các điều kiện phức tạp như quan hệ giữa các trường

## Mẹo sử dụng

1. **Trình tự nhập liệu được khuyến nghị**:
   - Chọn Company và Order Date trước
   - Chọn Supplier chính
   - Nhập thông tin nhân viên phụ trách
   - Thiết lập điều kiện thanh toán
   - Chọn Incoterm và Currency
   - Thêm sản phẩm

2. **Sử dụng tính năng tìm kiếm**: Tất cả các dropdown đều có tính năng tìm kiếm để nhanh chóng tìm được đối tác cần thiết

3. **Tạo số đơn hàng**: Sử dụng nút "Generate Order Number" sau khi đã chọn Company và Order Date để tự động tạo số đơn hàng theo chuẩn

4. **Quản lý trạng thái**: Bắt đầu với trạng thái "Draft", sau đó chuyển sang "Inprogress" khi bắt đầu xử lý và "Completed" khi hoàn thành

5. **Chú ý Incoterm**: Khi chọn Incoterm khác CIF, trường End User sẽ bị vô hiệu hóa tự động