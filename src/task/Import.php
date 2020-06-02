<?php

namespace Swordfox\Shopify\Task;

ini_set('max_execution_time', '300');

use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;

use Swordfox\Shopify\Client;

/**
 * Class Import
 *
 * @author Bram de Leeuw
 */
class Import extends BuildTask
{
    protected $title = 'Import shopify products';

    protected $description = 'Import shopify products from the configured store';

    protected $enabled = true;

    public $api_limit;

    public function run($request)
    {
        try {
            $client = new Client();
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            exit($e->getMessage());
        } catch (\Exception $e) {
            exit($e->getMessage());
        }

        if (!$client->api_limit = $client::config()->get('api_limit')) {
            $client->api_limit = 50;
        }

        $productsonly = false;
        $productsall = false;

        $urlParts = explode('/', $_SERVER['REQUEST_URI']);
        $urlPartsCheckIndex = (Director::is_cli() ? 3 : 4); // Cron or Browser

        if (isset($urlParts[$urlPartsCheckIndex])) {
            if ($urlParts[$urlPartsCheckIndex]=='productsonly') {
                $productsonly = true;
            } elseif ($urlParts[$urlPartsCheckIndex]=='productsall') {
                $productsall = true;
                $client->api_limit = 250; // Set to maximum API limit
            }
        }

        if (!Director::is_cli()) {
            echo "<pre>";
        }

        if ($productsonly) {
            $this->importProducts($client);
        } else {
            $this->importCollections($client, 'custom_collections');
            $this->importCollections($client, 'smart_collections');
            $this->importProducts($client, $productsall);
        }

        if (!Director::is_cli()) {
            echo "</pre>";
        }
        exit('Done');
    }

    /**
     * Loop the given data map and possible sub maps
     *
     * @param array $map
     * @param $object
     * @param $data
     */
    public static function loop_map($map, &$object, $data)
    {
        foreach ($map as $from => $to) {
            if (is_array($to) && is_object($data->{$from})) {
                self::loop_map($to, $object, $data->{$from});
            } elseif (isset($data->{$from}) && $value = $data->{$from}) {
                $object->{$to} = $value;
            }
        }
    }
}
