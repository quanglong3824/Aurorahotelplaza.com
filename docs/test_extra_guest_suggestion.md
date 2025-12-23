# Test Cases: Thuật toán gợi ý phụ thu khách thêm

## Mô tả thuật toán

### Quy tắc phụ thu
| Chiều cao | Phí/đêm | Ghi chú |
|-----------|---------|---------|
| Dưới 1m | Miễn phí | Bao gồm ăn sáng |
| 1m - dưới 1m3 | 200.000đ/đêm | Bao gồm ăn sáng |
| Từ 1m3 trở lên | 400.000đ/đêm | Bao gồm ăn sáng |

### Giường phụ
- Phí: 650.000đ/đêm
- Chỉ áp dụng cho phòng (không áp dụng cho căn hộ)
- Tối đa 2 giường phụ/phòng

---

## Test Cases

### TC01: 2 người lớn + 1 trẻ em (Case A)
**Input:**
- Loại phòng: Deluxe (max_adults=2, max_children=1, max_occupancy=2)
- Số người lớn: 2
- Số trẻ em: 1
- Khách thêm đã khai báo: 0

**Expected:**
- Hiển thị gợi ý: "Bạn có 1 trẻ em đi cùng. Vui lòng khai báo chiều cao để tính phụ thu (nếu có)."
- Button: "Khai báo chiều cao"

**Action:** Click "Khai báo chiều cao"
- Mở form thêm khách
- Tự động thêm 1 entry cho trẻ em

---

### TC02: 2 người lớn + 2 trẻ em (Case B)
**Input:**
- Loại phòng: Premium Deluxe (max_adults=2, max_children=1, max_occupancy=2)
- Số người lớn: 2
- Số trẻ em: 2
- Khách thêm đã khai báo: 0

**Expected:**
- Hiển thị gợi ý (warning): "Bạn có 2 trẻ em đi cùng 2 người lớn. Vui lòng khai báo chiều cao trẻ em để tính phụ thu chính xác. Bạn cũng có thể cần thêm giường phụ."
- Button 1: "Khai báo 2 trẻ em"
- Button 2: "Thêm giường phụ"

**Action:** Click "Khai báo 2 trẻ em"
- Mở form thêm khách
- Tự động thêm 2 entries

---

### TC03: Vượt quá sức chứa (Case C)
**Input:**
- Loại phòng: Deluxe (max_occupancy=2)
- Số người lớn: 2
- Số trẻ em: 2
- Tổng: 4 người > max_occupancy (2)
- Khách thêm đã khai báo: 0

**Expected:**
- Hiển thị gợi ý (warning): "Số khách (4 người) vượt quá sức chứa tiêu chuẩn của phòng (2 người). Vui lòng khai báo 2 khách thêm để tính phụ thu."
- Button: "Khai báo 2 khách thêm"

---

### TC04: Có trẻ em nhưng chưa khai báo (Case D - gợi ý nhẹ)
**Input:**
- Loại phòng: Family Apartment (max_adults=4, max_children=3, max_occupancy=5)
- Số người lớn: 2
- Số trẻ em: 1
- Khách thêm đã khai báo: 0

**Expected:**
- Hiển thị gợi ý (hint): "Lưu ý: Nếu trẻ em cao từ 1m trở lên sẽ có phụ thu. Bạn có muốn khai báo chiều cao không?"
- Button 1: "Khai báo ngay"
- Button 2: "Bỏ qua"

---

### TC05: Đã khai báo đủ - không hiện gợi ý
**Input:**
- Loại phòng: Deluxe (max_adults=2, max_children=1)
- Số người lớn: 2
- Số trẻ em: 1
- Khách thêm đã khai báo: 1 (chiều cao 1.2m)

**Expected:**
- KHÔNG hiển thị gợi ý
- Phí phụ thu: 200.000đ/đêm × số đêm

---

### TC06: Căn hộ - không gợi ý giường phụ
**Input:**
- Loại phòng: Studio Apartment (category=apartment)
- Số người lớn: 2
- Số trẻ em: 2
- Khách thêm đã khai báo: 0

**Expected:**
- Hiển thị gợi ý: "Bạn có 2 trẻ em đi cùng 2 người lớn. Vui lòng khai báo chiều cao trẻ em để tính phụ thu chính xác."
- Button: "Khai báo 2 trẻ em"
- KHÔNG có button "Thêm giường phụ" (vì là căn hộ)

---

### TC07: Dismiss gợi ý
**Input:**
- Bất kỳ case nào hiển thị gợi ý

**Action:** Click "Bỏ qua" hoặc nút X

**Expected:**
- Ẩn gợi ý
- Không hiển thị lại cho đến khi user thay đổi số người

---

### TC08: Tính tiền đầy đủ
**Input:**
- Loại phòng: Deluxe (giá 1.600.000đ/đêm)
- Số đêm: 2
- Số người lớn: 2
- Số trẻ em: 2
- Khách thêm khai báo:
  - Trẻ 1: 0.9m (dưới 1m) → Miễn phí
  - Trẻ 2: 1.2m (1m-1m3) → 200.000đ/đêm
- Giường phụ: 1

**Expected Calculation:**
```
Tiền phòng:     1.600.000 × 2 đêm = 3.200.000đ
Phụ thu trẻ 1:  0 × 2 đêm         = 0đ
Phụ thu trẻ 2:  200.000 × 2 đêm   = 400.000đ
Giường phụ:     650.000 × 2 đêm   = 1.300.000đ
─────────────────────────────────────────────
TỔNG CỘNG:                        = 4.900.000đ
```

---

## Checklist kiểm tra

- [ ] TC01: Gợi ý hiển thị đúng cho 2 lớn + 1 nhỏ
- [ ] TC02: Gợi ý hiển thị đúng cho 2 lớn + 2 nhỏ (có gợi ý giường)
- [ ] TC03: Gợi ý hiển thị đúng khi vượt sức chứa
- [ ] TC04: Gợi ý nhẹ hiển thị đúng
- [ ] TC05: Không hiện gợi ý khi đã khai báo đủ
- [ ] TC06: Căn hộ không gợi ý giường phụ
- [ ] TC07: Dismiss hoạt động đúng
- [ ] TC08: Tính tiền chính xác

---

## Lưu ý triển khai

1. **Frontend (booking.js):**
   - Gọi `checkAndShowSuggestion()` khi thay đổi số người
   - Reset `suggestionDismissed` khi user thay đổi input

2. **Backend (create_booking.php):**
   - Luôn tính lại phí phụ thu từ `extra_guests_data`
   - Không tin tưởng giá trị từ frontend

3. **Database:**
   - Lưu `extra_guest_fee`, `extra_bed_fee` vào bảng bookings
   - Có thể lưu chi tiết từng khách vào `booking_extra_guests` (nếu cần)
