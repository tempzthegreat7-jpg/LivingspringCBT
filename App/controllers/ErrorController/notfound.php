<?php

loadView('error', [
    'code' => 404,
    'title' => 'Page Not Found',
    'message' => 'The page you requested does not exist or may have been moved.'
]);
