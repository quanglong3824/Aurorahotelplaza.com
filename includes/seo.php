<?php
/**
 * SEO Helper - Quản lý Meta Tags cho website
 */

function get_meta_description() {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    
    $descriptions = [
        'index' => 'Khách sạn Aurora Hotel Plaza - Khách sạn 4 sao hàng đầu tại Biên Hòa, Đồng Nai. Cung cấp dịch vụ phòng nghỉ cao cấp, căn hộ Indochine sang trọng và trung tâm hội nghị tiệc cưới chuyên nghiệp.',
        'rooms' => 'Khám phá các loại phòng nghỉ cao cấp tại Aurora Hotel Plaza: Deluxe, Premium Deluxe, Suite với đầy đủ tiện nghi hiện đại và tầm nhìn tuyệt đẹp ra thành phố Biên Hòa.',
        'apartments' => 'Căn hộ Indochine và Modern Studio cho thuê dài hạn tại Aurora Hotel Plaza. Không gian sống đẳng cấp, đầy đủ tiện nghi bếp, máy giặt, phục vụ chuyên nghiệp như khách sạn.',
        'services' => 'Dịch vụ đẳng cấp tại Aurora Hotel Plaza: Nhà hàng cao cấp, hồ bơi, phòng Gym, Massage trị liệu, trung tâm hội nghị tiệc cưới và cho thuê văn phòng chuyên nghiệp.',
        'about' => 'Tìm hiểu về lịch sử và sứ mệnh của Aurora Hotel Plaza - biểu tượng nghỉ dưỡng sang trọng tại cửa ngõ TP. Biên Hòa với hơn 200 phòng nghỉ và căn hộ cao cấp.',
        'contact' => 'Liên hệ Aurora Hotel Plaza Biên Hòa. Địa chỉ: 253 Phạm Văn Thuận, P. Tân Mai, TP. Biên Hòa, Đồng Nai. Hotline: (+84-251) 391.8888.',
        'blog' => 'Cập nhật tin tức, sự kiện và cẩm nang du lịch Biên Hòa, Đồng Nai từ Aurora Hotel Plaza.',
        'explore' => 'Khám phá các địa danh du lịch nổi tiếng xung quanh Biên Hòa như Khu du lịch Bửu Long, Văn miếu Trấn Biên khi lưu trú tại Aurora Hotel Plaza.'
    ];

    return $descriptions[$current_page] ?? 'Chào mừng bạn đến với Aurora Hotel Plaza - Khách sạn & Căn hộ cao cấp tại Biên Hòa, Đồng Nai. Trải nghiệm dịch vụ nghỉ dưỡng 4 sao hoàn hảo.';
}
?>
