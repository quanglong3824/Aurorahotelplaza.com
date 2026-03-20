-- ============================================
-- Aurora Hotel Plaza - Performance Indexes
-- ============================================
-- Execute this SQL to optimize database queries
-- Run: mysql -u root -p auroraho_aurorahotelplaza.com < performance_indexes.sql
-- ============================================

-- 1. Bookings table - Most frequently queried
-- Query pattern: WHERE status = ? AND user_id = ? ORDER BY created_at DESC
-- Query pattern: WHERE status IN ('completed', 'checked_out') COUNT DISTINCT user_id
-- Query pattern: WHERE check_in_date >= ? AND check_out_date <= ?
DROP INDEX IF EXISTS idx_bookings_status_user ON bookings;
CREATE INDEX idx_bookings_status_user ON bookings(status, user_id, created_at);

DROP INDEX IF EXISTS idx_bookings_dates ON bookings;
CREATE INDEX idx_bookings_dates ON bookings(check_in_date, check_out_date, room_type_id);

DROP INDEX IF EXISTS idx_bookings_status_date ON bookings;
CREATE INDEX idx_bookings_status_date ON bookings(status, check_in_date);

-- 2. Room Types - Frequently filtered
-- Query pattern: WHERE status = 'active' AND category = ? ORDER BY sort_order ASC
DROP INDEX IF EXISTS idx_room_types_status_category ON room_types;
CREATE INDEX idx_room_types_status_category ON room_types(status, category, sort_order);

DROP INDEX IF EXISTS idx_room_types_slug ON room_types;
CREATE INDEX idx_room_types_slug ON room_types(slug);

-- 3. Rooms table - Count queries
-- Query pattern: SELECT COUNT(*) FROM rooms WHERE status != 'inactive'
DROP INDEX IF EXISTS idx_rooms_status ON rooms;
CREATE INDEX idx_rooms_status ON rooms(status);

-- 4. Reviews table - Rated reviews
-- Query pattern: WHERE status = 'approved' AND rating >= 4 ORDER BY created_at DESC
DROP INDEX IF EXISTS idx_reviews_status_rating ON reviews;
CREATE INDEX idx_reviews_status_rating ON reviews(status, rating, created_at);

DROP INDEX IF EXISTS idx_reviews_user ON reviews;
CREATE INDEX idx_reviews_user ON reviews(user_id, created_at);

-- 5. Blog Posts - Published content
-- Query pattern: WHERE status = 'published' ORDER BY published_at DESC
DROP INDEX IF EXISTS idx_blog_posts_status ON blog_posts;
CREATE INDEX idx_blog_posts_status ON blog_posts(status, published_at DESC);

DROP INDEX IF EXISTS idx_blog_posts_slug ON blog_posts;
CREATE INDEX idx_blog_posts_slug ON blog_posts(slug);

-- 6. Services - Active services
-- Query pattern: WHERE status = 'active' ORDER BY sort_order ASC
DROP INDEX IF EXISTS idx_services_status ON services;
CREATE INDEX idx_services_status ON services(status);

-- 7. Promotions - Date range and status
-- Query pattern: WHERE status = 'active' AND start_date <= CURDATE() AND end_date >= CURDATE()
DROP INDEX IF EXISTS idx_promotions_active ON promotions;
CREATE INDEX idx_promotions_active ON promotions(status, start_date, end_date);

-- 8. Users - User lookups
-- Query pattern: User_id lookups in reviews, bookings
DROP INDEX IF EXISTS idx_users_user_id ON users;
CREATE INDEX idx_users_user_id ON users(user_id);

DROP INDEX IF EXISTS idx_users_email ON users;
CREATE INDEX idx_users_email ON users(email);

-- 9. Images optimization - thumbnail lookups
DROP INDEX IF EXISTS idx_room_types_thumbnail ON room_types;
CREATE INDEX idx_room_types_thumbnail ON room_types(thumbnail(100));

-- 10. Session cleanup helpers
-- Add last_activity column if not exists
ALTER TABLE sessions ADD COLUMN IF NOT EXISTS last_activity INT UNSIGNED DEFAULT NULL;
UPDATE sessions SET last_activity = UNIX_TIMESTAMP() WHERE last_activity IS NULL;
DROP INDEX IF EXISTS idx_sessions_activity ON sessions;
CREATE INDEX idx_sessions_activity ON sessions(last_activity);

-- 11. Composite indexes for common multi-condition queries
DROP INDEX IF EXISTS idx_reviews_composite ON reviews;
CREATE INDEX idx_reviews_composite ON reviews(user_id, room_type_id, status, rating, created_at);

-- 12. Prevent duplicate lookups
DROP INDEX IF EXISTS idx_rooms_room_type ON rooms;
CREATE INDEX idx_rooms_room_type ON rooms(room_type_id, status);

-- ============================================
-- Verify indexes were created
-- ============================================
-- SHOW INDEX FROM bookings;
-- SHOW INDEX FROM room_types;
-- SHOW INDEX FROM rooms;
-- SHOW INDEX FROM reviews;
-- SHOW INDEX FROM blog_posts;

-- ============================================
-- Performance Notes
-- ============================================
-- 1. Run ANALYZE TABLE after adding indexes:
--    ANALYZE TABLE bookings;
--    ANALYZE TABLE room_types;
--    ANALYZE TABLE reviews;
--
-- 2. Monitor query performance with:
--    EXPLAIN SELECT * FROM bookings WHERE status = 'pending';
--
-- 3. These indexes should reduce query time from ~150-300ms to <50ms
--    for typical queries used in index.php, rooms.php, apartments.php
