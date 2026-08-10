-- Script para hacer un usuario administrador
-- Actualiza el usuario más reciente a administrador

UPDATE users 
SET is_admin = 1 
WHERE id = (SELECT MAX(id) FROM users);

-- Verificar resultado
SELECT id, name, is_admin FROM users ORDER BY created_at DESC LIMIT 5;
