<?php
// config/mail.php
// Gmail SMTP settings
// To get App Password: Google Account → Security → 2-Step Verification → App passwords
// Create app: Mail + Other → copy 16-char password here
return [
    'host'     => 'smtp.gmail.com',
    'port'     => 587,
    'username' => 'olexandrmasuk@gmail.com',
    'password' => '',           // ← вставити Gmail App Password (16 символів без пробілів)
    'from'     => 'olexandrmasuk@gmail.com',
    'from_name'=> 'Content Planner',
];
