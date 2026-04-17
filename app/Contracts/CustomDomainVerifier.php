<?php

namespace App\Contracts;

use App\Models\LandingPage;

interface CustomDomainVerifier
{
    public function sync(LandingPage $landingPage): LandingPage;

    public function clear(LandingPage $landingPage): LandingPage;
}
