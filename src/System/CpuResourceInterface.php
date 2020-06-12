<?php
declare(strict_types=1);

namespace DV\System;


interface CpuResourceInterface
{
    public function connect() ;
    public function extract() ;
}