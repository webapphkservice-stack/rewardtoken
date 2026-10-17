<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class AbiController extends Controller
{
    public function index()
    {
        $abiFile = storage_path('contracts/compiled/InviteRewardToken.abi');

        if (!file_exists($abiFile)) {
            return response()->json(['success' => false, 'message' => t('abi_file_not_found')]);
        }

        $abi = file_get_contents($abiFile);
        return response()->json([
            'success' => true,
            'abi' => json_decode($abi, true)
        ]);
    }
}
