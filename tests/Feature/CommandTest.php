<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Console\Commands\OrdersHandle;

class CommandTest extends TestCase
{

    /**
     * @dataProvider idProvider
     */
    public function testCommand($id, $result)
    {
        $this->assertEquals(OrdersHandle::isInCsvTable(23), $result);
    }

    public function idProvider() {
        return [
            [64, false],
            [57, false],
            [243, false],
            [798, false],
            [7483, false],
            [2356, false],
            [944, false],
            [3728, false],
            [283, false],
            [501, false],
            [492, false],
            [395, false],
        ];
    }

    
}
