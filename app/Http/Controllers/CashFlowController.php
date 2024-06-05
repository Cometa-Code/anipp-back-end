<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\CashFlow\CreateCashFlowRequest;
use App\Models\CashFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use App\Imports\ExcelImport;
use App\Mail\NotIdentifierPaymentMail;
use App\Mail\NotIdentifierUserMail;
use App\Models\User;
use App\Models\UserPayments;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class CashFlowController extends Controller
{
    public function store(CreateCashFlowRequest $request)
    {
        $user = Auth::user();

        if ($user->role == 'associate') {
            return Responses::BADREQUEST('Apenas administradores podem tomar essa ação!');
        }

        $data = $request->all();

        if (!$request->user_id) {
            $data['user_id'] = $user->id;
        }

        $createdCashFlow = CashFlow::create($data);

        if (!$createdCashFlow) {
            return Responses::BADREQUEST('Ocorreu um erro durante a criação de um novo histórico no caixa');
        }

        return Responses::CREATED('Histórico adicionado ao fluxo de caixa com sucesso!', $createdCashFlow);
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role == 'associate') {
            return Responses::BADREQUEST('Apenas administradores podem tomar essa ação!');
        }

        $items_per_page = $request->query('items_per_page', 10);
        $inital_date = $request->query('initial_date', '2018-01-01');
        $finish_date = $request->query('finish_date', date('Y-m-d'));

        $getCashFlow = CashFlow::whereBetween('date', [$inital_date, $finish_date])->orderBy('date', 'desc')->paginate($items_per_page);

        $sumEntry = CashFlow::where('type', 'Entrada')
                            ->whereBetween('date', [$inital_date, $finish_date])
                            ->sum('value');

        $sumExit = CashFlow::where('type', 'Saida')
                            ->whereBetween('date', [$inital_date, $finish_date])
                            ->sum('value');

        $finalSum = $sumEntry - $sumExit;

        return Responses::OK('Sucesso', [
            'data' => $getCashFlow,
            'entry_sum' => $sumEntry,
            'exit_sum' => $sumExit,
            'final_sum' => $finalSum
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role == 'associate') {
            return Responses::BADREQUEST('Apenas administradores podem tomar essa ação!');
        }

        $getCashFlow = CashFlow::where('id', $id)->first();

        if (!$getCashFlow) {
            return Responses::BADREQUEST('Fluxo de caixa não encontrado!');
        }

        $data = $request->all();

        $getCashFlow->update($data);

        return Responses::OK('Fluxo atualizado com sucesso!');
    }

    public function read_extract(Request $request)
    {
        $user = Auth::user();

        if ($user->role == 'associate') {
            return Responses::BADREQUEST('Apenas administradores podem tomar essa ação!');
        }

        $extrato = $request->json()->all();

        $hasFullERR = false;
        $hasERR = false;
        $notIdentifierUserMails = [];
        $notIdentifierPaymentMail = [];

        /* For each geral */
        foreach ($extrato as $item) {
            $hasERR = false;
            if (array_key_exists('__EMPTY_8', $item) && $item['Extrato Conta Corrente'] != 'Data' && $item['__EMPTY_6'] != 'Aplicação BB CDB DI') {
                /* Verificando o tipo da movimentação */
                $type = $item['__EMPTY_8'];
                $value = floatval(str_replace(',', '.', str_replace('.', '', trim($item['__EMPTY_7']))));
                $date = Carbon::createFromFormat('d/m/Y', $item['Extrato Conta Corrente'])->format('Y-m-d');
                $origin_agency = $item['__EMPTY_2'];
                $allotment = $item['__EMPTY_3'];
                $document_number = $item['__EMPTY_4'];
                $history_code = $item['__EMPTY_5'];
                $history = $item['__EMPTY_6'];
                $history_detail = $item['__EMPTY_9'];

                /* Se for do tipo entrada */
                if ($type == 'C' || $type == 'c') {
                    $identifier_type = preg_match('/\d{14}/', $history_detail) ? 'a' : 'b';

                    /* Se for identificação por CPF */
                    if ($identifier_type == 'a') {
                        $getIdentifierNumber = preg_match('/\d{14}/', $history_detail, $matches);

                        $getUser = User::where('bank_identifier_a', $matches[0])->first();

                        /* Se não encontrar um usuário com essa identificação */
                        if (!$getUser) {
                            array_push($notIdentifierUserMails, [
                                'type' => $type,
                                'value' => $value,
                                'date' => $date,
                                'origin_agency' => $origin_agency,
                                'allotment' => $allotment,
                                'document_number' => $document_number,
                                'history_code' => $history_code,
                                'history' => $history,
                                'history_detail' => $history_detail
                            ]);

                            $hasERR = true;
                        }
                    }

                    /* Se for identificação por número de documento */
                    if ($identifier_type == 'b') {
                        $getUser = User::where('bank_identifier_b', $document_number)->first();

                        /* Se não encontrar um usuário com essa identificação */
                        if (!$getUser) {
                            /* $sendMail = Mail::to('vitorlauvresbarroso@gmail.com')->send(new NotIdentifierUserMail("
                                'type' => $type,
                                'value' => $value,
                                'date' => $date,
                                'origin_agency' => $origin_agency,
                                'allotment' => $allotment,
                                'document_number' => $document_number,
                                'history_code' => $history_code,
                                'history' => $history,
                                'history_detail' => $history_detail
                            ")); */

                            $hasERR = true;
                        }
                    }

                    $payment_type = false;

                    /* Verificar o valor do pagamento */
                    if ($value == 252 || $value == 288) {
                        $payment_type = 'Anuidade';
                    }

                    if ($value == 180) {
                        $payment_type = 'Semestralidade';
                    }

                    if ($value == 30) {
                        $payment_type = 'Mensalidade';
                    }

                    /* Se o valor do pagamento não for identificado nos padrões */
                    if (!$payment_type && !$hasERR) {
                        /* $sendMail = Mail::to('vitorlauvresbarroso@gmail.com')->send(new NotIdentifierPaymentMail($getUser->name, $getUser->email, $value, $date, $document_number)); */

                        $hasERR = true;
                    }

                    /* Se não tiver erros */
                    if (!$hasERR) {
                        /* Adiciona aos pagamentos do usuário */
                        UserPayments::create([
                            'user_id' => $getUser->id,
                            'payment_method' => $history,
                            'payment_type' => $payment_type,
                            'payment_date' => $date,
                            'credit_value' => $value,
                            'membership_fee' => 0,
                            'charges' => 0,
                            'fees' => 0,
                            'comments' => $document_number,
                        ]);

                        /* Adiciona ao fluxo de caixa */
                        CashFlow::create([
                            'user_id' => $getUser->id,
                            'type' => 'Entrada',
                            'date' => $date,
                            'origin_agency' => $origin_agency,
                            'allotment' => $allotment,
                            'document_number' => $document_number,
                            'history_code' => $history_code,
                            'history' => $history,
                            'history_detail' => $history_detail,
                            'value' => $value,
                        ]);
                    }

                    if ($hasERR) {
                        $hasFullERR = true;
                    }
                }

                /* Se for do tipo saída */
                if ($type == 'D' || $type == 'd') {
                    /* Criando registro no fluxo de caixa */
                    $createCashFlow = CashFlow::create([
                        'user_id' => $user->id,
                        'type' => 'Saida',
                        'date' => $date,
                        'origin_agency' => $origin_agency,
                        'allotment' => $allotment,
                        'document_number' => $document_number,
                        'history_code' => $history_code,
                        'history' => $history,
                        'history_detail' => $history_detail,
                        'value' => $value,
                    ]);
                }
            }
        }

        if ($hasFullERR) {
            return Responses::CREATED('Histórico processado com sucesso. Confira o seu e-mail para resolver as pendências encontradas!');
        }

        return Responses::CREATED('Histórico processo e adicionado com sucesso!');
    }
}
