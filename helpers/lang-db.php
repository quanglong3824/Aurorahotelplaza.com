<?php
/**
 * lang-db.php - Helper để lấy text từ database theo ngôn ngữ hiện tại
 * Aurora Hotel Plaza — Bilingual DB Text System
 */

/**
 * Lấy text từ $row theo ngôn ngữ hiện tại.
 * Nếu lang = 'en' và có cột $field_en không rỗng → trả về EN
 * Ngược lại → trả về VI (mặc định)
 *
 * @param array  $row    Mảng dữ liệu từ DB (kết quả fetch)
 * @param string $field  Tên cột gốc (ví dụ: 'description', 'type_name')
 * @param string|null $lang Ngôn ngữ cụ thể, nếu null thì đọc từ session
 * @return string
 *
 * Ví dụ:
 *   echo db_text($room, 'description');           // tự động theo session lang
 *   echo db_text($room, 'type_name', 'en');       // ép EN
 *   echo db_text($faq, 'answer');                 // FAQ answer
 */
function db_text(array $row, string $field, ?string $lang = null): string
{
    if ($lang === null) {
        $lang = $_SESSION['lang'] ?? 'vi';
    }

    $en_field = $field . '_en';

    if ($lang === 'en' && array_key_exists($en_field, $row) && !empty(trim((string) ($row[$en_field] ?? '')))) {
        return (string) $row[$en_field];
    }

    return (string) ($row[$field] ?? '');
}

/**
 * Lấy tên cột phù hợp với ngôn ngữ để dùng trong SQL SELECT/ORDER BY.
 * Nếu cột _en tồn tại (không thể biết trước), dùng hàm này cho SELECT.
 *
 * @param string $field Tên cột gốc
 * @param string|null $lang
 * @return string Tên cột để dùng trong SQL
 *
 * Ví dụ:
 *   $col = db_col('description');   // trả 'description' hoặc 'description_en'
 *   $sql = "SELECT $col FROM room_types";
 */
function db_col(string $field, ?string $lang = null): string
{
    if ($lang === null) {
        $lang = $_SESSION['lang'] ?? 'vi';
    }
    return $lang === 'en' ? $field . '_en' : $field;
}

/**
 * Lấy text với fallback: nếu _en rỗng, dùng VI.
 * Alias của db_text() — cùng logic, tên rõ hơn.
 */
function db_lang(array $row, string $field, ?string $lang = null): string
{
    return db_text($row, $field, $lang);
}

/**
 * Xuất echo trực tiếp (tiện dùng trong template)
 *
 * Ví dụ:
 *   db_echo($room, 'type_name');
 *   db_echo($faq, 'question');
 */
function db_echo(array $row, string $field, ?string $lang = null): void
{
    echo htmlspecialchars(db_text($row, $field, $lang), ENT_QUOTES, 'UTF-8');
}

/**
 * Xuất HTML nguyên bản (khi field chứa HTML, không escape)
 */
function db_html(array $row, string $field, ?string $lang = null): void
{
    echo db_text($row, $field, $lang);
}
