<?php

namespace App\CaptchaSolver;

interface CaptchaSolverInterface
{
    public function solveCaptcha(\GdImage $image): ?string;
}
