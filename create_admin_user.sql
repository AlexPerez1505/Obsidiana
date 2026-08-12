-- Script para crear un nuevo usuario administrador
-- Ejecuta esto en tu base de datos MySQL

INSERT INTO users (
    name, 
    email, 
    email_verified_at,
    password, 
    is_admin, 
    status,
    created_at, 
    updated_at
) VALUES (
    'Admin',
    'admin@obsidiana.local',
    NOW(),
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5YmMxSUmGEJiq', -- password: password
    1,
    'approved',
    NOW(),
    NOW()
);

-- Mostrar el nuevo usuario creado
SELECT id, name, email, is_admin, status FROM users WHERE email = 'admin@obsidiana.local';
