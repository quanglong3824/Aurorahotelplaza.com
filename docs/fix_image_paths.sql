-- SQL Script to fix image path casing (Convert to Lowercase)
-- Run this script to ensure all image paths in the database match the Linux filesystem casing

-- 1. Fix Room Types
UPDATE room_types SET thumbnail = LOWER(thumbnail) WHERE thumbnail IS NOT NULL;
UPDATE room_types SET images = LOWER(images) WHERE images IS NOT NULL;

-- 2. Fix Services
UPDATE services SET thumbnail = LOWER(thumbnail) WHERE thumbnail IS NOT NULL;
UPDATE services SET images = LOWER(images) WHERE images IS NOT NULL;

-- 3. Fix Gallery
UPDATE gallery SET image_url = LOWER(image_url) WHERE image_url IS NOT NULL;
UPDATE gallery SET thumbnail_url = LOWER(thumbnail_url) WHERE thumbnail_url IS NOT NULL;

-- 4. Fix Blog Posts
UPDATE blog_posts SET featured_image = LOWER(featured_image) WHERE featured_image IS NOT NULL;

-- 5. Fix Banners
UPDATE banners SET image_desktop = LOWER(image_desktop) WHERE image_desktop IS NOT NULL;
UPDATE banners SET image_mobile = LOWER(image_mobile) WHERE image_mobile IS NOT NULL;

-- 6. Specific Fixes for known bad paths (if any remained)
-- Correcting 'src/ui' paths if they are intended to point to 'ui' directly or if 'src' implies a dev path
-- Assuming 'assets/img/src/ui/' should map to 'assets/img/ui/' if 'src' is not present in deployment
-- Based on file check: assets/img/src/ui DOES exist, so LOWER() is sufficient.
