# Đánh giá Hệ thống & Kế hoạch Cải tiến

> **Ngày đánh giá:** 22/12/2024
> **Người đánh giá:** Danh (Người 3)
> **Phạm vi:** Module Kho hàng, Thống kê, POS

---

## Phần 1: So sánh với Tiêu chuẩn Ngành

### Tính năng Chuẩn POS Quán Cà Phê (Theo nghiên cứu)

| Tính năng | Hệ thống Hiện tại | Trạng thái |
|-----------|-------------------|------------|
| Real-time Inventory Tracking | Có | ✅ |
| Low-stock Alerts | Có (cảnh báo ≤5) | ✅ |
| Recipe/Ingredient Management | Chưa có | ❌ |
| Automated Reordering | Chưa có | ❌ |
| Supplier Management | Chưa có | ❌ |
| Purchase Order Tracking | Chưa có | ❌ |
| Waste Tracking | Chưa có | ❌ |
| Expiration Date Tracking | Chưa có | ❌ |
| Barcode Scanning | Chưa có | ❌ |
| Multi-location Support | Chưa có | ❌ |
| Sales Analytics | Có cơ bản | ⚠️ |
| Cost of Goods Sold (COGS) | Mới thêm cost_price | ⚠️ |
| Online Ordering Integration | Chưa có | ❌ |
| Staff Performance Tracking | Chưa có | ❌ |

---

## Phần 2: Các Vấn đề Hiện tại

### A. Lỗi Logic Nghiệp vụ

#### 1. Không kiểm tra Stock khi Checkout
**Mức độ:** 🔴 NGHIÊM TRỌNG
**File:** `pos/checkout_process.php`
**Vấn đề:** 
- Chỉ kiểm tra khi thêm vào giỏ (đã fix)
- CHƯA kiểm tra lại khi checkout
- Có thể xảy ra race condition (2 người đặt cùng lúc)

**Giải pháp đề xuất:**
```php
// Trong checkout_process.php
foreach ($cart as $item) {
    $stock = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT stock FROM products WHERE id = {$item['id']}"
    ))['stock'];
    
    if ($item['quantity'] > $stock) {
        echo json_encode(['success' => false, 
            'message' => "Sản phẩm {$item['name']} chỉ còn $stock!"]);
        exit;
    }
}
```

---

#### 2. Không có Đơn vị Tính (Unit)
**Mức độ:** 🟡 TRUNG BÌNH
**Bảng:** `products`, `inventory_history`
**Vấn đề:**
- Không phân biệt được "1 ly" vs "1 kg" vs "1 gói"
- Khó quản lý nguyên liệu thô (cà phê, sữa, đường)

**Giải pháp đề xuất:**
```sql
ALTER TABLE products ADD COLUMN unit VARCHAR(20) DEFAULT 'ly';
-- VD: ly, kg, gói, chai, lon
```

---

#### 3. Lịch sử Kho thiếu Thông tin
**Mức độ:** 🟡 TRUNG BÌNH
**Bảng:** `inventory_history`
**Vấn đề:**
- Không ghi ai thực hiện (user_id)
- Không ghi số dư trước/sau
- Chỉ có `note` dạng text

**Giải pháp đề xuất:**
```sql
ALTER TABLE inventory_history 
ADD COLUMN user_id INT,
ADD COLUMN balance_before INT,
ADD COLUMN balance_after INT,
ADD COLUMN action_type ENUM('import', 'export', 'adjust', 'sale');
```

---

#### 4. Giá Topping Cứng trong Code
**Mức độ:** 🟡 TRUNG BÌNH
**File:** `pos/pos.php` (dòng 172-177)
**Vấn đề:**
- Giá topping hardcode: 5000, 7000, 10000...
- Không thể thay đổi từ admin

**Giải pháp đề xuất:**
- Tạo bảng `toppings` riêng
- Load topping từ database

---

### B. Thiếu Tính năng Quan trọng

#### 5. Không có Quản lý Nhà cung cấp
**Mức độ:** 🔴 CAO
**Vấn đề:**
- Nhập hàng không biết từ NCC nào
- Không theo dõi được nợ/thanh toán
- Không so sánh giá giữa các NCC

**Cần tạo:**
- Bảng `suppliers` (id, name, phone, address, debt)
- Bảng `purchase_orders` (id, supplier_id, date, total, status)
- Bảng `purchase_order_details` (product_id, qty, price)
- File `admin/supplier_*.php`
- File `admin/purchase_order_*.php`

---

#### 6. Không có Báo cáo Công nợ
**Mức độ:** 🔴 CAO
**Vấn đề:**
- Nợ NCC bao nhiêu? Không biết
- Đã thanh toán chưa? Không biết

---

#### 7. Export Excel Giới hạn
**Mức độ:** 🟡 TRUNG BÌNH
**File:** `admin/export_excel.php`
**Vấn đề:**
- Chỉ xuất được 1 ngày hoặc 1 tháng
- Không xuất được khoảng ngày tùy chọn
- Template đơn giản (chỉ là HTML table)

**Giải pháp:**
- Thêm date range picker
- Dùng PhpSpreadsheet cho template đẹp

---

#### 8. Không có Phiếu In
**Mức độ:** 🟢 THẤP
**Vấn đề:**
- Không có hóa đơn in nhiệt
- Không có phiếu nhập kho

---

### C. Vấn đề Bảo mật

#### 9. SQL Injection Risk
**Mức độ:** 🔴 NGHIÊM TRỌNG
**Nhiều file:** Sử dụng biến trực tiếp trong SQL
**Ví dụ tệ:**
```php
$sql = "SELECT * FROM products WHERE id = $id";
```
**Nên sửa:**
```php
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
```

---

#### 10. Không Validate Server-side đầy đủ
**Mức độ:** 🟡 TRUNG BÌNH
**Vấn đề:**
- Chủ yếu validate ở JavaScript (client)
- Có thể bypass bằng cách sửa request

---

### D. Vấn đề UI/UX

#### 11. Thiếu Loading State
**Mức độ:** 🟢 THẤP
**Vấn đề:**
- Không có spinner khi đang xử lý
- User không biết đang chờ

---

#### 12. Thiếu Confirm Dialog
**Mức độ:** 🟢 THẤP
**Vấn đề:**
- Xóa sản phẩm chỉ có `confirm()` cơ bản
- Không có modal đẹp/rõ ràng

---

## Phần 3: Roadmap Cải tiến

### Priority 1: Sửa Lỗi Nghiêm trọng (1-2 tuần)

| Task | File | Effort |
|------|------|--------|
| Kiểm tra stock khi checkout | checkout_process.php | 2h |
| Prepared statements (SQL Injection) | Nhiều file | 1 ngày |
| Server-side validation | Nhiều file | 4h |

### Priority 2: Tính năng Thiếu (2-3 tuần)

| Task | Files | Effort |
|------|-------|--------|
| Bảng suppliers + CRUD | New files | 2 ngày |
| Bảng purchase_orders + CRUD | New files | 3 ngày |
| Export Excel date range | export_excel.php | 4h |
| Toppings từ database | pos.php, new table | 4h |

### Priority 3: Cải thiện (1-2 tuần)

| Task | Files | Effort |
|------|-------|--------|
| Thêm unit cho products | products table | 2h |
| Inventory history chi tiết | table + files | 3h |
| Loading states | CSS + JS | 2h |
| Better confirm dialogs | UI components | 2h |

### Priority 4: Tính năng Nâng cao (Tương lai)

| Task | Mô tả |
|------|-------|
| Recipe Management | Quản lý nguyên liệu theo công thức |
| Waste Tracking | Theo dõi hàng hỏng/hết hạn |
| Barcode Scanning | Quét mã khi nhập kho |
| Staff Performance | Thống kê theo nhân viên |
| Multi-location | Hỗ trợ nhiều chi nhánh |

---

## Phần 4: Kế hoạch Hành động

### Tuần này (23-28/12)
- [ ] Sửa checkout_process.php - check stock
- [ ] Tạo Pull Request Phase 1
- [ ] Đợi An review + merge

### Tuần sau (nếu có thời gian)
- [ ] Tạo bảng suppliers
- [ ] Làm CRUD suppliers
- [ ] Tạo bảng purchase_orders
- [ ] Làm form nhập hàng có NCC

---

## Kết luận

**Điểm mạnh:**
- ✅ Giao diện POS đẹp, dễ dùng
- ✅ Thống kê cơ bản đầy đủ (Chart.js)
- ✅ Phân quyền rõ ràng (admin/pos)
- ✅ Quản lý ca làm việc

**Điểm yếu:**
- ❌ Thiếu quản lý nhà cung cấp
- ❌ Thiếu phiếu nhập kho
- ❌ SQL không an toàn
- ❌ Export Excel hạn chế

**Đề xuất:** Tập trung vào Priority 1-2 trước, các tính năng nâng cao để sau khi bảo vệ xong.
