# CHƯƠNG 2: PHÂN TÍCH THIẾT KẾ HỆ THỐNG

---

## 1. Mô tả bài toán

Cửa hàng cần xây dựng hệ thống quản lý bán hàng POS để quản lý hoạt động kinh doanh cà phê. Hệ thống được xây dựng đảm bảo các yêu cầu sau:

**Đối với Thu ngân:**
- Bán hàng tại quầy qua giao diện POS
- Quản lý giỏ hàng, thanh toán
- Nhập hàng vào kho, giao ca
- Xem thống kê, báo cáo

**Đối với Admin:**
- Quản lý nhân viên, sản phẩm, danh mục
- Xem lịch sử giao dịch, lịch sử kho
- Xuất báo cáo Excel

---

## 2. Phân tích yêu cầu

### 2.1. Chức năng Thu ngân
- **Đăng nhập:** Xác thực tài khoản, kiểm tra quyền truy cập
- **Bán hàng POS:** Chọn sản phẩm, thêm vào giỏ, tính tổng tiền, thanh toán
- **Nhập kho:** Cập nhật số lượng tồn kho, cập nhật giá vốn
- **Giao ca:** Đối soát tiền mặt với doanh thu hệ thống
- **Xem thống kê:** Doanh thu ngày, tháng, lợi nhuận

### 2.2. Chức năng Admin
- **Quản lý nhân viên:** CRUD nhân viên, phân quyền, khóa tài khoản
- **Quản lý sản phẩm:** CRUD sản phẩm, upload ảnh, quản lý giá
- **Quản lý danh mục:** CRUD danh mục sản phẩm
- **Xem báo cáo:** Lịch sử đơn hàng, lịch sử kho, lịch sử ca
- **Xuất Excel:** Báo cáo theo ngày, tháng, khoảng thời gian

---

## 3. Phân công Công việc (WBS)

### 3.1. Bảng Phân công Chi tiết

**Bảng 1. Phân công chi tiết 3 thành viên**

| Thành viên | MSSV | Vai trò | Module phụ trách | Kỹ thuật chính |
|------------|------|---------|------------------|----------------|
| Lê Văn Huấn | 97412 | Backend & Admin | Đăng nhập, Phân quyền, CRUD Nhân viên, CRUD Sản phẩm | PHP CRUD, Session, File Upload |
| Vũ Thành An | 98979 | Frontend & POS (NT) | Bán hàng POS, Giỏ hàng, Thanh toán, Đơn hàng | JavaScript, AJAX, Transaction SQL |
| Vũ Công Danh | 96264 | Data & Analytics | Nhập kho, Lịch sử kho, Giao ca, Thống kê, Dashboard | SQL Aggregation, Chart, Export |

### 3.2. Sơ đồ Phân công 3 Người

```
                    +----------------------------------+
                    |        HỆ THỐNG CỘNG CÀ PHÊ     |
                    +----------------------------------+
                                    |
         +--------------------------|---------------------------+
         |                          |                           |
         v                          v                           v
+------------------+      +------------------+      +------------------+
|    NGƯỜI 1       |      |    NGƯỜI 2       |      |    NGƯỜI 3       |
|   LÊ VĂN HUẤN    |      |   VŨ THÀNH AN    |      |   VŨ CÔNG DANH   |
|   (97412)        |      |   (98979 - NT)   |      |   (96264)        |
+------------------+      +------------------+      +------------------+
|                  |      |                  |      |                  |
| [Quản trị Nền    |      | [Bán hàng &      |      | [Kho hàng &      |
|  tảng]           |      |  Giao dịch]      |      |  Báo cáo]        |
|                  |      |                  |      |                  |
| - login.php      |      | - pos.php        |      | - inventory_     |
| - login_process  |      | - checkout_      |      |   import.php     |
| - auth_*.php     |      |   process.php    |      | - inventory_     |
| - user_*.php     |      | - order_list.php |      |   history.php    |
| - product_*.php  |      | - order_details  |      | - shift_report   |
| - Thiết kế DB    |      | - Cart Logic     |      | - shift_history  |
|                  |      |                  |      | - stats.php      |
|                  |      |                  |      | - dashboard.php  |
|                  |      |                  |      | - export_excel   |
+------------------+      +------------------+      +------------------+
         |                          |                           |
         v                          v                           v
    Tạo dữ liệu           Xử lý giao dịch            Tổng hợp & Báo cáo
    (users, products)     (orders, order_details)    (stats, history)
```

### 3.3. Ma trận Truy xuất Dữ liệu

**Bảng 2. Ma trận truy xuất dữ liệu**

Bảng này thể hiện quan hệ giữa Module và Bảng dữ liệu:

| Bảng / Module | Module 1 (Huấn) | Module 2 (An) | Module 3 (Danh) |
|---------------|-----------------|---------------|-----------------|
| **users** | ✅ CRUD | ✅ Read | ✅ Read |
| **categories** | ✅ CRUD | ✅ Read | ✅ Read |
| **products** | ✅ CRUD | ✅ Read/Update | ✅ Update |
| **orders** | ❌ | ✅ Create/Read | ✅ Read |
| **order_details** | ❌ | ✅ Create/Read | ✅ Read |
| **inventory_history** | ❌ | ✅ Create | ✅ Create/Read |
| **shift_reports** | ❌ | ✅ Read | ✅ Create/Read |

**Chú thích:**
- ✅ CRUD: Create, Read, Update, Delete (toàn quyền)
- ✅ Create: Chỉ tạo mới
- ✅ Read: Chỉ đọc
- ✅ Update: Chỉ cập nhật
- ❌ Không truy cập

---

*[Tiếp tục: Phần 2 - Sơ đồ Phân rã Chức năng]*
## 2.3. Biểu đồ Use Case

### 2.3.1. Use Case Tổng quát

```
+------------------------------------------------------------------+
|                         HỆ THỐNG POS                             |
+------------------------------------------------------------------+
|                                                                  |
|     +------------+                        +------------+         |
|     |            |                        |            |         |
|     |   ADMIN    |                        |  THU NGÂN  |         |
|     |   (Huấn)   |                        | (An, Danh) |         |
|     +-----+------+                        +------+-----+         |
|           |                                      |               |
|           |  +-----------------------+           |               |
|           +->| Đăng nhập             |<----------+               |
|           |  +-----------------------+                           |
|           |                                      |               |
|           |  +-----------------------+           |               |
|           +->| Quản lý Nhân viên     |           |               |
|           |  +-----------------------+           |               |
|           |                                      |               |
|           |  +-----------------------+           |               |
|           +->| Quản lý Sản phẩm      |           |               |
|           |  +-----------------------+           |               |
|           |                                      |               |
|           |  +-----------------------+           |               |
|           +->| Quản lý Danh mục      |           |               |
|              +-----------------------+           |               |
|                                                  |               |
|              +-----------------------+           |               |
|              | Bán hàng (POS)        |<----------+               |
|              +-----------------------+           |               |
|                                                  |               |
|              +-----------------------+           |               |
|              | Thanh toán            |<----------+               |
|              +-----------------------+           |               |
|                                                  |               |
|              +-----------------------+           |               |
|              | Nhập hàng             |<----------+               |
|              +-----------------------+           |               |
|                                                  |               |
|              +-----------------------+           |               |
|              | Giao ca               |<----------+               |
|              +-----------------------+           |               |
|                                                  |               |
|           |  +-----------------------+           |               |
|           +->| Xem Thống kê          |<----------+               |
|           |  +-----------------------+                           |
|           |                                                      |
|           |  +-----------------------+                           |
|           +->| Xuất báo cáo Excel    |                           |
|              +-----------------------+                           |
|                                                                  |
+------------------------------------------------------------------+
```

### 2.3.2. Use Case Phân rã - Quản lý Sản phẩm (Người 1)

```
                        +------------------+
                        |      ADMIN       |
                        +--------+---------+
                                 |
                +----------------+----------------+
                |                |                |
                v                v                v
        +-------------+  +-------------+  +-------------+
        | Thêm sản    |  | Sửa sản     |  | Xóa/Ẩn      |
        | phẩm mới    |  | phẩm        |  | sản phẩm    |
        +-------------+  +-------------+  +-------------+
                |                |                |
                v                v                v
        +-------------+  +-------------+  +-------------+
        | - Nhập tên  |  | - Sửa giá   |  | - Xác nhận  |
        | - Chọn DM   |  | - Sửa ảnh   |  | - Cập nhật  |
        | - Nhập giá  |  | - Sửa stock |  |   is_locked |
        | - Upload ảnh|  | - Sửa mô tả |  |             |
        | - Nhập stock|  |             |  |             |
        +-------------+  +-------------+  +-------------+
```

### 2.3.3. Use Case Phân rã - Bán hàng POS (Người 2)

```
                        +------------------+
                        |    THU NGÂN      |
                        +--------+---------+
                                 |
        +------------------------+------------------------+
        |                        |                        |
        v                        v                        v
+---------------+        +---------------+        +---------------+
| Chọn sản phẩm |        | Quản lý       |        | Thanh toán    |
|               |        | giỏ hàng      |        |               |
+---------------+        +---------------+        +---------------+
        |                        |                        |
        v                        v                        v
+---------------+        +---------------+        +---------------+
| - Xem danh    |        | - Thêm món    |        | - Tính tổng   |
|   sách theo   |        | - Sửa số      |        | - Tạo đơn     |
|   danh mục    |        |   lượng       |        | - Trừ kho     |
| - Tìm kiếm    |        | - Xóa món     |        | - Ghi log     |
| - Xem tồn kho |        | - Chọn size/  |        | - In hóa đơn  |
|               |        |   topping     |        |               |
+---------------+        +---------------+        +---------------+
```

### 2.3.4. Use Case Phân rã - Kho hàng & Báo cáo (Người 3)

```
                        +------------------+
                        |    THU NGÂN      |
                        +--------+---------+
                                 |
    +----------------------------+----------------------------+
    |              |             |             |              |
    v              v             v             v              v
+--------+   +----------+   +--------+   +----------+   +--------+
| Nhập   |   | Xem lịch |   | Giao   |   | Xem      |   | Xuất   |
| hàng   |   | sử kho   |   | ca     |   | thống kê |   | Excel  |
+--------+   +----------+   +--------+   +----------+   +--------+
    |              |             |             |              |
    v              v             v             v              v
+--------+   +----------+   +--------+   +----------+   +--------+
|- Chọn  |   |- Lọc     |   |- Nhập  |   |- Doanh   |   |- Theo  |
|  sản   |   |  theo    |   |  tiền  |   |  thu     |   |  ngày  |
|  phẩm  |   |  ngày    |   |  thực  |   |  ngày    |   |- Theo  |
|- Nhập  |   |- Lọc     |   |  tế    |   |- Doanh   |   |  tháng |
|  số    |   |  theo    |   |- Ghi   |   |  thu     |   |- Theo  |
|  lượng |   |  loại    |   |  chú   |   |  tháng   |   |  khoảng|
|- Cập   |   |  (nhập/  |   |  kho   |   |- Lợi     |   |  ngày  |
|  nhật  |   |  xuất)   |   |- So    |   |  nhuận   |   |        |
|  giá   |   |          |   |  sánh  |   |- Món bán |   |        |
|  vốn   |   |          |   |        |   |  chạy    |   |        |
+--------+   +----------+   +--------+   +----------+   +--------+
```

---

## 2.4. Mô tả Chi tiết Use Case

### Bảng UC01: Đăng nhập Hệ thống

| Mục | Nội dung |
|-----|----------|
| **Tên UC** | Đăng nhập |
| **Tác nhân** | Admin, Thu ngân |
| **Mục đích** | Xác thực người dùng để truy cập hệ thống |
| **Tiền điều kiện** | Có tài khoản trong hệ thống, tài khoản không bị khóa |
| **Luồng chính** | 1. Người dùng nhập username và password<br>2. Hệ thống kiểm tra thông tin<br>3. Nếu đúng, chuyển hướng theo vai trò<br>4. Admin → dashboard.php<br>5. Staff → pos.php |
| **Luồng phụ** | 3a. Nếu sai: Hiển thị thông báo lỗi |
| **Hậu điều kiện** | Lưu session user_id, role |

### Bảng UC02: Bán hàng POS

| Mục | Nội dung |
|-----|----------|
| **Tên UC** | Bán hàng POS |
| **Tác nhân** | Thu ngân |
| **Mục đích** | Tạo đơn hàng cho khách |
| **Tiền điều kiện** | Đã đăng nhập, sản phẩm còn tồn kho |
| **Luồng chính** | 1. Chọn sản phẩm từ lưới<br>2. Chọn size, đá, topping (nếu có)<br>3. Thêm vào giỏ hàng<br>4. Nhập số lượng<br>5. Nhấn Thanh toán<br>6. Xác nhận đơn hàng |
| **Luồng phụ** | 4a. Nếu số lượng > tồn kho: Cảnh báo |
| **Hậu điều kiện** | Tạo order + order_details, trừ stock, ghi log xuất kho |

### Bảng UC03: Nhập hàng

| Mục | Nội dung |
|-----|----------|
| **Tên UC** | Nhập hàng nhanh |
| **Tác nhân** | Thu ngân |
| **Mục đích** | Bổ sung số lượng tồn kho |
| **Tiền điều kiện** | Đã đăng nhập |
| **Luồng chính** | 1. Vào trang Nhập hàng<br>2. Chọn sản phẩm cần nhập<br>3. Nhập số lượng và giá vốn mới (nếu có)<br>4. Nhấn Lưu<br>5. Hệ thống cập nhật stock và cost_price<br>6. Ghi log vào inventory_history |
| **Hậu điều kiện** | Tăng stock, cập nhật cost_price, ghi note "Nhập hàng nhanh" |

### Bảng UC04: Giao ca

| Mục | Nội dung |
|-----|----------|
| **Tên UC** | Báo cáo Giao ca |
| **Tác nhân** | Thu ngân |
| **Mục đích** | Kết thúc ca làm việc, đối soát doanh thu |
| **Tiền điều kiện** | Đã đăng nhập, có đơn hàng trong ca |
| **Luồng chính** | 1. Vào trang Giao ca<br>2. Hệ thống tự động tính doanh thu ca<br>3. Nhập tiền mặt thực tế<br>4. Hệ thống tính chênh lệch<br>5. Nhập ghi chú kho, ghi chú chung<br>6. Nhấn Chốt ca<br>7. Lưu vào shift_reports |
| **Hậu điều kiện** | Tạo bản ghi shift_reports với thông tin ca |

---

## Tóm tắt Chương 2

Chương này đã trình bày:
- Phân công công việc chi tiết cho 3 thành viên
- Sơ đồ phối hợp giữa các module
- Thiết kế ERD với 7 bảng chính
- Mô tả chi tiết các bảng dữ liệu
- 4 Use Case tổng quát và phân rã
- Mô tả chi tiết các Use Case quan trọng
## 6. Thi?t k? Co s? D? li?u

### 6.1. M� h�nh ERD (Entity Relationship Diagram)

```
+-------------+       +----------------+       +-------------+
|   users     |       |    orders      |       | order_      |
+-------------+       +----------------+       | details     |
| PK id       |<---+  | PK id          |<--+   +-------------+
| username    |    |  | FK user_id ----+   |   | PK id       |
| password    |    |  | order_date     |   +---| FK order_id |
| full_name   |    |  | total_amount   |       | FK product_ |
| phone       |    |  | status         |       |    id       |
| role        |    |  +----------------+       | quantity    |
| is_locked   |    |                           | price       |
| created_at  |    |                           | note        |
+-------------+    |                           +-------------+
                   |                                  |
+-------------+    |    +-------------+               |
| categories  |    |    |  products   |<--------------+
+-------------+    |    +-------------+
| PK id       |    |    | PK id       |
| name        |    |    | FK category |
+-------------+    |    |    _id      |
      |            |    | name        |
      +------------|----| price       |
                   |    | cost_price  |
                   |    | stock       |
                   |    | image       |
                   |    +-------------+
                   |           |
                   |           v
                   |    +----------------+
                   |    | inventory_     |
                   |    | history        |
                   |    +----------------+
                   |    | PK id          |
                   |    | FK product_id  |
                   |    | quantity       |
                   |    | created_at     |
                   |    | note           |
                   |    +----------------+
                   |
                   |    +----------------+
                   +--->| shift_reports  |
                        +----------------+
                        | PK id          |
                        | FK user_id     |
                        | shift_type     |
                        | system_revenue |
                        | actual_cash    |
                        | difference     |
                        | created_at     |
                        +----------------+
```

### 6.2. M� t? Chi ti?t C�c B?ng

#### B?ng 9. M� t? b?ng users (Ngu?i d�ng)

| STT | Thu?c t�nh | Ki?u d? li?u | � nghia | R�ng bu?c |
|-----|------------|--------------|---------|-----------|
| 1 | id | INT(11) | M� ngu?i d�ng | PRIMARY KEY |
| 2 | username | VARCHAR(50) | T�n dang nh?p | UNIQUE, NOT NULL |
| 3 | password | VARCHAR(255) | M?t kh?u (MD5) | NOT NULL |
| 4 | full_name | VARCHAR(100) | H? v� t�n | NOT NULL |
| 5 | phone | VARCHAR(15) | S? di?n tho?i | |
| 6 | role | ENUM | Vai tr� | 'admin', 'staff' |
| 7 | is_locked | TINYINT(1) | Tr?ng th�i kh�a | DEFAULT 0 |
| 8 | created_at | TIMESTAMP | Ng�y t?o | |

#### B?ng 10. M� t? b?ng products (S?n ph?m)

| STT | Thu?c t�nh | Ki?u d? li?u | � nghia | R�ng bu?c |
|-----|------------|--------------|---------|-----------|
| 1 | id | INT(11) | M� s?n ph?m | PRIMARY KEY |
| 2 | category_id | INT(11) | M� danh m?c | FOREIGN KEY |
| 3 | name | VARCHAR(255) | T�n s?n ph?m | NOT NULL |
| 4 | price | INT(11) | Gi� b�n | NOT NULL |
| 5 | cost_price | INT(11) | Gi� v?n | DEFAULT 0 |
| 6 | stock | INT(11) | S? lu?ng t?n | NOT NULL |
| 7 | image | VARCHAR(255) | �u?ng d?n ?nh | |
| 8 | is_locked | TINYINT(1) | ?n s?n ph?m | DEFAULT 0 |

#### B?ng 11. M� t? b?ng categories (Danh m?c)

| STT | Thu?c t�nh | Ki?u d? li?u | � nghia | R�ng bu?c |
|-----|------------|--------------|---------|-----------|
| 1 | id | INT(11) | M� danh m?c | PRIMARY KEY |
| 2 | name | VARCHAR(50) | T�n danh m?c | NOT NULL |

#### B?ng 12. M� t? b?ng orders (�on h�ng)

| STT | Thu?c t�nh | Ki?u d? li?u | � nghia | R�ng bu?c |
|-----|------------|--------------|---------|-----------|
| 1 | id | INT(11) | M� don h�ng | PRIMARY KEY |
| 2 | user_id | INT(11) | Nh�n vi�n t?o don | FOREIGN KEY |
| 3 | order_date | DATETIME | Th?i gian t?o | |
| 4 | total_amount | INT(11) | T?ng ti?n | NOT NULL |
| 5 | status | VARCHAR(50) | Tr?ng th�i | |

#### B?ng 13. M� t? b?ng order_details (Chi ti?t don)

| STT | Thu?c t�nh | Ki?u d? li?u | � nghia | R�ng bu?c |
|-----|------------|--------------|---------|-----------|
| 1 | id | INT(11) | M� chi ti?t | PRIMARY KEY |
| 2 | order_id | INT(11) | M� don h�ng | FOREIGN KEY |
| 3 | product_id | INT(11) | M� s?n ph?m | FOREIGN KEY |
| 4 | quantity | INT(11) | S? lu?ng | NOT NULL |
| 5 | price | INT(11) | Gi� t?i th?i di?m b�n | NOT NULL |

#### B?ng 14. M� t? b?ng inventory_history (L?ch s? kho)

| STT | Thu?c t�nh | Ki?u d? li?u | � nghia | R�ng bu?c |
|-----|------------|--------------|---------|-----------|
| 1 | id | INT(11) | M� giao d?ch | PRIMARY KEY |
| 2 | product_id | INT(11) | M� s?n ph?m | FOREIGN KEY |
| 3 | quantity | INT(11) | S? lu?ng (+/-) | NOT NULL |
| 4 | created_at | TIMESTAMP | Th?i gian | |
| 5 | note | TEXT | Ghi ch� | |

#### B?ng 15. M� t? b?ng shift_reports (B�o c�o ca)

| STT | Thu?c t�nh | Ki?u d? li?u | � nghia | R�ng bu?c |
|-----|------------|--------------|---------|-----------|
| 1 | id | INT(11) | M� b�o c�o | PRIMARY KEY |
| 2 | user_id | INT(11) | Nh�n vi�n | FOREIGN KEY |
| 3 | shift_type | VARCHAR(20) | Lo ca | S�ng/Chi?u/T?i |
| 4 | system_revenue | INT(11) | Doanh thu h? th?ng | |
| 5 | actual_cash | INT(11) | Ti?n m?t th?c t? | |
| 6 | difference | INT(11) | Ch�nh l?ch | |
| 7 | created_at | TIMESTAMP | Th?i gian ch?t ca | |

---

*[Ti?p theo: Chuong 3 - Quy tr�nh & Giao di?n, Chuong 4 - K?t lu?n]*
