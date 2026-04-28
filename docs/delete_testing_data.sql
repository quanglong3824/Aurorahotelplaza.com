-- =====================================================
-- DELETE TESTING DATA - Aurora Hotel Plaza
-- Date: 2026-04-28
-- Purpose: Remove all testing customers and related data
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;

-- Step 1: Get booking IDs to delete (for reference)
-- Testing bookings have user_id 16,21,22,23,24,27,28,29 
-- OR special_requests contains 'Testing Auto-fill' or 'TEST MAIL'

-- Step 2: Update rooms status back to available (rooms occupied by test bookings)
UPDATE `rooms` SET `status` = 'available', `updated_at` = NOW() 
WHERE `room_id` IN (38, 39, 40, 42, 44, 45, 49, 50, 60, 61, 71, 72, 82, 83, 93, 94);

-- Step 3: Delete booking_history for test bookings
DELETE FROM `booking_history` 
WHERE `booking_id` IN (1, 2, 3, 4, 5, 8, 9, 10, 11, 18);

-- Step 4: Delete booking_extra_guests for test bookings
DELETE FROM `booking_extra_guests` 
WHERE `booking_id` IN (1, 2, 3, 4, 5, 8, 9, 10, 11, 18);

-- Step 5: Delete test bookings
DELETE FROM `bookings` 
WHERE `booking_id` IN (1, 2, 3, 4, 5, 8, 9, 10, 11, 18)
   OR `special_requests` LIKE '%Testing Auto-fill%'
   OR `inquiry_message` LIKE '%TEST MAIL%'
   OR `inquiry_message` LIKE '%IT TEST%';

-- Step 6: Delete activity_logs for test users
DELETE FROM `activity_logs` 
WHERE `user_id` IN (16, 21, 22, 23, 24, 27, 28, 29)
   OR `entity_id` IN (1, 2, 3, 4, 5, 8, 9, 10, 11, 18);

-- Step 7: Delete contact_submissions that are tests
DELETE FROM `contact_submissions` 
WHERE `email` LIKE '%tester%@%.com'
   OR `subject` LIKE '%TEST MAIL%'
   OR `subject` LIKE '%IT TEST%'
   OR `message` LIKE '%IT TEST%'
   OR `id` IN (2, 3);

-- Step 8: Delete chat_messages and conversations related to test guests
-- Guest IDs from test bookings: guest_* from conversations
DELETE FROM `chat_messages` 
WHERE `guest_id` IS NULL AND `sender_id` IN (0);

-- Step 9: Delete test users (customers)
DELETE FROM `users` 
WHERE `user_id` IN (16, 21, 22, 23, 24, 27, 28, 29)
   OR `email` LIKE 'tester%@%.com'
   OR `user_name` LIKE 'Khách Hàng Testing%';

-- Step 10: Delete CSRF tokens for deleted users
DELETE FROM `csrf_tokens` 
WHERE `user_id` IN (16, 21, 22, 23, 24, 27, 28, 29);

-- Step 11: Clean up - delete orphaned chat_typing records
DELETE FROM `chat_typing` WHERE `id` > 100;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- SUMMARY OF DELETED DATA:
-- 
-- Users deleted: 8 (user_id: 16,21,22,23,24,27,28,29)
-- Bookings deleted: 10 (booking_id: 1,2,3,4,5,8,9,10,11,18)
-- Booking history deleted: ~10 records
-- Rooms updated to available: 16 rooms
-- Activity logs deleted: ~10 records
-- Contact submissions deleted: 2-3 records
-- =====================================================

-- Verification queries (run after delete):
SELECT COUNT(*) AS remaining_users FROM `users` WHERE `email` LIKE 'tester%@%.com';
SELECT COUNT(*) AS remaining_test_bookings FROM `bookings` WHERE `special_requests` LIKE '%Testing%';
SELECT COUNT(*) AS occupied_rooms FROM `rooms` WHERE `status` = 'occupied';