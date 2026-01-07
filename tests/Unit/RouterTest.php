<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Core\Router;

class RouterTest extends TestCase
{
    public function testAddRoute()
    {
        $router = new Router();
        $router->add('GET', '/test', function() {
            return 'success';
        });

        $this->assertEquals('success', $router->dispatch('GET', '/test'));
    }

    public function testNotFound()
    {
        $router = new Router();

        ob_start();
        $router->dispatch('GET', '/not-found');
        $output = ob_get_clean();

        $this->assertEquals(404, http_response_code());
        $this->assertEquals('404 Not Found', $output);
    }
}
