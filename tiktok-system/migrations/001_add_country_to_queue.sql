-- Ejecutar en MySQL (Hostinger) si la tabla ya existe sin esta columna.
ALTER TABLE queue
  ADD COLUMN country VARCHAR(100) NULL
  AFTER uploader_name;
