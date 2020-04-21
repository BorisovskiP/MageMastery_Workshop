<?php

declare(strict_types=1);

namespace MageMastery\Todo\Api;

/**
 * @api
 */
interface TaskMenagementInterface
{
    public function save();

    public function delete();
}
