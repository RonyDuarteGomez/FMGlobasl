<?php
require dirname(__DIR__, 1) . '/bootstrap/app.php';
fm_dispatch('oauth2callback.php');
