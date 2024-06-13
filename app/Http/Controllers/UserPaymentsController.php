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

    public function update_bank_identifier(Request $request)
    {
        $getUser = User::where('name', 'LIKE', "%$request->C%")->first();

        if (!$getUser) {
            return Responses::BADREQUEST('Usuário não encontrado');
        }

        if (!$request->E) {
            return Responses::BADREQUEST('Campos não completos');
        }

        $bank_identifier_b = $request->E;

        $getUser->update([
            'bank_identifier_b' => $bank_identifier_b
        ]);

        return Responses::OK('Atualizado com sucesso!');
    }

    public function insert_table_payments(Request $request)
    {
        $getUser = User::where('name', 'LIKE', "%$request->A%")->first();

        if (!$getUser) {
            return Responses::BADREQUEST('Usuário não encontrado');
        }

        $payment_method = $request->C;

        $payment_type = 'Adesao';
        $credit_value = 0;

        if ($request->G > 0) {
            $payment_type = 'Anuidade';
            $credit_value = $request->G;
        }

        if ($request->H > 0) {
            $payment_type = 'Semestralidade';
            $credit_value = $request->H;
        }

        if ($request->I > 0) {
            $payment_type = 'Mensalidade';
            $credit_value = $request->I;
        }

        $payment_date = $request->D;

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
            'membership_fee' => $membership_fee,
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
            'description' => "Recebimento de $getUser->name",
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
            DB::raw('SUM(fees) as total_fees'),
            DB::raw('SUM(CASE WHEN payment_type IN ("Mensalidade") THEN credit_value ELSE 0 END) as total_credit_value_monthly'),
            DB::raw('SUM(CASE WHEN payment_type IN ("Semestralidade") THEN credit_value ELSE 0 END) as total_credit_value_semiannual'),
            DB::raw('SUM(CASE WHEN payment_type IN ("Anuidade") THEN credit_value ELSE 0 END) as total_credit_value_annual')
        )
        ->first();

        return Responses::OK('', [
            "data" => $getPayments,
            "totalCreditValue" => $sumPayments['total_credit_value'] != null ? $sumPayments['total_credit_value'] : 0,
            "totalMembershipFee" => $sumPayments['total_membership_fee'] != null ? $sumPayments['total_membership_fee'] : 0,
            "totalCharges" => $sumPayments['total_charges'] != null ? $sumPayments['total_charges'] : 0,
            "totalFees" => $sumPayments['total_fees'] != null ? $sumPayments['total_fees'] : 0,
            "totalSumPayments" => $sumPayments['total_credit_value'] + $sumPayments['total_membership_fee'] + $sumPayments['total_charges'] + $sumPayments['total_fees'],
            "totalCreditValueMonthly" => $sumPayments['total_credit_value_monthly'] != null ? $sumPayments['total_credit_value_monthly'] : 0,
            "totalCreditValueSemiannual" => $sumPayments['total_credit_value_semiannual'] != null ? $sumPayments['total_credit_value_semiannual'] : 0,
            "totalCreditValueAnnual" => $sumPayments['total_credit_value_annual'] != null ? $sumPayments['total_credit_value_annual'] : 0,
            "financial_situation" => $user->financial_situation,
            "financial_situation_description" => $user->financial_situation_description,
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

        $getPayments = UserPayments::whereBetween('payment_date', [$inital_date, $finish_date])->where('user_id', $user_id)->with('user')->orderBy('payment_date', 'asc')->paginate($items_per_page);

        if (!$getPayments) {
            return Responses::BADREQUEST('Erro ao buscar pagamentos!');
        }

        $sumPayments = UserPayments::whereBetween('payment_date', [$inital_date, $finish_date])
        ->where('user_id', $user_id)
        ->select(
            DB::raw('SUM(credit_value) as total_credit_value'),
            DB::raw('SUM(membership_fee) as total_membership_fee'),
            DB::raw('SUM(charges) as total_charges'),
            DB::raw('SUM(fees) as total_fees'),
            DB::raw('SUM(CASE WHEN payment_type IN ("Mensalidade") THEN credit_value ELSE 0 END) as total_credit_value_monthly'),
            DB::raw('SUM(CASE WHEN payment_type IN ("Semestralidade") THEN credit_value ELSE 0 END) as total_credit_value_semiannual'),
            DB::raw('SUM(CASE WHEN payment_type IN ("Anuidade") THEN credit_value ELSE 0 END) as total_credit_value_annual')
        )
        ->first();

        return Responses::OK('', [
            "data" => $getPayments,
            "totalCreditValue" => $sumPayments['total_credit_value'] != null ? $sumPayments['total_credit_value'] : 0,
            "totalMembershipFee" => $sumPayments['total_membership_fee'] != null ? $sumPayments['total_membership_fee'] : 0,
            "totalCharges" => $sumPayments['total_charges'] != null ? $sumPayments['total_charges'] : 0,
            "totalFees" => $sumPayments['total_fees'] != null ? $sumPayments['total_fees'] : 0,
            "totalSumPayments" => $sumPayments['total_credit_value'] + $sumPayments['total_membership_fee'] + $sumPayments['total_charges'] + $sumPayments['total_fees'],
            "totalCreditValueMonthly" => $sumPayments['total_credit_value_monthly'] != null ? $sumPayments['total_credit_value_monthly'] : 0,
            "totalCreditValueSemiannual" => $sumPayments['total_credit_value_semiannual'] != null ? $sumPayments['total_credit_value_semiannual'] : 0,
            "totalCreditValueAnnual" => $sumPayments['total_credit_value_annual'] != null ? $sumPayments['total_credit_value_annual'] : 0,
            "financial_situation" => $user->financial_situation,
            "financial_situation_description" => $user->financial_situation_description,
            "associate_data" => $getUserInfos
        ]);
    }
}
