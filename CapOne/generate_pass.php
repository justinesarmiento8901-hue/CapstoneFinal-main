<?php
$fixedPassword = 'MyNewSecurePassword123!';
$hashedPassword = password_hash($fixedPassword, PASSWORD_BCRYPT);
echo "Hashed password: " . $hashedPassword;
