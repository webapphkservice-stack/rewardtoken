<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class SiteSettingsController extends Controller
{
    public function index()
    {
        if (request()->isMethod('GET')) {
            return response()->json([
                'success' => true,
                'data' => getSiteConfig(),
            ]);
        }

        if (request()->isMethod('POST')) {
            $input = request()->post();
            if (empty($input)) {
                $json = file_get_contents('php://input');
                $input = json_decode($json, true) ?: [];
            }

            $config = getSiteConfig();

            if (isset($input['site_name']) && is_string($input['site_name'])) {
                $config['site_name'] = trim($input['site_name']);
            }
            if (isset($input['site_name_full']) && is_string($input['site_name_full'])) {
                $config['site_name_full'] = trim($input['site_name_full']);
            }
            if (isset($input['site_description']) && is_string($input['site_description'])) {
                $config['site_description'] = trim($input['site_description']);
            }
            if (isset($input['symbol']) && is_string($input['symbol'])) {
                $config['symbol'] = trim($input['symbol']);
            }
            if (array_key_exists('transfer_fee', $input)) {
                $config['transfer_fee'] = is_numeric($input['transfer_fee']) ? max(0, min(100, (float) $input['transfer_fee'])) : 0;
            }

            if (array_key_exists('token_purchase_price_usdt', $input)) {
                $config['token_purchase_price_usdt'] = is_numeric($input['token_purchase_price_usdt']) ? max(1, (float) $input['token_purchase_price_usdt']) : 10;
            }
            if (array_key_exists('token_purchase_amount', $input)) {
                $config['token_purchase_amount'] = is_numeric($input['token_purchase_amount']) ? max(1, (int) $input['token_purchase_amount']) : 1000;
            }
            if (array_key_exists('token_purchase_bonus', $input)) {
                $config['token_purchase_bonus'] = is_numeric($input['token_purchase_bonus']) ? max(0, (int) $input['token_purchase_bonus']) : 0;
            }

            $configFile = __DIR__ . '/../../../../config/site.json';
            $dir = dirname($configFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $result = file_put_contents(
                $configFile,
                json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                LOCK_EX
            );

            if ($result === false) {
                return response()->json(['success' => false, 'message' => t('save_failed_permission')]);
            }

            return response()->json(['success' => true, 'message' => t('site_info_saved'), 'data' => $config]);
        }

        return response()->json(['success' => false, 'message' => t('unsupported_request_method')]);
    }
}
