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
            'packs_quantity',
            'resto_id'
        ]);

        $transaction = Transactions::create([
            // 'id' => $request->id,
            'status' => $request->status,
            'payment_url' => '',
            'type' => $request->type,
            'valuation' => $request->valuation,
            'packs' => $request->packs,
            'user_id' => $request->id,
        ]);

        //MIDTRANS CONFIG
        // Config::$serverKey = config('services.midtrans.serverKey');
        // Config::$isProduction = config('services.midtrans.isProduction');
        // Config::$isSanitized = config('services.midtrans.isSanitized');
        // Config::$is3ds = config('services.midtrans.is3ds');

        //call the created transaction
        $transactionGetter = Transactions::with(['user'])->find($transaction->id);
        $userGetter = User::where('id', $request->id)->first();
        $restoGetter = Restos::where('id', $request->resto_id)->first();

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

        if ($transactionGetter->type == 'withdraw') {
            try {

                if ($userGetter->current_credit >= $request->valuation) {
                    $userGetter->current_credit = $userGetter->current_credit - $request->valuation;
                    //CALL MIDTRANS //call Midtrans Iris / XENDIT
                    // $transactionGetter->payment_url = ''; //PENDING
                } else {
                    $errorMsg = 'Your input is exceeding your current credit';
                }

                $transactionGetter->status = 'Success';
                $userGetter->save();
                $transactionGetter->save();
                return ResponseFormatter::success(
                    $transactionGetter ?? $errorMsg,
                    'Transaksi Berhasil'
                );
            } catch (Exception $error) {
                return ResponseFormatter::error(
                    $error->getMessage(),
                    'Transaksi Gagal'
                );
            }
        } else if ($transactionGetter->type == 'return') {
            try {
                //panggil API TYPE B, YG INI TYPE A 

                //USER SCAN QR => DAPET KODE KHUSUS
                //SOMEHOW DARI KODE KHUSUS, AKSES DB SEMENTARA RESTO (1 CASHIER = 1 TABLE DB)
                //DISPLAY KE APP USER (WAIT FOR FLUTTER INTEGRATION)
                //CONFIRMED? => CALL API YG INI (type + )

                //tambah 
                $packs = explode(',', $request->packs);
                $packs_quantity = explode(',', $request->packs_quantity);
                $packsDB = json_decode($userGetter->packs, true);

                for ($i = 0; $i < count($packs); $i++) {
                    $result = $packsDB[$packs[$i]] ?? null;
                    if ($result != null) {
                        $packsDB[$packs[$i]] = $packsDB[$packs[$i]] - $packs_quantity[$i];
                        if ($packsDB[$packs[$i]] <= 0) {
                            unset($packsDB[$packs[$i]]);
                        }
                    } else {
                        $errorMsg = 'Array key is not found';
                    }
                }

                $userGetter->packs = json_encode($packsDB);
                $transactionGetter->packs = json_encode($packsDB);
                $transactionGetter->status = 'Success';
                // $transactionGetter->provider = $restoGetter->restoName ?? null;  //STAFF ID

                $transactionGetter->save();
                $userGetter->save();

                return ResponseFormatter::success(
                    $packsDB,
                    'Peminjaman Berhasil'
                );
            } catch (Exception $error) {
                return ResponseFormatter::error(
                    $error->getMessage(),
                    'Peminjaman Gagal'
                );
            }
        } else if ($transactionGetter->type == 'rent') {
            try {
                //STAFF SUDAH DAPATKAN ID USER => DAPAT DATA PACKS DARI DB SESUAI ID USER
                //SETELAH QR SCANNING, API DISPLAYER DIPANGGIL.

                //SETELAH CONFIRM, API INI DIPANGGIL
                //PACKS DITAMPILKAN DI STAFF APP VER.
                //STAFF MEMILIH PACKS YANG DIKEMBALIKAN + checking
                //KONFIRMASI (checkout method dipanggil) => PACKS = packs yang akan dikembalikan

                $packs = explode(',', $request->packs);
                $packs_quantity = explode(',', $request->packs_quantity);
                $packsDB = json_decode($userGetter->packs, true);
                
                for ($i = 0; $i < count($packs); $i++) {
                    $result = $packsDB[$packs[$i]] ?? null;
                    if ($result != null) {
                        $packsDB[$packs[$i]] = $packsDB[$packs[$i]] + $packs_quantity[$i];
                    } else {
                        $packsDB[$packs[$i]] = (int) $packs_quantity[$i];
                    }
                }

                $userGetter->packs = json_encode($packsDB);
                $transactionGetter->packs = json_encode($packsDB);
                // $restoGetter->cashier1 = json_encode([]);
                $transactionGetter->status = 'Success';
                $transactionGetter->provider = $restoGetter->restoName ?? null;

                // $restoGetter->save();
                $transactionGetter->save();
                $userGetter->save();

                return ResponseFormatter::success(
                    $packsDB ?? '',
                    'Pengembalian Berhasil'
                );
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
            'resto_id' => 'required'
        ]);

        $restoGetter = Restos::with(['user'])->find($request->resto_id);

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

    public function qrCode(Request $request)
    {
        $request->validate([
            'id'
        ]);

        $userGetter = User::where('id', $request->id)->first();
        try {
            $userGetter->qr_code = "api.qrserver.com/v1/create-qr-code/?data=pack-me-user-" + $request->id + ";size=500x500";
            return ResponseFormatter::success(
                $userGetter->qr_code,
                'Peminjaman Berhasil'
            );
        } catch (Exception $error) {
            return ResponseFormatter::error(
                $error->getMessage(),
                'Peminjaman Gagal'
            );
        }
    }
}
