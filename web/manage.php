<?php

require_once __DIR__ . '/src/setup.php';

echo ( new EasyTransfer\Pages\ManagementPage() )->getOutput();