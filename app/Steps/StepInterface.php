<?php

declare(strict_types=1);

namespace App\Steps;

interface StepInterface
{
    /**
     * Performs actions of the step
     */
    public function forward();

    /**
     * Reverts actions of the step previously performed by forward() method
     */
    public function backward();

    /**
     * Checks whether revert by backward() method is needed
     *
     * @return bool
     */
    public function needBackward(): bool;
}
