<?php

declare(strict_types=1);

namespace App\Steps;

abstract class StepAbstract implements StepInterface
{
    /**
     * @var StepConfig
     */
    protected $stepConfig;

    public function __construct(StepConfig $stepConfig)
    {
        $this->stepConfig = $stepConfig;
    }
}
