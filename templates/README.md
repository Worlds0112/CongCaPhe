# Huong Dan Su Dung Template Nhap Lieu

## Gioi thieu
Cac file template nay giup nhap du lieu vao he thong Quan Ly Quan Ca Phe.
Mo file CSV bang Excel, dien du lieu theo mau, roi import vao database.

---

## Danh sach Template

### 1. Template_SanPham.csv
Dung de nhap san pham moi vao he thong.

| Cot | Mo ta | Bat buoc |
|-----|-------|----------|
| category_id | ID danh muc (1-9, xem Template_DanhMuc.csv) | Co |
| name | Ten san pham | Co |
| price | Gia ban (VND, khong dau cham) | Co |
| cost_price | Gia von (VND) | Co |
| stock | So luong ton kho | Co |
| description | Mo ta san pham | Khong |

**Vi du:**
```
3,Bac xiu,45000,10500,20,Ca phe sua truyen thong
```

---

### 2. Template_NhapKho.csv
Dung de nhap them so luong vao kho.

| Cot | Mo ta |
|-----|-------|
| product_id | ID san pham (lay tu he thong) |
| product_name | Ten san pham (de tham khao) |
| quantity | So luong nhap (+) hoac xuat (-) |
| note | Ghi chu |

**Vi du:**
```
7,Bac xiu,25,Nhap hang tu NCC ABC
```

---

### 3. Template_DanhMuc.csv
Bang tra cuu danh muc san pham.

| ID | Ten Danh Muc |
|----|--------------|
| 1 | Che doi moi |
| 2 | Dac san tai Cong |
| 3 | Ca phe Viet Nam |
| 4 | Tra shan tuyet |
| 5 | Do uong dia phuong |
| 6 | Trai cay - Tuoi tre |
| 7 | Sua chua tuyet |
| 8 | Topping |
| 9 | Do an choi |

---

## Cach su dung

### Buoc 1: Mo file CSV bang Excel
1. Mo Excel
2. File > Open > Chon file .csv
3. Chon "Delimited" > Next
4. Tick "Comma" > Finish

### Buoc 2: Dien du lieu
- Xoa dong mau (giu dong tieu de)
- Dien du lieu theo cot
- Gia tien: Khong dung dau cham, chi so (VD: 45000)

### Buoc 3: Luu file
- File > Save As
- Chon dinh dang: CSV UTF-8
- Dat ten file thich hop

### Buoc 4: Import vao he thong
- Su dung chuc nang Import trong Admin Panel
- Hoac chay script PHP import

---

## Luu y
- Khong xoa dong tieu de (dong dau tien)
- Khong de trong cac cot bat buoc
- Dung dau phay (,) phan cach cot
- Gia tien la so nguyen, khong co dau cham

---

## Lien he ho tro
- Nguoi 1 (An): Quan ly tong the
- Nguoi 3 (Danh): Kho hang & Thong ke

