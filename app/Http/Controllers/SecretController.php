<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use  App\Models\TopSecret;

class SecretController extends Controller
{    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeSecret(Request $request)
    {    
        try {    
            $this->validate(request(), [
                'secret' => 'required',
                'expireAfterViews' => 'required',
                'expireAfter' => 'required',
            ]);
            
            $secretT = request('secret');
            $expireAfterViews = request('expireAfterViews');
            $expireAfter = (int)request('expireAfter');
            
            $time  = Carbon::now();
            $eTime = Carbon::now()->addMinutes($expireAfter);
            $randomstring = $this->generateRandomString();
            $hash = Crypt::encryptString($randomstring);
            
            $secretText = Crypt::encryptString($secretT);
            
            $secret = new TopSecret;
            $secret->hash = $hash;
            $secret->secretText = $secretText;
            $secret->remainingViews = (int)$expireAfterViews;
            $secret->expiresAt = $eTime;
            $secret->createdAt = $time;
            $secret->save();
            return response()->json([
                'description' => 'Successful operation',
                'secret' => [
                    'hash' => $secret->hash,
                    'secretText' => Crypt::decryptString($secret->secretText),
                    'createdAt' => $secret->createdAt,
                    'remainingViews' => $secret->remainingViews,
                    'expiresAt' => $secret->expiresAt,                
                ],
            ], 200 );
        } catch (\Exception $e) {
            return response()->json([
                'description' => $e
            ], 405 );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  hash  $hash
     * @return \Illuminate\Http\Response
     */
    public function showSecret($hash)
    {
        $hash = trim($hash);
        if ($hash != "") {
            try {
                try {
                    $secret = TopSecret::where([
                        ['hash', '=', $hash],
                        ['remainingViews', '>', 0],
                        ['expiresAt', '>=', Carbon::now()],
                    ])->first();
                } catch(\Illuminate\Database\QueryException $ex){ 
                    //echo($ex->getMessage());
                }
                if ($secret === null) {
                    return response()->json([
                        'description' => 'Secret not found'                
                    ], 404 );
                 } else {
                    $secret->remainingViews = $secret->remainingViews -1;
                    $secret->save();
                    return response()->json([
                        'description' => 'Successful operation',
                        'secret' => [
                        'hash' => $secret->hash,
                        'secretText' => Crypt::decryptString($secret->secretText),
                        'createdAt' => $secret->createdAt,
                        'expiresAt' => $secret->expiresAt,
                        'remainingViews' => $secret->remainingViews,
                        ]
                    ], 200 );
                 }
            } catch (\Exception $e) {
                return response()->json([
                    'description' => 'An error has occured'
                ], 405 );
            }
        } else {
            return response()->json([
                'description' => 'No hash input'                
            ], 405 );
        }

    }


    public function generateRandomString($length = 7) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


}
