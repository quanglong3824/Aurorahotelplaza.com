# 🗑️ _trash — Các file không còn cần thiết

> Thư mục này chứa các file debug, script migration đã dùng, và file tiện ích phát triển không cần thiết trên Production.
> **Không được include hoặc require bất kỳ file nào trong thư mục này.**
> Có thể xóa hoàn toàn thư mục này bất cứ lúc nào.

---

## root/ — File từ thư mục gốc

| File | Lý do gom vào trash |
|------|---------------------|
| `check_ai_config.php` | Debug script kiểm tra cấu hình AI |
| `check_ai_logs.php` | Debug script kiểm tra logs AI |
| `check_untranslated_en.php` | Công cụ dev kiểm tra bản dịch |
| `compare_langs.php` | Công cụ dev so sánh ngôn ngữ |
| `debug_env.php` | Debug script kiểm tra biến môi trường |
| `debug_paths.php` | Debug script kiểm tra đường dẫn |
| `list_models.php` | Dev utility liệt kê AI models |
| `apply_notranslate.js` | Script một lần dùng |
| `apply_notranslate.php` | Script một lần dùng |
| `update-chat-db.php` | Script migration DB đã hoàn thành |
| `update-chat-messages-db.php` | Script migration DB đã hoàn thành |
| `replace-tailwind.bat` | Windows batch script (dự án Mac/Linux) |
| `postcss.config.js` | Không dùng (không có build pipeline) |
| `package.json` | Không dùng (không có Node build) |
| `tailwind.config.js` | Không dùng (CSS là Vanilla) |
| `404.html` | Trùng chức năng với `404.php` |

## docs/ — File từ thư mục docs

| File | Lý do gom vào trash |
|------|---------------------|
| `backup_2026-03-06_13-25-50.sql` | SQL backup cũ (nên lưu ngoài repo) |
| `system_health_check.py` | Python script dev, không dùng trên prod |
| `yeu-cau-.txt` | File ghi chú nội bộ |
| `Thống kê lỗi & hướng khắc phục.txt` | File ghi chú nội bộ cho AI |

---
*Gom lại ngày: 2026-04-02*
