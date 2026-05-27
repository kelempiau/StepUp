<?php
require_once '../../config/db.php';
session_start();
session_destroy();
header("Location: ../../login.php");
exit;

