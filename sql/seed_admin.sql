USE news_website;

-- Create default super admin
-- Username: admin
-- Password: Admin@123
-- Make sure to change this password after first login!

INSERT INTO admins (username, email, password, full_name, role, status) VALUES
('admin', 'admin@newshub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 'super_admin', 'active');

-- Note: The password hash above is for "password" - you should generate a new hash for "Admin@123" using:
-- password_hash('Admin@123', PASSWORD_DEFAULT);
-- For production use, run this PHP code to generate the hash:
/*
<?php
echo password_hash('Admin@123', PASSWORD_DEFAULT);
?>
*/