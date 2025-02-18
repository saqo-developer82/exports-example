<?php

function calculateProgressPercentage($current, $total, $max_percent)
{
    $percentage = floor($current * $max_percent / $total);
    return $percentage;
}
