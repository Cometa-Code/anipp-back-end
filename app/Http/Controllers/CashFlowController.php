<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\CashFlow\CreateCashFlowRequest;
use App\Models\CashFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
