# CHƯƠNG 1: TỔNG QUAN VỀ ĐỀ TÀI

---

## 1.1. Giới thiệu Đề tài

### Tên Đề tài
**Xây dựng hệ thống quản lý chuỗi cửa hàng Cộng Cà Phê trên nền tảng Web**

### Tính cần thiết
Trong bối cảnh ngành F&B (Food & Beverage) phát triển mạnh mẽ, việc quản lý chuỗi cửa hàng cà phê đòi hỏi một hệ thống số hóa để:
- Quản lý bán hàng tại quầy (POS) nhanh chóng, chính xác
- Theo dõi tồn kho, nhập/xuất hàng hóa theo thời gian thực
- Quản lý ca làm việc, đối soát doanh thu cuối ca
- Thống kê, báo cáo doanh thu, lợi nhuận theo ngày/tháng
- Phân quyền nhân viên theo vai trò (Admin, Thu ngân)

### Mục tiêu Hệ thống
1. Xây dựng giao diện POS thân thiện, tối ưu cho thao tác nhanh
2. Quản lý sản phẩm với giá bán, giá vốn, tồn kho
3. Xử lý đơn hàng, tính tiền, trừ kho tự động
4. Ghi nhận lịch sử giao dịch nhập/xuất kho
5. Báo cáo thống kê doanh thu, lợi nhuận
6. Xuất báo cáo ra file Excel

---

## 1.2. Đối tượng Sử dụng

### 1.2.1. Admin (Quản trị viên)
- Quản lý toàn bộ hệ thống
- CRUD nhân viên, sản phẩm, danh mục
- Xem báo cáo thống kê, lịch sử ca làm việc
- Phân quyền và khóa tài khoản

### 1.2.2. Thu ngân (Nhân viên bán hàng)
- Đăng nhập vào ca làm việc
- Thao tác bán hàng trên giao diện POS
- Nhập hàng vào kho
- Chốt ca, đối soát tiền mặt
- Xem lịch sử đơn hàng trong ca

---

## 1.3. Phạm vi Hệ thống

### Các Module chính

```
+--------------------------------------------------+
|           HỆ THỐNG QUẢN LÝ CỘNG CÀ PHÊ           |
+--------------------------------------------------+
|                                                  |
|  +------------+  +------------+  +------------+  |
|  |   NGƯỜI 1  |  |   NGƯỜI 2  |  |   NGƯỜI 3  |  |
|  |   (Huấn)   |  |    (An)    |  |   (Danh)   |  |
|  +------------+  +------------+  +------------+  |
|  | - Đăng nhập|  | - POS      |  | - Nhập kho |  |
|  | - Phân     |  | - Giỏ hàng |  | - Lịch sử  |  |
|  |   quyền    |  | - Thanh    |  |   kho      |  |
|  | - Quản lý  |  |   toán     |  | - Báo cáo  |  |
|  |   nhân viên|  | - Đơn hàng |  |   ca       |  |
|  | - Quản lý  |  | - Trừ kho  |  | - Thống kê |  |
|  |   sản phẩm |  |            |  | - Dashboard|  |
|  +------------+  +------------+  +------------+  |
|                                                  |
+--------------------------------------------------+
```

---

## 1.4. Đặc tả Yêu cầu

### 1.4.1. Yêu cầu Chức năng

| STT | Module | Chức năng | Mô tả |
|-----|--------|-----------|-------|
| 1 | Đăng nhập | Xác thực | Kiểm tra username/password, phân quyền |
| 2 | Nhân viên | CRUD | Thêm, sửa, xóa, khóa tài khoản |
| 3 | Sản phẩm | CRUD | Thêm, sửa, xóa, ẩn/hiện sản phẩm |
| 4 | Danh mục | Quản lý | Phân loại sản phẩm theo nhóm |
| 5 | POS | Bán hàng | Chọn món, tùy chỉnh size/topping |
| 6 | Giỏ hàng | Quản lý | Thêm, sửa số lượng, xóa món |
| 7 | Thanh toán | Xử lý | Tính tiền, tạo đơn, trừ kho |
| 8 | Đơn hàng | Lịch sử | Xem danh sách, chi tiết đơn |
| 9 | Nhập kho | Cập nhật | Tăng số lượng, cập nhật giá vốn |
| 10 | Lịch sử kho | Theo dõi | Log nhập/xuất theo thời gian |
| 11 | Giao ca | Đối soát | So sánh doanh thu máy vs thực tế |
| 12 | Thống kê | Báo cáo | Doanh thu, lợi nhuận, món bán chạy |
| 13 | Export | Xuất file | Xuất Excel theo ngày/tháng/khoảng |

### 1.4.2. Yêu cầu Phi chức năng

| Yêu cầu | Mô tả |
|---------|-------|
| Hiệu năng | Thời gian phản hồi < 2 giây |
| Bảo mật | Mã hóa mật khẩu, phân quyền chặt chẽ |
| Giao diện | Responsive, tương thích đa thiết bị |
| Dễ sử dụng | Thao tác trực quan, ít bước click |

---

## 1.5. Công nghệ Sử dụng

### 1.5.1. Ngôn ngữ Lập trình

| Công nghệ | Phiên bản | Mục đích |
|-----------|-----------|----------|
| PHP | 8.2 | Xử lý logic server-side |
| JavaScript | ES6+ | Xử lý client-side, AJAX |
| HTML5 | - | Cấu trúc trang web |
| CSS3 | - | Định dạng giao diện |
| SQL | MySQL | Truy vấn cơ sở dữ liệu |

### 1.5.2. Môi trường Phát triển

| Công cụ | Mục đích |
|---------|----------|
| XAMPP | Web server (Apache + MySQL + PHP) |
| phpMyAdmin | Quản lý cơ sở dữ liệu |
| Visual Studio Code | Code editor |
| Git/GitHub | Quản lý phiên bản |
| Chrome DevTools | Debug, test giao diện |

### 1.5.3. Kiến trúc Hệ thống

```
+-------------------+     +-------------------+     +-------------------+
|                   |     |                   |     |                   |
|   TRÌNH DUYỆT     |<--->|    APACHE WEB     |<--->|     MySQL DB      |
|   (Client)        |     |    SERVER (PHP)   |     |  (db_quanlycafe)  |
|                   |     |                   |     |                   |
+-------------------+     +-------------------+     +-------------------+
        |                         |                         |
        v                         v                         v
  HTML/CSS/JS              PHP Scripts               Tables:
  - POS Interface          - login_process.php       - users
  - Admin Panel            - checkout_process.php    - products
  - Ajax Requests          - inventory_import.php    - categories
                           - stats.php               - orders
                           - export_excel.php        - order_details
                                                     - inventory_history
                                                     - shift_reports
```

---

## 1.6. Cấu trúc Thư mục Dự án

```
CongCaPhe/
├── admin/                  # Quản trị Admin
│   ├── dashboard.php       # Trang chủ admin
│   ├── product_*.php       # CRUD sản phẩm
│   ├── user_*.php          # CRUD nhân viên
│   ├── inventory_*.php     # Quản lý kho
│   ├── stats.php           # Thống kê
│   ├── export_excel.php    # Xuất báo cáo
│   ├── shift_history.php   # Lịch sử ca
│   └── uploads/            # Ảnh sản phẩm
│
├── pos/                    # Giao diện bán hàng
│   ├── pos.php             # Màn hình POS chính
│   ├── checkout_process.php# Xử lý thanh toán
│   ├── shift_report.php    # Báo cáo giao ca
│   └── my_orders.php       # Lịch sử đơn hàng
│
├── includes/               # File dùng chung
│   ├── connect.php         # Kết nối DB
│   ├── auth_admin.php      # Xác thực Admin
│   ├── auth_pos.php        # Xác thực POS
│   └── auto_shift_check.php# Kiểm tra ca tự động
│
├── css/                    # File CSS
│   ├── admin_style.css
│   ├── pos_style.css
│   └── header_style.css
│
├── templates/              # Template Excel
│   ├── Layout_*.xls
│   └── Template_*.csv
│
├── index.php               # Trang chủ
├── login.php               # Đăng nhập
├── login_process.php       # Xử lý đăng nhập
└── database.sql            # File backup CSDL
```

---

## Tóm tắt Chương 1

Chương này đã trình bày:
- Tính cần thiết và mục tiêu của hệ thống
- Đối tượng sử dụng (Admin, Thu ngân)
- Phạm vi và các module chính
- Đặc tả yêu cầu chức năng và phi chức năng
- Công nghệ sử dụng và kiến trúc hệ thống
- Cấu trúc thư mục dự án

---

*[Tiếp theo: Chương 2 - Thiết kế Hệ thống]*
