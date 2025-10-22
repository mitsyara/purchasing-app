# Hướng dẫn nhập liệu Form Lô hàng (Purchase Shipment Form)

## Tổng quan
Form này được sử dụng để quản lý thông tin chi tiết về các lô hàng trong đơn đặt hàng, bao gồm thông tin vận chuyển, thông quan và quản lý Lo/Lot.

## Cấu trúc Form

Form được chia thành 4 tab chính:

### 1. Tab "Shipment Info" (Thông tin lô hàng)

#### A. Thông tin đơn hàng liên quan
- **Purchase Order** (Đơn đặt hàng)
  - Hiển thị số đơn hàng liên quan
  - Có thể nhấp vào để xem chi tiết đơn hàng

#### B. Trạng thái và địa điểm

- **Shipment Status** (Trạng thái lô hàng) - **Bắt buộc**
  - Chọn trạng thái của lô hàng
  - Các tùy chọn tương ứng

- **Port** (Cảng) 
  - Chọn cảng nhập lô hàng
  - Bắt buộc nếu đơn hàng nhập khẩu chính ngạch

- **Warehouse** (Kho) - **Bắt buộc**
  - Chọn kho để nhập hàng hóa

#### C. Nhân viên phụ trách

- **Docs Staff** (Nhân viên chứng từ) - **Bắt buộc**
  - Tự lấy thông tin từ đơn hàng gốc
  - Chọn nhân viên phụ trách xử lý chứng từ (nếu có thay đổi)

#### D. Thông tin vận chuyển

- **Tracking Number** (Số vận đơn)
  - Nhập số theo dõi vận đơn

- **ETA/ETD Fields** (Thời gian dự kiến đến/đi)
  - Tự lấy thông tin từ đơn hàng gốc

- **Actual Arrival/Departure** (Thời gian thực tế đến/đi)
  - Nhập thời gian thực tế ATD/ATA

### 2. Tab "Clearance & Exchange Rate" (Thông quan & Tỷ giá)

#### A. Nhân viên phụ trách thông quan

- **Declarant** (Nhân viên khai báo)
  - Chọn nhân viên phụ trách khai báo
  - Bắt buộc nếu đơn hàng nhập khẩu chính ngạch

- **Processing Staff** (Nhân viên xử lý)
  - Chọn nhân viên phụ trách nộp hồ sơ
  - Bắt buộc nếu đơn hàng nhập khẩu chính ngạch

#### B. Thông tin tỷ giá

- **Exchange Rate** (Tỷ giá)
  - Nhập tỷ giá quy đổi
  - Bấm vào "Get Rate" để tự động lấy tỷ giá từ Vietcombank
  - Ưu tiên lấy ngày thông quan trước, ngày nộp tờ khai sau để xác định ngày tỷ giá.

- **Final Rate?** (Chốt Tỷ giá?)
  - Đánh dấu để xác nhận chốt tỷ giá

#### C. Khai báo hải quan

- **Declaration No.** (Số tờ khai)
  - Nhập số tờ khai hải quan

- **Declaration Date** (Ngày khai báo)
  - Chọn ngày nộp tờ khai

- **Clearance Status** (Tình trạng thông quan) - **Bắt buộc**
  - Chọn tình trạng thông quan hiện tại

- **Clearance Date** (Ngày thông quan)
  - Chọn ngày hoàn thành thông quan

**Lưu ý**: Toàn bộ phần Customs Declaration sẽ bị vô hiệu hóa nếu đơn hàng không yêu cầu khai báo Hải quan.

### 3. Tab "Products" (Sản phẩm)

#### Quản lý sản phẩm trong lô hàng

- **Products** (Danh sách sản phẩm)
  - Hiển thị các sản phẩm từ đơn hàng gốc
  - Tối thiểu 1 sản phẩm trong 1 lô hàng

#### Thông tin từng sản phẩm

- **Break Price** (Giá hoà vốn)
  - Nhập giá hoà vốn, tính bằng VND

#### Quản lý Lot/Batch

- **Transactions** (Giao dịch)
  - Sử dụng để khai báo số Lot/Batch
  - Hiển thị dạng bảng với các cột:
    - Lot/Batch No. (Số Lot/Batch, Bắt buộc)
    - Quantity (Số lượng, Bắt buộc)
    - Mfg Date (Ngày sản xuất, Bắt buộc)
    - Exp Date (Ngày hết hạn, Bắt buộc)

##### Chi tiết từng Lot/Batch:

- **Lot No.** (Số lô/lot) - **Bắt buộc**
  - Nhập số Lot/Batch của sản phẩm

- **Quantity** (Số lượng) - **Bắt buộc**
  - Nhập số lượng trong lô
  - Có validation: tổng SL không được vượt quá SL của Lô hàng về (shipment)

- **Mfg Date** (Ngày sản xuất) - **Bắt buộc**
  - Chọn ngày sản xuất
  - Không được sau ngày hiện tại
  - Khi thay đổi sẽ tự động tính toán Exp Date

- **Exp Date** (Ngày hết hạn) - **Bắt buộc**
  - Tự động tính toán dựa vào Mfg Date và Product Life Cycle
  - Có thể chỉnh sửa thủ công nếu cần

##### Quy tắc đặc biệt:
- Có thể thêm lot/batch mới (nút "Add Lot/Batch")
- Chỉ cho phép thêm lot/batch nếu Đơn hàng không phải là CIF

### 4. Tab "Costs & Notes" (Chi phí & Ghi chú)

#### A. Chi phí phát sinh

- **Extra Costs** (Chi phí phát sinh)
  - Dùng để nhập các chi phí phát sinh
  - Chi phí tính bằng VND

#### B. Ghi chú

- **Notes** (Ghi chú)
  - Nhập ghi chú bổ sung về lô hàng
  - Trường văn bản nhiều dòng

## Tính năng đặc biệt

### 1. Tự động lấy tỷ giá
- Nút "Get Rate" sẽ tự động lấy tỷ giá từ VCB
- Dựa vào ngày Clearance Date hoặc Declaration Date
- Chỉ hoạt động với tiền tệ khác với VND

### 2. Tự động tính ngày hết hạn
- Khi nhập Mfg Date, hệ thống tự động tính Exp Date
- Dựa vào Product Life Cycle: Exp Date = Mfg Date + Life Cycle - 1 ngày

### 3. Validation
- Tổng quantity trong tất cả transactions không được vượt quá quantity có sẵn
- Kiểm tra tuần tự từng Lot/Batch để xác định dòng nào vượt quá SL

## Quy tắc nhập liệu

### Trường bắt buộc
- Shipment Status
- Warehouse
- Docs Staff
- Clearance Status
- Các trường trong Lot/Batch: Lot No., Quantity, Mfg Date, Exp Date

### Trường bắt buộc có điều kiện
- Port (nếu đơn hàng mua nhập khẩu hải quan chính ngạch)
- Declarant (nếu đơn hàng mua nhập khẩu hải quan chính ngạch)
- Processing Staff (nếu đơn hàng mua nhập khẩu hải quan chính ngạch)

### Quy tắc ràng buộc
1. Clearance Date không được sau ngày hiện tại
2. Mfg Date không được sau ngày hiện tại
3. Tổng SL Lot/Batch không được vượt quá SL sản phẩm trong Lô hàng (Shipment) có sẵn
4. Chi phí phát sinh phải có giá trị nếu được thêm mới

## Mẹo sử dụng

1. **Trình tự nhập liệu được khuyến nghị**:
   - Chọn Shipment Status và Warehouse
   - Chọn nhân viên phụ trách
   - Nhập thông tin thông quan (nếu cần)
   - Thiết lập tỷ giá (nếu cần)
   - Khai báo products và lots/batches
   - Thêm chi phí phát sinh (nếu có)

2. **Sử dụng tự động tỷ giá**: Nhập Declaration Date hoặc Clearance Date trước khi sử dụng chức năng "Get Rate"

3. **Quản lý Lot/Batch**: Luôn Nhập Mfg Date trước để hệ thống tự động tính Exp Date

4. **Kiểm tra SL**: Luôn kiểm tra tổng SL Lot/Batch không vượt quá SL lô hàng (shipment)

5. **Label hiển thị**: Mỗi sản phẩm sẽ hiển thị tên, số lượng, đơn vị và giá để dễ nhận biết