<?php
session_start();
require_once '../config/database.php';
require_once 'controllers/CalendarTimelineController.php';

$page_title = 'Lịch đặt phòng - Timeline';
$page_subtitle = 'Xem lịch đặt phòng theo timeline';

$data = getCalendarTimelineData();
extract($data);

include 'includes/admin-header.php';
include 'views/calendar-timeline.view.php';
include 'includes/admin-footer.php';
