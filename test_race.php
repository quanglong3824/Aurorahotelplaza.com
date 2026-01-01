<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Race Condition Tester</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            line-height: 1.6;
        }

        .log-box {
            background: #f4f4f4;
            padding: 15px;
            border: 1px solid #ddd;
            height: 300px;
            overflow-y: auto;
            margin-top: 10px;
            font-family: monospace;
        }

        .success {
            color: green;
            font-weight: bold;
        }

        .fail {
            color: red;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <h1>🏎️ Race Condition Tester</h1>
    <p>Bài toán: Chỉ còn <strong>1 phòng cuối cùng</strong>.</p>
    <p>Thử thách: Bắn <strong>5 request</strong> cùng lúc. Nếu có > 1 request thành công => <strong>LỖI
            (Overbooking)</strong>.</p>

    <div style="margin-bottom: 20px;">
        <label>Chọn loại phòng để test:</label>
        <select id="room_type_select">
            <option value="">Đang tải...</option>
        </select>
    </div>

    <button onclick="startRace()" id="startBtn">🚀 BẮN 5 REQUEST CÙNG LÚC</button>
    <button onclick="resetData()" style="background:#ddd">Reset Test Data</button>

    <div class="log-box" id="log"></div>

    <script>
        // Load room types
        fetch('booking/api/get_room_types.php?category=room') // Giả sử có API này hoặc tạo tạm query
            .then(r => r.json())
            .then(data => {
                const sel = document.getElementById('room_type_select');
                sel.innerHTML = '';
                // Hardcode logic lấy room types nếu API chưa chuẩn
                // Ở đây ta gọi file seed_data.php mode lấy info cho lẹ, hoặc hardcode ID để test
                sel.innerHTML = '<option value="1">Deluxe (Room ID: 1)</option>';
            });

        function log(msg, type = '') {
            const el = document.getElementById('log');
            el.innerHTML += `<div class="${type}">[${new Date().toLocaleTimeString()}] ${msg}</div>`;
            el.scrollTop = el.scrollHeight;
        }

        async function startRace() {
            const roomTypeId = 1; // Test cứng loại phòng ID 1 (Deluxe)
            const checkIn = '2026-05-01'; // Ngày xa lắc để tránh trùng data cũ
            const checkOut = '2026-05-02';

            log(`-----------------------------------`);
            log(`🏁 Bắt đầu Race: 5 threads giành nhau ngày ${checkIn}`);

            const threads = [];
            const numThreads = 5;

            document.getElementById('startBtn').disabled = true;

            // Tạo 5 request song song
            for (let i = 0; i < numThreads; i++) {
                const payload = new FormData();
                payload.append('room_type_id', roomTypeId);
                payload.append('check_in_date', checkIn);
                payload.append('check_out_date', checkOut);
                payload.append('num_adults', 1);
                payload.append('num_children', 0);
                payload.append('booking_type', 'instant');
                payload.append('guest_name', 'Racer ' + (i + 1));
                payload.append('guest_email', `racer${i + 1}@test.com`);
                payload.append('guest_phone', '0999999999');
                payload.append('payment_method', 'cash'); // Skip payment

                // Gọi API thật
                const req = fetch('booking/api/create_booking.php', {
                    method: 'POST',
                    body: payload
                }).then(r => r.json()).then(res => {
                    return { id: i + 1, result: res };
                });

                threads.push(req);
            }

            // Chờ tất cả trả về
            const results = await Promise.all(threads);

            let successCount = 0;
            results.forEach(r => {
                if (r.result.success) {
                    log(`✅ Thread ${r.id}: THÀNH CÔNG (Mã: ${r.result.booking_code})`, 'success');
                    successCount++;
                } else {
                    log(`❌ Thread ${r.id}: THẤT BẠI (${r.result.message})`, 'fail');
                }
            });

            if (successCount > 1) {
                log(`🚨 LỖI NGHIÊM TRỌNG: Đã bán được ${successCount} phòng trong khi kho chỉ nên bán 1!`, 'fail');
                log(`=> Hệ thống bị Race Condition.`);
            } else if (successCount === 1) {
                log(`🏆 TUYỆT VỜI: Chỉ có 1 người mua được. Hệ thống an toàn!`, 'success');
            } else {
                log(`⚠️ KỲ LẠ: Không ai mua được? (Có thể hết phòng từ trước)`, 'fail');
            }

            document.getElementById('startBtn').disabled = false;
        }

        function resetData() {
            // Logic clear booking ngày test
            log('Vui lòng vào xóa booking ngày 2026-05-01 thủ công để test lại.');
        }
    </script>
</body>

</html>