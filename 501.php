<?php

ob_start();
?>
    <h1>501 Not Implemented</h1>

<?php

$content = ob_get_contents();
ob_end_clean();

require(__DIR__ . "/app/layouts/errors.phtml");