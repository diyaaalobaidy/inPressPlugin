<?php

/**
 * @file plugins/generic/inPressPlugin/index.php
 *
 * Copyright (c) 2026
 * Distributed under the GNU GPL v3.
 *
 * @brief Wrapper for the In Press Plugin for OJS 3.5.
 */

require_once __DIR__ . '/InPressPlugin.php';

return new \APP\plugins\generic\inPressPlugin\InPressPlugin();
