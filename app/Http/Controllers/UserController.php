<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Users\CreateUserRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(CreateUserRequest $request)
    {
        $hasUser = User::where('email', $request->email)->orWhere('document_cpf', $request->document_cpf)->first();

        if ($hasUser) {
            return Responses::BADREQUEST('E-mail ou CPF já cadastrados na base de dados!');
        }

        $data = $request->all();

        $data['role'] = 'associate';
        $data['is_associate'] = true;

        $createUser = User::create($data);

        if (!$createUser)
        {
            return Responses::BADREQUEST('Erro ao criar usuário');
        }

        return Responses::CREATED('Usuário criado com sucesso!', $createUser);
    }
}
