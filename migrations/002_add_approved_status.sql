ALTER TABLE queue 
MODIFY COLUMN status ENUM('pending','approved','processing','done','failed','rejected') 
NOT NULL DEFAULT 'pending';
