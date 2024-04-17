<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Payments\CreatePaymentRequest;
use App\Models\UserPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
