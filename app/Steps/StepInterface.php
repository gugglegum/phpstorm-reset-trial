<?php

declare(strict_types=1);

namespace App\Steps;

interface StepInterface
{
    /**
     * Performs actions of the step
     *
     * @return void
     */
    public function forward(): void;

    /**
     * Reverts actions of the step previously performed by forward() method
     */
    public function backward(): void;

    /**
     * Checks whether revert by backward() method is needed
     *
     * @return bool
     */
    public function needBackward(): bool;
}
