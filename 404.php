<?php

ob_start();
?>
<h1>404 Not Found</h1>

<?php

$content = ob_get_contents();
ob_end_clean();
require(__DIR__ . "/app/templates/errors.php");
?>