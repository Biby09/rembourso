<?php include $_SERVER['DOCUMENT_ROOT'].'/config/session.php'; ?>

<?php
session_destroy();
header("Location: ../login");
exit();