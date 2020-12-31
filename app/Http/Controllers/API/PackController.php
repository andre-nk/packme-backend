<?php

namespace App\Http\Controllers\API;

use App\Models\Packs;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PackController extends Controller
{
    public function all(Request $request){
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $name = $request->input('name');
        $type = $request->input('type');
        $valuation_high = $request->input('price_high');
        $valuation_low = $request->input('price_low');

        if($id){
            $pack = Packs::find($id);
            if($pack){
                return ResponseFormatter::success([
                    $pack,
                    'Data packs berhasil diambil'
                ]);
            }else{
                return ResponseFormatter::error([
                    null,
                    'Data packs gagal diambil',
                    404
                ]);
            }
        }

        $packs = Packs::query();

        if($name){
            $packs -> where('name', 'like', '%' . $name . '%');
        }

        if($type){
            $packs -> where('type', 'like', '%' . $type . '%');
        }

        if($valuation_high){
            $packs -> where('valuation', '>=', $valuation_high);
        }

        if($valuation_low){
            $packs -> where('valuation', '<=', $valuation_low);
        }

        return ResponseFormatter::success([
            $packs -> paginate($limit),
            'Daftar packs berhasil diambil'
        ]);
    }
}
