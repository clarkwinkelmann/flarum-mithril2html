<?php

use ClarkWinkelmann\Mithril2Html\Setup;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extend\Frontend;
use Flarum\Extension\Extension;
use Flarum\Foundation\Config;
use Flarum\Foundation\InstalledSite;
use Flarum\Foundation\Paths;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;

// From Flarum\Testing\integration\UsesTmpDir
$tmp = getenv('FLARUM_TEST_TMP_DIR_LOCAL') ?: getenv('FLARUM_TEST_TMP_DIR') ?: __DIR__ . '/../../vendor/flarum/testing/src/integration/tmp';

if (preg_match('~^/assets/~', $_SERVER['REQUEST_URI'])) {
    // Remove ?v= from assets before checking if file exists
    $path = $tmp . '/public' . explode('?', $_SERVER['REQUEST_URI'])[0];

    if (file_exists($path)) {
        echo file_get_contents($path);

        die();
    }

    return false;
}

require __DIR__ . '/../../vendor/autoload.php';

$config = include "$tmp/config.php";

// Force Flarum to use the same URL that was used for the dev server
Arr::set($config, 'url', 'http://' . $_SERVER['HTTP_HOST']);

$site = new InstalledSite(
    new Paths([
        'base' => $tmp,
        'public' => "$tmp/public",
        'storage' => "$tmp/storage",
        'vendor' => __DIR__ . '../../vendor',
    ]),
    new Config($config)
);

// Similar to flarum/testing's BeginTransactionAndSetDatabase but we need to set the setting value before everything else
// that way tests don't need any database seed, which prevents any deadlock with the transaction
class PrepareDatabase implements ExtenderInterface
{
    public function extend(Container $container, Extension $extension = null)
    {
        /**
         * @var ConnectionInterface $db
         */
        $db = $container->make(ConnectionInterface::class);

        $db->table('settings')->insertOrIgnore([
            'key' => 'mithril2html.token',
            'value' => 'testing',
        ]);

        $db->beginTransaction();

        // Done inside of the transaction, we don't want this to conflict with potential future tests
        // Or other tests done using the same database
        $db->table('discussions')->insert([
            'id' => 1,
            'title' => 'The discussion',
        ]);
    }
}

$site->extendWith([
    new Setup(),
    (new Frontend('mithril2html'))->js(__DIR__ . '/../fixtures/js/dist/mithril2html.js'),
    new PrepareDatabase(),
]);

$server = new Flarum\Http\Server($site);
$server->listen();
