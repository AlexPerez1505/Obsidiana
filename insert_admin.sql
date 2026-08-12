-- Insertar nuevo usuario administrador
-- Contraseña: password (hash bcrypt)

INSERT INTO users (name, email, email_verified_at, password, is_admin, status, created_at, updated_at) 
VALUES (
    'Admin',
    'admin@obsidiana.local',
    NOW(),
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5YmMxSUmGEJiq',
    1,
    'approved',
    NOW(),
    NOW()
);

-- Verificar que se creó
SELECT id, name, email, is_admin, status FROM users WHERE email = 'admin@obsidiana.local';
