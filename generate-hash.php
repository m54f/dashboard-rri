<?php
// Ganti 'password_anda_disini' dengan password yang Anda inginkan
$password = 'admin123';
echo password_hash($password, PASSWORD_BCRYPT);
