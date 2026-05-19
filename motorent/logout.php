<?php
session_start();
session_destroy();
header('Location: /motorent/login.php');
exit;
