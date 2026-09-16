<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Config\Config;

class RedirectTest extends TestCase
{
    private $redirects = [
        'xt7oldexample1' => [
            'to' => 'xt7newexample1',
            'note' => 'Retired for testing.',
        ],
        'xt7oldexample2' => [
            'to' => 'not a valid id',
        ],
        'xt7oldexample3' => [],
    ];

    public function testRetiredIdRedirects(): void
    {
        $this->assertSame(
            'xt7newexample1',
            Config::resolveRedirect('xt7oldexample1', $this->redirects)
        );
    }

    public function testComponentFallsBackToCollection(): void
    {
        $this->assertSame(
            'xt7newexample1',
            Config::resolveRedirect('xt7oldexample1_cid3644001', $this->redirects)
        );
    }

    public function testLiveIdIsNotRedirected(): void
    {
        $this->assertFalse(Config::resolveRedirect('xt73xs5jd22r', $this->redirects));
        $this->assertFalse(Config::resolveRedirect('xt73xs5jd22r_cid3644001', $this->redirects));
    }

    public function testMalformedTargetIsRejected(): void
    {
        $this->assertFalse(Config::resolveRedirect('xt7oldexample2', $this->redirects));
        $this->assertFalse(Config::resolveRedirect('xt7oldexample3', $this->redirects));
    }

    public function testShippedRedirectsAreUsable(): void
    {
        $file = implode(DIRECTORY_SEPARATOR, [APP, 'Config', 'redirects.json']);
        $redirects = json_decode(file_get_contents($file), true);
        $this->assertIsArray($redirects, 'redirects.json is not valid JSON');

        foreach (array_keys($redirects) as $old) {
            $this->assertNotFalse(
                Config::resolveRedirect($old, $redirects),
                "redirects.json entry for $old does not resolve to a usable id"
            );
        }
    }
}
