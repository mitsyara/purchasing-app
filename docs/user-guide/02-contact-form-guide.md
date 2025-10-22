# Hướng dẫn nhập liệu Form Liên hệ (Contact Form)

## Tổng quan
Form này được sử dụng để nhập thông tin về các đối tác liên hệ trong hệ thống, bao gồm nhà cung cấp, khách hàng và các loại đối tác khác.

## Cấu trúc Form

Form được chia thành 4 tab chính:

### 1. Tab "Contact Info" (Thông tin liên hệ)

#### A. Phần "Basic Information" (Thông tin cơ bản)

##### Thông tin công ty
- **Company Name** (Tên công ty) - **Bắt buộc**
  - Nhập tên đầy đủ của công ty/đối tác
  
- **Company Address** (Địa chỉ công ty)
  - Nhập địa chỉ trụ sở chính của công ty

##### Thông tin liên hệ
- **Tax Code** (Mã số thuế)
  - Nhập mã số thuế của công ty
  - Bắt buộc nếu đối tác là khách hàng
  
- **Email**
  - Nhập email liên hệ chính
  - Phải đúng định dạng email (abc@123.xyz)
  
- **Phone** (Số điện thoại)
  - Nhập số điện thoại liên hệ chính

##### Thông tin người đại diện
- **Rep. Title** (Chức danh)
  - Nhập chức danh của người đại diện
  - Bắt buộc nếu có nhập tên đại diện

- **Rep. Gender** (Giới tính)
  - Chọn giới tính của người đại diện
  - Các tùy chọn có sẵn trong hệ thống
  - Bắt buộc nếu có nhập tên đại diện
  
- **Rep. Name** (Tên)
  - Nhập tên đầy đủ của người đại diện
  - Bắt buộc nếu có chọn giới tính

##### Chứng nhận GMP
- **GMP No.** (Số chứng nhận GMP)
  - Nhập số chứng nhận GMP nếu có
  
- **GMP Expires At** (Ngày hết hạn GMP)
  - Chọn ngày hết hạn của chứng nhận GMP

##### Chứng chỉ khác
- **Certificates** (Các chứng chỉ)
  - Nhập danh sách các chứng chỉ khác
  - Có thể nhập nhiều chứng chỉ, phân cách bằng dấu phẩy, chấm phẩy hoặc khoảng trắng

#### B. Phần "Additional Information" (Thông tin bổ sung)

##### Loại đối tác
- **Manufacturer** (Nhà sản xuất)
  - Bật/tắt để đánh dấu đây là nhà sản xuất
  
- **Customer** (Khách hàng)
  - Bật/tắt để đánh dấu đây là khách hàng
  
- **Trader** (Nhà phân phối)
  - Bật/tắt để đánh dấu đây là nhà phân phối

##### Thông tin khác
- **Favorite** (Yêu thích)
  - Đánh dấu đối tác yêu thích để dễ dàng tìm kiếm/sắp xếp

- **Code** (Mã đối tác)
  - Nhập mã định danh duy nhất cho đối tác
  - Phải là mã duy nhất trong hệ thống
  
- **Short Name** (Tên viết tắt)
  - Nhập tên viết tắt của đối tác
  - Bắt buộc nếu đối tác là nhà sản xuất
  - Phải là tên duy nhất trong hệ thống

##### Thông tin địa lý
- **Country** (Quốc gia)
  - Chọn quốc gia từ danh sách có sẵn
  - Mặc định là Việt Nam
  - Có thể tìm kiếm trong danh sách
  
- **Region** (Khu vực)
  - Chọn khu vực địa lý
  - Mặc định là "Other"

### 2. Tab "Warehouse & Other Info" (Kho bãi & Thông tin khác)

#### A. Phần "Warehouses" (Kho bãi)
- **Warehouse Addresses** (Địa chỉ kho)
  - Có thể thêm nhiều địa chỉ kho
  - Mỗi địa chỉ kho là một dòng riêng biệt

#### B. Phần "Bank Info" (Thông tin ngân hàng)
- **Bank Information** (Thông tin ngân hàng)
  - Có thể thêm nhiều thông tin ngân hàng
  - Bao gồm tên ngân hàng, số tài khoản, v.v.

#### C. Phần "Other Info" (Thông tin khác)
- **Other Information** (Thông tin khác)
  - Có thể thêm các thông tin bổ sung khác
  - Không giới hạn số lượng

#### D. Ghi chú
- **Notes** (Ghi chú)
  - Nhập các ghi chú bổ sung về đối tác
  - Trường văn bản nhiều dòng

### 3. Tab "Specialized Products" (Sản phẩm chuyên môn)

- **Specialized in** (Chuyên về)
  - Chọn các sản phẩm mà đối tác chuyên phân phối
  - Chỉ hiển thị khi đối tác là nhà phân phối
  - Có thể chọn nhiều sản phẩm

- **Interested in** (Quan tâm đến)
  - Chọn các sản phẩm mà khách hàng quan tâm
  - Chỉ hiển thị khi đối tác là khách hàng
  - Có thể chọn nhiều sản phẩm

### 4. Tab "Comments" (Bình luận)

- Phần để thêm các bình luận, ghi chú theo dõi về đối tác
- Mỗi người dùng chỉ chỉnh sửa được comment của bản thân

## Quy tắc nhập liệu

### Trường bắt buộc
- Company Name (Tên công ty)
- Rep. Name và Rep. Gender (nếu nhập một trong hai)
- Rep. Title (nếu có Rep. Name)
- Short Name (nếu là nhà sản xuất)
- Tax Code (nếu là khách hàng)

### Trường duy nhất
- Contact Code (Mã đối tác)
- Short Name (Tên viết tắt)

### Lưu ý đặc biệt
1. Khi đánh dấu là "Manufacturer", trường "Short Name" sẽ trở thành bắt buộc
2. Khi đánh dấu là "Customer", trường "Tax Code" sẽ trở thành bắt buộc
3. Tab "Specialized Products" chỉ hiển thị các trường phù hợp dựa vào loại đối tác đã chọn
4. Có thể nhập nhiều địa chỉ kho, thông tin ngân hàng và thông tin khác
5. Khi nhập chứng chỉ, có thể sử dụng dấu phẩy, chấm phẩy hoặc khoảng trắng để phân cách
6. Tất cả sản phẩm được chọn phải là sản phẩm đang hoạt động

## Mẹo sử dụng
- Sử dụng tính năng tìm kiếm trong các dropdown để nhanh chóng tìm được quốc gia hoặc sản phẩm cần chọn
- Đánh dấu "Favorite" cho các đối tác thường xuyên làm việc để dễ dàng tìm kiếm sau này
- Điền đầy đủ thông tin liên hệ để thuận tiện cho việc giao tiếp sau này