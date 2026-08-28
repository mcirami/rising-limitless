<?php
// This legacy URL is handled by Laravel, including failed middleware responses.
$response->send();
$kernel->terminate($request, $response);
