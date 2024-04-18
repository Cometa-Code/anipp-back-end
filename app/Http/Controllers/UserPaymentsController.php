<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Payments\CreatePaymentRequest;
use App\Models\User;
use App\Models\UserPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserPaymentsController extends Controller
{
    public function store(CreatePaymentRequest $request)
    {
        $user = Auth::user();

        if ($user->role == 'associate') {
            return Responses::BADREQUEST('Apenas administradores podem tomar essa ação!');
        }

        $data = $request->all();

        $createPayment = UserPayments::create($data);

        if (!$createPayment) {
            return Responses::BADREQUEST('Erro ao adicionar pagamento!');
        }

        return Responses::CREATED('Pagamento adicionado com sucesso!');
    }

    public function index(Request $request) {
        $user = Auth::user();

        $items_per_page = $request->query('items_per_page', 10);

        $getPayments = UserPayments::where('user_id', $user->id)->orderBy('payment_date', 'asc')->paginate($items_per_page);

        if (!$getPayments) {
            return Responses::BADREQUEST('Erro ao buscar pagamentos!');
        }

        $sumPayments = UserPayments::where('user_id', $user->id)
        ->select(
            DB::raw('SUM(credit_value) as total_credit_value'),
            DB::raw('SUM(membership_fee) as total_membership_fee'),
            DB::raw('SUM(charges) as total_charges'),
            DB::raw('SUM(fees) as total_fees')
        )
        ->first();

        return Responses::OK('', [
            "data" => $getPayments,
            "totalSumPayments" => $sumPayments['total_credit_value'] + $sumPayments['total_membership_fee'] + $sumPayments['total_charges'] + $sumPayments['total_fees'],
            "financial_situation" => $user->financial_situation
        ]);
    }

    public function get_associate_payments(Request $request, $user_id)
    {
        $user = Auth::user();

        if ($user->role == 'associate') {
            return Responses::BADREQUEST('Apenas administradores podem tomar essa ação!');
        }

        $items_per_page = $request->query('items_per_page', 10);

        $getUserInfos = User::where('id', $user_id)->first();

        $getPayments = UserPayments::where('user_id', $user_id)->with('user')->orderBy('payment_date', 'asc')->paginate($items_per_page);

        if (!$getPayments) {
            return Responses::BADREQUEST('Erro ao buscar pagamentos!');
        }

        $sumPayments = UserPayments::where('user_id', $user_id)
        ->select(
            DB::raw('SUM(credit_value) as total_credit_value'),
            DB::raw('SUM(membership_fee) as total_membership_fee'),
            DB::raw('SUM(charges) as total_charges'),
            DB::raw('SUM(fees) as total_fees')
        )
        ->first();

        return Responses::OK('', [
            "data" => $getPayments,
            "totalSumPayments" => $sumPayments['total_credit_value'] + $sumPayments['total_membership_fee'] + $sumPayments['total_charges'] + $sumPayments['total_fees'],
            "financial_situation" => $user->financial_situation,
            "associate_data" => $getUserInfos
        ]);
    }
}
