-- =============================================================
-- EFL Access Portal — Super Admin inicial
-- Ejecutar UNA SOLA VEZ en la DB de producción
-- Base de datos: servercpanel_privateapi
-- =============================================================

-- =============================================================
-- OPCIÓN A: Crear usuario nuevo
-- Cambiar el password después del primer login desde el panel
-- =============================================================
INSERT INTO `users` (`name`, `email`, `password`, `role`, `country_id`, `is_active`, `created_at`, `updated_at`)
VALUES (
    'EFL Super Admin',
    'admin@efltrackingsystem.com',
    '$2y$12$e3f6o9JScTJh5TSnYjlN3uLg.GtvUa0Y0doBfn/Y5Uz0WIqM3qhAS',  -- hash bcrypt (cambiar password tras primer login)
    'super_admin',
    NULL,   -- NULL = acceso global a todos los países
    1,
    NOW(),
    NOW()
);

-- =============================================================
-- OPCIÓN B: Promover un usuario existente a super_admin
-- Reemplazar con el email del usuario a promover
-- =============================================================
-- UPDATE `users`
-- SET `role` = 'super_admin', `country_id` = NULL, `is_active` = 1
-- WHERE `email` = 'usuario@efltrackingsystem.com';

-- =============================================================
-- Verificar resultado
-- =============================================================
-- SELECT id, name, email, role, country_id, is_active FROM users WHERE role = 'super_admin';
