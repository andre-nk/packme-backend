<?php

namespace App\Http\Controllers\API;

use App\Models\Restos;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Transactions;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        // $pack = $request->input('pack');
        // $quantity = $request->input('quantity');
        $status = $request->input('status');

        if ($id) {
            $transaction = Transactions::with(['user'])->find($id);
            if ($transaction) {
                return ResponseFormatter::success([
                    $transaction,
                    'Data transaksi berhasil diambil'
                ]);
            } else {
                return ResponseFormatter::error([
                    null,
                    'Data transaksi gagal diambil',
                    404
                ]);
            }
        }

        $transactions = Transactions::with(['user'])->where('user_id', Auth::user()->id);

        if ($status) {
            $transactions->where('status', $status);
        }

        return ResponseFormatter::success([
            $transactions->paginate($limit),
            'Daftar transaksi berhasil diambil'
        ]);
    }

    //CHECKOUT DIJALANKAN KETIKA:
    //1.) RENT: SETELAH QR CODE TERIMA INPUT, LALU CALL API
    //2.) WITHDRAW: SETELAH TOMBOL CONFIRM DITAP,
    //3.) RETURN: SETELAH QR CODE USER DI SCAN STAFF, DAN CALL API
    public function checkout(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'type' => 'required',
            'valuation' => 'required',
            'packs',
        ]);

        $transaction = Transactions::create([
            // 'id' => $request->id,
            'status' => $request->status,
            'payment_url' => '',
            'type' => $request->type,
            'valuation' => $request->valuation,
            'packs' => $request->packs,
            'user_id' => $request->id
        ]);

        //MIDTRANS CONFIG
        // Config::$serverKey = config('services.midtrans.serverKey');
        // Config::$isProduction = config('services.midtrans.isProduction');
        // Config::$isSanitized = config('services.midtrans.isSanitized');
        // Config::$is3ds = config('services.midtrans.is3ds');

        //call the created transaction
        $transactionGetter = Transactions::with(['user'])->find($transaction->id);
        $userGetter = User::where('id', $request->id)->first();

        // $midtrans = [
        //     'transaction_details' => [
        //         'order_id' => $transaction->id,
        //         'gross_amount' => (int) $transaction->total,
        //     ],
        //     'customer_details' => [
        //         'first_name' => $transaction->user->name,
        //         'email' => $transaction->user->email,
        //     ],
        //     'enabled_payments' => [
        //         'gopay',
        //         'bank_transfer'
        //     ],
        //     'vtweb' => []
        // ];

        //MIDTRANS CALL
        if ($transactionGetter->type == 'withdraw') {
            try {
                //call Midtrans Iris / XENDIT
                // $transactionGetter->payment_url = ''; //PENDING
                // $transactionGetter->save();
                $transactionGetter->status = 'Success';

                $transactionGetter->save();
                return ResponseFormatter::success(
                    $transactionGetter,
                    'Transaksi Berhasil'
                );
            } catch (Exception $error) {
                return ResponseFormatter::error(
                    $error->getMessage(),
                    'Transaksi Gagal'
                );
            }
        } else if ($transactionGetter->type == 'rent') {
            try {
                //panggil API TYPE B, YG INI TYPE A 

                //USER SCAN QR => DAPET KODE KHUSUS
                //SOMEHOW DARI KODE KHUSUS, AKSES DB SEMENTARA RESTO (1 CASHIER = 1 TABLE DB)
                //DISPLAY KE APP USER (WAIT FOR FLUTTER INTEGRATION)
                //CONFIRMED? => CALL API YG INI (type + )

                //tambah 
                //$userGetter; //edit this constant (PACK DETAILS)

                $userJson = json_encode($request->packs, true);
                $userDBJson = json_encode($userGetter->packs, true);
                $userGetter->packs = json_encode(array_merge(explode(' ', json_decode($userJson, true)), explode(' ', json_decode($userDBJson, true))));
                $userGetter->save();

                return ResponseFormatter::success(
                    json_encode($request->packs, true),
                    'Peminjaman Berhasil'
                );
            } catch (Exception $error) {
                return ResponseFormatter::error(
                    $error->getMessage(),
                    'Peminjaman Gagal'
                );
            }
        } else if ($transactionGetter->type == 'return') {
            try {


                //STAFF SUDAH DAPATKAN ID USER => DAPAT DATA PACKS DARI DB SESUAI ID USER
                //SETELAH QR SCANNING, API DISPLAYER DIPANGGIL.

                //SETELAH CONFIRM, API INI DIPANGGIL
                //PACKS DITAMPILKAN DI STAFF APP VER.
                //STAFF MEMILIH PACKS YANG DIKEMBALIKAN + checking
                //KONFIRMASI (checkout method dipanggil) => PACKS = packs yang akan dikembalikan

                //$transactionGetter->packs = ''; KURANGI PACK SESUAI PACK DETAIL YANG AKAN DIKEMBALIKAN, FIND OUT HOW!
                //tambah 
                $userJson = json_encode($request->packs, true);
                $userDBJson = explode(' ', json_encode($userGetter->packs, true));

                if (($key = array_search($userJson, $userDBJson)) !== false) {
                    unset($userDBJson[$key]);
                }
                
                $userGetter->packs = $userDBJson;
                $userGetter->save();

                return ResponseFormatter::success(
                    json_encode($request->packs, true),
                    'Pengembalian Berhasil'
                );
                //PACK INFO PENDING
            } catch (Exception $error) {
                return ResponseFormatter::error(
                    $error->getMessage(),
                    'Pengembalian Gagal'
                );
            }
        };
    }

    public function checkout_B(Request $request)
    {
        $request->validate([
            'db_link' => $request->db_link
        ]);

        $restoGetter = Restos::with(['user'])->find($request->db_link);

        if ($restoGetter) {
            return ResponseFormatter::success(
                $restoGetter,
                'Data Restoran Berhasil Diambil'
            );
        } else {
            return ResponseFormatter::error(
                [
                    null,
                    'Data transaksi gagal diambil',
                    404
                ]
            );
        }
    }
    //public function driverGetUserData(){}
}
