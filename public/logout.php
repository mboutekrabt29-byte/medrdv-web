<?php
session_start();
session_unset();
session_destroy();

header("Location: /medrdv_web/public/index.php");
exit;