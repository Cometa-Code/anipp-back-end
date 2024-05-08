<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Payments\CreatePaymentRequest;
use App\Models\CashFlow;
use App\Models\User;
use App\Models\UserPayments;
use Carbon\Carbon;
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

    public function insert_table_payments(Request $request)
    {
        $getUser = User::where('name', 'LIKE', "%$request->A%")->first();

        if (!$getUser) {
            return Responses::BADREQUEST('Usuário não encontrado');
        }

        $payment_method = $request->C;

        if ($request->G > 0) {
            $payment_type = 'Anuidade';
        }

        if ($request->H > 0) {
            $payment_type = 'Semestralidade';
        }

        if ($request->I) {
            $payment_type = 'Mensalidade';
        }

        $payment_date = Carbon::createFromFormat('m/d/Y', $request->D)->format('Y-m-d');

        $credit_value = $request->E;

        $membership_fee = $request->F;

        $fees = $request->J;

        $charges = $request->K;

        $comments = $request->N;

        $createPayment = UserPayments::create([
            'user_id' => $getUser->id,
            'payment_method' => $payment_method,
            'payment_type' => $payment_type,
            'payment_date' => $payment_date,
            'credit_value' => $credit_value,
            'member_fee' => $membership_fee,
            'charges' => $charges,
            'fees' => $fees,
            'comments' => $comments
        ]);

        if (!$createPayment) {
            Return Responses::BADREQUEST('Erro ao adicionar pagamento ao usuário!');
        }

        $createCashFlow = CashFlow::create([
            'user_id' => $getUser->id,
            'type' => 'Entrada',
            'date' => $payment_date,
            'origin_agency' => null,
            'allotment' => null,
            'document_number' => null,
            'history_code' => null,
            'history' => null,
            'value' => $credit_value,
            'history_detail' => null,
            'description' => $comments,
        ]);

        if (!$createCashFlow) {
            return Responses::BADREQUEST('Erro ao adicionar caixa!');
        }

        return Responses::CREATED('Pagamento adicionado com sucesso!');
    }

    public function index(Request $request) {
        $user = Auth::user();

        $items_per_page = $request->query('items_per_page', 10);
        $inital_date = $request->query('initial_date', '2018-01-01');
        $finish_date = $request->query('finish_date', date('Y-m-d'));

        $getPayments = UserPayments::whereBetween('payment_date', [$inital_date, $finish_date])->where('user_id', $user->id)->orderBy('payment_date', 'desc')->paginate($items_per_page);

        if (!$getPayments) {
            return Responses::BADREQUEST('Erro ao buscar pagamentos!');
        }

        $sumPayments = UserPayments::whereBetween('payment_date', [$inital_date, $finish_date])
        ->where('user_id', $user->id)
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
        $inital_date = $request->query('initial_date', '2018-01-01');
        $finish_date = $request->query('finish_date', date('Y-m-d'));

        $getUserInfos = User::where('id', $user_id)->first();

        $getPayments = UserPayments::whereBetween('payment_date', [$inital_date, $finish_date])->where('user_id', $user_id)->with('user')->orderBy('payment_date', 'desc')->paginate($items_per_page);

        if (!$getPayments) {
            return Responses::BADREQUEST('Erro ao buscar pagamentos!');
        }

        $sumPayments = UserPayments::whereBetween('payment_date', [$inital_date, $finish_date])
        ->where('user_id', $user_id)
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
