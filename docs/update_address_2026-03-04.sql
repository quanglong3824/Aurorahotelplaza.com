-- SQL Migration: Update Hotel Official Address
-- New Address: 253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai

-- 1. Update system_settings table
UPDATE system_settings 
SET setting_value = '253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai'
WHERE setting_key = 'hotel_address';

-- 2. Update bot_knowledge table (chatbot knowledge base)
UPDATE bot_knowledge
SET content = REPLACE(content, '253 Phạm Văn Thuận, KP 17, Phường Tam Hiệp, TP. Biên Hòa, Tỉnh Đồng Nai', '253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai')
WHERE content LIKE '%253 Phạm Văn Thuận%';

UPDATE bot_knowledge
SET content = REPLACE(content, '253 Phạm Văn Thuận, KP 17, Phường Tam Hiệp, Tỉnh Đồng Nai', '253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai')
WHERE content LIKE '%253 Phạm Văn Thuận%';

UPDATE bot_knowledge
SET content = REPLACE(content, '253 Phạm Văn Thuận, Phường Tam Hiệp, TP. Biên Hòa, Đồng Nai', '253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai')
WHERE content LIKE '%253 Phạm Văn Thuận%';

UPDATE bot_knowledge
SET content = REPLACE(content, '253 Phạm Văn Thuận, KP 17, Phường Tam Hiệp, Biên Hòa, Đồng Nai', '253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai')
WHERE content LIKE '%253 Phạm Văn Thuận%';

-- 3. Update faqs table
UPDATE faqs
SET answer_vi = REPLACE(answer_vi, '253 Phạm Văn Thuận, KP 17, Phường Tam Hiệp, TP. Biên Hòa, Tỉnh Đồng Nai', '253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai')
WHERE answer_vi LIKE '%253 Phạm Văn Thuận%';

UPDATE faqs
SET answer_en = REPLACE(answer_en, '253 Pham Van Thuan, Tam Hiep Ward, Bien Hoa City, Dong Nai Province', '253, Pham Van Thuan, KP2, Tam Hiep Ward, Dong Nai Province')
WHERE answer_en LIKE '%253 Pham Van Thuan%';

-- 4. Update chat_conversations table (chat logs)
UPDATE chat_conversations
SET message = REPLACE(message, '253 Phạm Văn Thuận, KP 17, Phường Tam Hiệp, TP. Biên Hòa, Tỉnh Đồng Nai', '253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai')
WHERE message LIKE '%253 Phạm Văn Thuận%';

UPDATE chat_conversations
SET message = REPLACE(message, '253 Phạm Văn Thuận, KP 17, Phường Tam Hiệp, Biên Hòa, Đồng Nai', '253, Phạm Văn Thuận, KP2, Phường Tam Hiệp, Tỉnh Đồng Nai')
WHERE message LIKE '%253 Phạm Văn Thuận%';
